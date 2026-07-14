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
  });
})();