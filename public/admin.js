(() => {
  document.addEventListener('DOMContentLoaded', () => {
    // Fields marked data-custom-api are the ones only Custom mode uses (the
    // Challenge URL); data-selfhosted-api marks the inverse — settings the
    // backend owns in Custom mode, so the control goes dead there (Complexity,
    // Expiration). Both attributes are rendered server-side, so the state is
    // already right on load; this only keeps it right while the API Mode
    // dropdown is being changed.
    function onApiChange(api) {
      const custom = api === 'custom';
      [...document.querySelectorAll('[data-custom-api]')].forEach((el) => {
        el.disabled = !custom;
      });
      [...document.querySelectorAll('[data-selfhosted-api]')].forEach((el) => {
        el.disabled = custom;
      });
      [...document.querySelectorAll('[data-selfhosted-note]')].forEach((el) => {
        el.style.display = custom ? '' : 'none';
      });
    }
    const apiEl = document.querySelector('#openporte_api');
    if (apiEl) {
      apiEl.addEventListener('change', (ev) => onApiChange(ev.target.value));
      onApiChange(apiEl.value);
    }

    // Preset dropdowns with a "Custom" choice reveal a companion number input.
    // Shared by Expiration and Replay limit; both render the same markup.
    function wireCustomToggle(selectId, inputId) {
      const selectEl = document.querySelector(selectId);
      const inputEl = document.querySelector(inputId);
      if (!selectEl || !inputEl) {
        return;
      }
      const sync = (value) => {
        inputEl.style.display = value === 'custom' ? '' : 'none';
      };
      selectEl.addEventListener('change', (ev) => sync(ev.target.value));
      sync(selectEl.value);
    }
    wireCustomToggle('#openporte_expires', '#openporte_expires_custom');
    wireCustomToggle('#openporte_replaylimit', '#openporte_replaylimit_custom');

    // Show/Hide toggle for password-type settings fields. The labels come
    // from data attributes rendered server-side, so they stay translated.
    [...document.querySelectorAll('.openporte-toggle-password')].forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) {
          return;
        }
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        btn.textContent = reveal ? btn.dataset.labelHide : btn.dataset.labelShow;
      });
    });

    // Copy button for password-type settings fields. The async Clipboard API
    // needs a secure context, and wp-admin over plain http (intranets) is
    // common — fall back to execCommand('copy') on a temporary textarea,
    // because copying straight from a type=password input is unreliable
    // across browsers.
    [...document.querySelectorAll('.openporte-copy-password')].forEach((btn) => {
      let timer = null;
      const flash = (label) => {
        // Clear any pending reset: a new click mid-flash must not let the
        // older timeout snap the label back to "Copy" early.
        if (timer !== null) {
          window.clearTimeout(timer);
        }
        btn.textContent = label;
        timer = window.setTimeout(() => {
          timer = null;
          btn.textContent = btn.dataset.labelCopy;
        }, 2000);
      };
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) {
          return;
        }
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(input.value).then(
            () => flash(btn.dataset.labelCopied),
            () => flash(btn.dataset.labelFailed)
          );
          return;
        }
        const helper = document.createElement('textarea');
        helper.value = input.value;
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();
        let copied = false;
        try {
          copied = document.execCommand('copy');
        } catch (err) {
          copied = false;
        }
        helper.remove();
        flash(copied ? btn.dataset.labelCopied : btn.dataset.labelFailed);
      });
    });

    // Regenerate button for the Shared Secret field: fills the input with a
    // fresh 256-bit hex secret, matching the server-side format produced by
    // OpenPortePlugin::random_secret() (bin2hex(random_bytes(32))). Nothing
    // is stored until the user saves, so a reload cancels it.
    [...document.querySelectorAll('.openporte-regenerate-secret')].forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input || !window.crypto || !window.crypto.getRandomValues) {
          return;
        }
        const bytes = new Uint8Array(32);
        window.crypto.getRandomValues(bytes);
        input.value = [...bytes]
          .map((byte) => byte.toString(16).padStart(2, '0'))
          .join('');
      });
    });
  });
})();
