(() => {
  // Ninja Forms renders and submits forms through its Backbone app: the AJAX
  // payload is built from the field models plus an "extra" object, so the
  // widget's hidden "altcha" input — like any plain input in the markup — is
  // never serialized into the submission on its own. Copy the solved-challenge
  // payload into "extra" when a form submits, where the server-side
  // ninja_forms_submit_data callback (integrations/ninja-forms.php) reads it.
  //
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
  });
})();
