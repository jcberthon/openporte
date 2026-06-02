(() => {
  document.addEventListener('DOMContentLoaded', () => {
    function onApiChange(api) {
      [...document.querySelectorAll('[data-custom-api]')].forEach((el) => {
        el.disabled = api !== 'custom';
      });
    }
    const apiEl = document.querySelector('#altcha_api');
    if (apiEl) {
      apiEl.addEventListener('change', (ev) => onApiChange(ev.target.value));
      onApiChange(apiEl.value);
    }
  });
})();