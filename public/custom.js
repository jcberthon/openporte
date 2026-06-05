(() => {
  document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => {
      if ('OPENPORTE_WIDGET_ATTRS' in window && typeof window['OPENPORTE_WIDGET_ATTRS'] === 'object') {
        [...document.querySelectorAll('altcha-widget')].forEach((el) => {
          if (typeof el['configure'] === 'function' && !el.getAttribute('challengeurl')) {
            el.configure(window['OPENPORTE_WIDGET_ATTRS']);
          }
        });
      }
    });
  });
})();
