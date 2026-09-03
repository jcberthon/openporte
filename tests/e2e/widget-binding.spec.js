/**
 * Hermetic tests for the widget-binding half of public/script.js.
 *
 * Unlike settings-matrix.spec.js these need no bench: the page is served by a
 * route interceptor and the ALTCHA widget is a minimal stub. That still buys a
 * real browser — real custom-element upgrade timing, real MutationObserver,
 * real capture-phase ordering — for the one behaviour the matrix structurally
 * cannot see: a widget inserted into a form *after* page load.
 *
 * The matrix misses it because every submission path it drives goes through
 * the capture-phase click guard, which queries widgets at click time and so
 * works whether or not the per-widget submit listener was ever bound. Enter is
 * one of those paths, not an exception to them: the browser's implicit
 * submission dispatches a click on the form's default button, which is what
 * the guard is watching for.
 *
 * On Ninja Forms the binding is not merely untested but unobservable, which is
 * what makes this spec the only guard for it rather than the cheap one. Ninja
 * Forms submits from a delegated click handler and preventDefaults the click,
 * so no native submit event is ever dispatched on its forms — measured on the
 * bench with the click guard disabled and the widget checkbox not required
 * (auto="onsubmit"), the two conditions that would let one through. With
 * nothing there to fire it, the per-widget listener cannot distinguish a bound
 * widget from an unbound one.
 *
 * The stub models only what docs/agents/altcha-upstream.md already names as
 * the contract to re-check on each widget update: a `.altcha` element carrying
 * `data-state`, and an `input[type=checkbox]`. Keep it that thin. A richer
 * stub would drift from the real widget and start testing itself.
 */

const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');

const SCRIPT = fs.readFileSync(path.join(__dirname, '..', '..', 'public', 'script.js'), 'utf8');

// Intercepted, never resolved: no DNS, no bench, no network.
const ORIGIN = 'http://openporte.test';

const STUB = `
  class AltchaWidgetStub extends HTMLElement {
    connectedCallback() {
      // "bare" widgets stand in for the real element between insertion and
      // the moment it has rendered its own internals.
      if (this.getAttribute('data-bare') !== 'true') {
        this.renderInternals();
      }
    }
    renderInternals() {
      this.innerHTML = '<div class="altcha" data-state="unverified"><input type="checkbox"></div>';
    }
    setState(state) {
      this.querySelector('.altcha').setAttribute('data-state', state);
    }
  }
  customElements.define('altcha-widget', AltchaWidgetStub);

  // Bubble-phase recorder. script.js binds in capture phase, so it always runs
  // first: a submit it holds never reaches this listener.
  window.__submits = 0;
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('form').addEventListener('submit', (ev) => {
      ev.preventDefault();
      window.__submits += 1;
    });
  });
`;

/**
 * Serve a one-form fixture page with public/script.js loaded for real.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{widgetAtLoad?: boolean, bare?: boolean}} options
 */
async function openFixture(page, { widgetAtLoad = false, bare = false } = {}) {
  const widget = widgetAtLoad
    ? `<altcha-widget${bare ? ' data-bare="true"' : ''}></altcha-widget>`
    : '';
  const html = `<!doctype html><html><head><meta charset="utf-8"><title>fixture</title></head>
    <body>
      <form id="f" action="/submitted" method="post">
        <input type="text" name="x" value="v">
        ${widget}
        <button type="submit">Send</button>
      </form>
      <script src="/stub.js"></script>
      <script src="/script.js"></script>
    </body></html>`;

  await page.route(`${ORIGIN}/**`, (route) => {
    const { pathname } = new URL(route.request().url());
    if (pathname === '/script.js') {
      return route.fulfill({ contentType: 'text/javascript', body: SCRIPT });
    }
    if (pathname === '/stub.js') {
      return route.fulfill({ contentType: 'text/javascript', body: STUB });
    }
    return route.fulfill({ contentType: 'text/html', body: html });
  });

  await page.goto(`${ORIGIN}/`);
}

/**
 * Wait past script.js's DOMContentLoaded + requestAnimationFrame entry point,
 * and past any pending MutationObserver callback. Two frames is deterministic
 * where a fixed timeout would only be probable.
 *
 * @param {import('@playwright/test').Page} page
 */
function settle(page) {
  return page.evaluate(() => new Promise((resolve) => {
    requestAnimationFrame(() => requestAnimationFrame(resolve));
  }));
}

/**
 * Drive the behaviour that proves a widget is bound: a submit arriving mid
 * solve must be held, and replayed once the widget reports 'verified'.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<{held: number, afterVerified: number}>} Recorded submits
 *          before the solve completes, and after the replay.
 */
function submitDuringSolve(page) {
  return page.evaluate(async () => {
    const widget = document.querySelector('altcha-widget');
    widget.setState('verifying');
    document.querySelector('form').requestSubmit();
    const held = window.__submits;
    widget.setState('verified');
    widget.dispatchEvent(new Event('verified'));
    await new Promise((resolve) => requestAnimationFrame(resolve));
    return { held, afterVerified: window.__submits };
  });
}

test.describe('public/script.js submit binding', () => {
  test('binds a widget present at page load', async ({ page }) => {
    await openFixture(page, { widgetAtLoad: true });
    await settle(page);

    expect(await submitDuringSolve(page)).toEqual({ held: 0, afterVerified: 1 });
  });

  test('binds a widget inserted after page load', async ({ page }) => {
    await openFixture(page);
    await settle(page);

    // What Ninja Forms does: the form is rendered from JavaScript long after
    // the one-shot DOMContentLoaded scan has run.
    await page.evaluate(() => {
      const widget = document.createElement('altcha-widget');
      document.querySelector('form').appendChild(widget);
    });
    await settle(page);

    expect(await submitDuringSolve(page)).toEqual({ held: 0, afterVerified: 1 });
  });

  test('binds a late widget only once, however many mutations follow', async ({ page }) => {
    await openFixture(page);
    await settle(page);

    await page.evaluate(async () => {
      document.querySelector('form').appendChild(document.createElement('altcha-widget'));
      // Unrelated DOM churn — an Elementor popup, a wpDiscuz re-render — must
      // not stack a second listener, which would replay the submit twice.
      for (let i = 0; i < 5; i += 1) {
        const noise = document.createElement('div');
        document.body.appendChild(noise);
        await new Promise((resolve) => requestAnimationFrame(resolve));
        noise.remove();
      }
    });
    await settle(page);

    expect(await submitDuringSolve(page)).toEqual({ held: 0, afterVerified: 1 });
  });

  test('picks up a widget that had not rendered its internals yet', async ({ page }) => {
    await openFixture(page);
    await settle(page);

    // Inserted but empty: no .altcha, no checkbox. Binding cannot happen yet,
    // and must not be written off as done.
    await page.evaluate(() => {
      const widget = document.createElement('altcha-widget');
      widget.setAttribute('data-bare', 'true');
      document.querySelector('form').appendChild(widget);
    });
    await settle(page);

    await page.evaluate(() => document.querySelector('altcha-widget').renderInternals());
    await settle(page);

    expect(await submitDuringSolve(page)).toEqual({ held: 0, afterVerified: 1 });
  });
});
