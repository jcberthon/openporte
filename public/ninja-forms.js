/**
 * Front-end glue between the ALTCHA widget and Ninja Forms.
 *
 * Ninja Forms renders and submits forms through its own Backbone app rather
 * than a native form submission, so neither of the two things a plain HTML
 * form gets for free is present here: the widget's hidden "altcha" input is
 * never serialized into the AJAX payload on its own, and the widget's
 * required checkbox does not stop Ninja Forms from attempting a submission
 * while it is unchecked. Both gaps are closed below.
 *
 * @since 1.29.0
 */
(() => {
  // nfRadio is guaranteed by the nf-front-end script dependency; the guard
  // only covers exotic setups that dequeue or break it.
  if (typeof nfRadio === 'undefined') {
    return;
  }

  nfRadio.channel('forms').on('before:submit', (form) => {
    // Multi-instance renders suffix the id ("3_2") — the container id and the
    // per-form radio channel use the same suffixed value, so no normalizing.
    const formId = form.get('id');
    const container = document.getElementById('nf-form-' + formId + '-cont');
    const input = container ? container.querySelector('input[name="altcha"]') : null;
    nfRadio
      .channel('form-' + formId)
      .request('add:extra', 'altcha', input ? input.value : '');
    if (container) {
      // Remember how many solves this form had when the submission left, so
      // the response handler below can tell whether another landed while it
      // was in flight. See the comment on solveCount().
      container.dataset.openporteSolvesAtSubmit = container.dataset.openporteSolves || '0';
    }
  });

  /**
   * Completed solves for one form.
   *
   * Counted by the 'verified' listener at the bottom of this file. Comparing
   * the count taken when a submission left with the count when its response
   * lands separates "a solve completed while the rejection was in flight" --
   * nothing else will ever fire, so the response handler has to clear the
   * error itself -- from "the widget was already solved and the server
   * rejected the token anyway" (expired or replayed), where the error is real
   * and must stay on screen until a fresh solve completes. Any solve counts,
   * not just a visitor-initiated one: an automatic re-solve (auto="onload",
   * or the widget refetching an expired challenge) leaves the visitor holding
   * a valid token just the same, so the stale rejection should go. A plain
   * "is it verified now?" test cannot tell those apart, and would wipe a
   * genuine rejection off the screen before it could be read.
   *
   * @since 1.29.0
   *
   * @param {HTMLElement} container The form's .nf-form-cont element.
   * @return {number} Solves completed on this form so far this page load.
   */
  const solveCount = (container) => Number(container.dataset.openporteSolves || 0);

  /**
   * Drop a pending rejection.
   *
   * Forgets the marker and tells Ninja Forms to remove the error, which is
   * what lifts its blocking 'field-errors' entry. A no-op when no rejection
   * is pending.
   *
   * @since 1.29.0
   *
   * @param {HTMLElement} container The form's .nf-form-cont element.
   * @return {void}
   */
  const clearRejection = (container) => {
    const fieldId = container.dataset.openporteRejectedField;
    if (!fieldId) {
      return;
    }
    delete container.dataset.openporteRejectedField;
    nfRadio.channel('fields').request('remove:error', fieldId, 'openporte_invalid');
  };

  // Ninja Forms refuses to (re-)submit while any field carries an error
  // (assets/js/min/front-end.js: controllers/submit.js checks
  // formModel.errors for a blocking entry before posting; that entry is the
  // 'field-errors' aggregate controllers/formErrors.js adds whenever any
  // field has one, and removes only once every field's errors are gone).
  // Nothing in Ninja Forms ever clears our error on its own: the field it is
  // attached to (openporte_ninja_forms_error_field_id() in
  // integrations/ninja-forms.php, normally the submit field itself) has no
  // value a visitor can edit to trigger the usual revalidate-on-change path
  // that clears other fields' errors. Left alone, one rejected submission
  // silently blocks every later attempt on that page load — solving the
  // widget afterwards does nothing, because Ninja Forms never lets a new
  // submission past its own stale-error check.
  //
  // The field id is read back from the server's own response rather than
  // recomputed here, so this can never disagree with which field the PHP
  // side actually chose, and it is stashed on the form's container rather
  // than in a form-id-keyed map so multiple Ninja Forms on one page each
  // track their own pending rejection independently.
  nfRadio.channel('forms').on('submit:response', (response, textStatus, jqXHR, formId) => {
    const fields = response && response.errors && response.errors.fields;
    if (!fields) {
      return;
    }
    const container = document.getElementById('nf-form-' + formId + '-cont');
    if (!container) {
      return;
    }
    Object.keys(fields).forEach((fieldId) => {
      const error = fields[fieldId];
      if (error && error.slug === 'openporte_invalid') {
        container.dataset.openporteRejectedField = fieldId;
      }
    });
    // A solve that finished while this submission was in flight has already
    // fired its 'verified' event -- before the marker just set existed, so
    // the listener below saw nothing to clear, and no further event is
    // coming. Left here, the marker would sit there for the life of the page
    // with Ninja Forms refusing every later submission. Clear it now instead.
    //
    // Deferred a tick because Ninja Forms applies the very error being
    // removed while handling this same response: a synchronous removal runs
    // too early -- before the error exists -- and is immediately undone.
    const atSubmit = container.dataset.openporteSolvesAtSubmit;
    if (atSubmit !== undefined && solveCount(container) > Number(atSubmit)) {
      setTimeout(() => clearRejection(container), 0);
    }
  });

  // Capture phase: the widget's 'verified' CustomEvent is not dispatched
  // with bubbles set, so a normal (bubble-phase) delegated listener never
  // sees it. Capture listeners on document run on the way down to every
  // event's target regardless of that event's own bubbling, which is what
  // makes one listener here enough to cover every widget on the page,
  // including ones added after load.
  document.addEventListener('verified', (ev) => {
    const container = ev.target.closest ? ev.target.closest('.nf-form-cont') : null;
    if (!container) {
      return;
    }
    container.dataset.openporteSolves = String(solveCount(container) + 1);
    clearRejection(container);
  }, true);
})();
