(() => {
  document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => {
      // Tracks widgets whose in-flight solve was started by the widget's own
      // submit interception (auto="onsubmit" / Floating UI): those replay the
      // submission themselves once solved, so we must not queue a second one.
      const widgetManagedSolve = new WeakSet();
      const pendingResubmit = new WeakSet();
      [...document.querySelectorAll('altcha-widget')].forEach((el) => {
        // add the name attr to fix input validation exception
        const altcha = el.querySelector('.altcha')
        const checkbox = el.querySelector('input[type="checkbox"]')
        checkbox?.setAttribute('name', '');
        const form = el.closest('form');
        if (form && checkbox && altcha?.getAttribute('data-state') !== 'code') {
          form.addEventListener('submit', (ev) => {
            const state = altcha?.getAttribute('data-state');
            if (state === 'code') {
              return;
            }
            if (state === 'unverified' || state === 'error') {
              // In onsubmit/floating modes the widget intercepts this submit,
              // solves, and replays it itself when done — remember that so the
              // 'verifying' branch below doesn't queue a duplicate replay.
              widgetManagedSolve.add(el);
              return;
            }
            if (state === 'verifying') {
              // A submit landing while a solve is already in flight (started
              // by auto="onload"/"onfocus") is dropped: the widget's floating/
              // onsubmit interception preventDefaults it but never replays it,
              // and in the other modes the unchecked-checkbox guard below
              // would block it. Queue exactly one replay for when the solve
              // finishes — unless the widget started this solve from a submit
              // and will replay on its own.
              ev.preventDefault();
              ev.stopPropagation();
              if (!widgetManagedSolve.has(el) && !pendingResubmit.has(el)) {
                pendingResubmit.add(el);
                el.addEventListener('verified', () => {
                  pendingResubmit.delete(el);
                  form.requestSubmit ? form.requestSubmit() : form.submit();
                }, { once: true });
              }
              return;
            }
            widgetManagedSolve.delete(el);
            if (!checkbox.reportValidity()) {
              ev.preventDefault();
              ev.stopPropagation();
            }
          }, true);
        }
      });

      // Removes duplicate widgets when manipulated with JS such as elementor popups and wpdiscuz
      const observer = new MutationObserver((mutations) => {
        [...document.querySelectorAll('altcha-widget')].forEach((el) => {
          const altchas = [...el.querySelectorAll('.altcha')];
          if (altchas.length > 1) {
            altchas.slice(0, -1).forEach((altcha) => altcha.remove());
          }
        })
      });
      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });

      // Capture-phase click guard, for the three ways a submit click is lost
      // while the PoW is still being solved:
      //
      // 1. Any form. With auto="onload"/"onfocus" the widget's checkbox is
      //    required and still unchecked during the solve, so a click on submit
      //    is rejected by the browser's own constraint validation: no 'submit'
      //    event is fired at all and the guard above never sees it. The solve
      //    finishes moments later, but the click is already gone — the visitor
      //    has to press the button a second time.
      // 2. wpDiscuz. It submits from a jQuery click handler delegated on
      //    <body> (bubble phase) and never fires a native form submit, so the
      //    widget's auto="onsubmit" interception cannot pause the submission:
      //    the AJAX serializes an empty altcha field mid-solve. Here even an
      //    unverified widget must be solved first, hence the wider condition.
      // 3. onsubmit mode (explicit, or implied by Floating UI). The widget
      //    intercepts and replays the submit itself, but its replay loses the
      //    submit button's name/value — fatal for WooCommerce login/register,
      //    whose handlers key on that field and silently ignore the POST.
      //
      // A capture-phase listener always runs before bubble-phase delegation
      // and before constraint validation, so hold the click, then replay it
      // once solved; the replay finds state "verified" and passes straight
      // through (to the browser, or to wpDiscuz).
      const pendingClick = new WeakSet();
      document.addEventListener('click', (ev) => {
        const target = ev.target instanceof Element ? ev.target : null;
        if (!target) {
          return;
        }
        const wpdiscuzButton = target.closest('.wc_comm_submit');
        const button = wpdiscuzButton || target.closest('button, input[type="submit"], input[type="image"]');
        // Ignore non-submitting controls and the widget's own UI (the
        // checkbox, the code-challenge buttons).
        if (!button || button.closest('altcha-widget')) {
          return;
        }
        if (!wpdiscuzButton && button.type !== 'submit' && button.type !== 'image') {
          return;
        }
        const form = button.form || button.closest('form');
        const widgetEl = form ? form.querySelector('altcha-widget') : null;
        const state = widgetEl?.querySelector('.altcha')?.getAttribute('data-state');
        // No widget in this form, code-challenge mode, or already solved:
        // let the click through untouched.
        if (!widgetEl || !state || state === 'verified' || state === 'code') {
          return;
        }
        // Outside wpDiscuz, hold the click when a solve is in flight, and
        // also when the widget itself would intercept this submit (explicit
        // auto="onsubmit", or Floating UI implying it): the widget's own
        // replay drops the submit button's name/value from the POST (solving
        // resets its stored submitter), which breaks handlers that key on the
        // button — WooCommerce ignores a login/register POST without
        // login=/register=. Our click replay keeps the submitter. (Enter-key
        // submissions skip this path and keep the upstream quirk.)
        // An unverified widget outside onsubmit mode is just waiting for the
        // visitor to tick the box — let the browser's validation handle it.
        if (!wpdiscuzButton && state !== 'verifying') {
          const config = typeof widgetEl.getConfiguration === 'function'
            ? widgetEl.getConfiguration()
            : null;
          const auto = config
            ? config.auto
            : (widgetEl.getAttribute('auto')
              || (widgetEl.hasAttribute('floating') ? 'onsubmit' : ''));
          if (auto !== 'onsubmit') {
            return;
          }
        }
        ev.preventDefault();
        ev.stopPropagation();
        if (!pendingClick.has(widgetEl)) {
          pendingClick.add(widgetEl);
          widgetEl.addEventListener('verified', () => {
            pendingClick.delete(widgetEl);
            button.click();
          }, { once: true });
        }
        // 'verifying' means a solve is already in flight — just wait for it.
        if (state !== 'verifying') {
          widgetEl.verify?.();
        }
      }, true);
    });
  });
})();
