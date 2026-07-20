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
  });
})();