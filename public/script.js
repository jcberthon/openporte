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

      // wpDiscuz submits comments from a jQuery click handler delegated on
      // <body> (bubble phase) and never fires a native form submit, so the
      // widget's auto="onsubmit" interception — and Floating UI, which
      // implies onsubmit — cannot pause the submission: the AJAX serializes
      // an empty altcha field while the PoW is still being solved. Intercept
      // the click in the CAPTURE phase (which always runs before any
      // bubble-phase delegation), solve first, then replay the click; the
      // guard then sees state "verified" and lets it through to wpDiscuz.
      const wpdiscuzPending = new WeakSet();
      document.addEventListener('click', (ev) => {
        const button = ev.target instanceof Element ? ev.target.closest('.wc_comm_submit') : null;
        if (!button) {
          return;
        }
        const form = button.closest('form');
        const widgetEl = form ? form.querySelector('altcha-widget') : null;
        const state = widgetEl?.querySelector('.altcha')?.getAttribute('data-state');
        // No widget in this form, code-challenge mode, or already solved:
        // let wpDiscuz handle the click normally.
        if (!widgetEl || !state || state === 'verified' || state === 'code') {
          return;
        }
        ev.preventDefault();
        ev.stopPropagation();
        if (!wpdiscuzPending.has(widgetEl)) {
          wpdiscuzPending.add(widgetEl);
          widgetEl.addEventListener('verified', () => {
            wpdiscuzPending.delete(widgetEl);
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
