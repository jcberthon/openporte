(() => {
  document.addEventListener('DOMContentLoaded', () => {
    function onApiChange(api) {
      [...document.querySelectorAll('[data-custom-api]')].forEach((el) => {
        el.disabled = api !== 'custom';
      });
    }
    const apiEl = document.querySelector('#openporte_api');
    if (apiEl) {
      apiEl.addEventListener('change', (ev) => onApiChange(ev.target.value));
      onApiChange(apiEl.value);
    }

    // Show the free-form seconds input only when Expiration is set to Custom.
    function onExpiresChange(value) {
      const customEl = document.querySelector('#openporte_expires_custom');
      if (customEl) {
        customEl.style.display = value === 'custom' ? '' : 'none';
      }
    }
    const expiresEl = document.querySelector('#openporte_expires');
    if (expiresEl) {
      expiresEl.addEventListener('change', (ev) => onExpiresChange(ev.target.value));
      onExpiresChange(expiresEl.value);
    }

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
      const flash = (label) => {
        btn.textContent = label;
        window.setTimeout(() => {
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
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  });
})();