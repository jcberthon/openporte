const { test } = require('@playwright/test');
const drivers = require('./drivers');
const {
  wpSetOption,
  applyBaseline,
  applyCombo,
  waitForVerified,
  clickWidgetCheckbox,
  removeWidgets,
} = require('./helpers');

// '' = the "Disabled" auto-verification mode (user ticks the checkbox).
const AUTO_MODES = ['', 'onload', 'onfocus', 'onsubmit'];
const FLOATING_MODES = [0, 1];

let counter = 0;

for (const driver of drivers) {
  test.describe(driver.key, () => {
    let ctx;

    test.beforeAll(async () => {
      applyBaseline();
      // ensure() may be async (a driver waiting for its plugin to be live).
      ctx = await driver.ensure();
      wpSetOption(driver.option, '1');
    });

    for (const auto of AUTO_MODES) {
      for (const floating of FLOATING_MODES) {
        const label = `auto=${auto || 'disabled'} floating=${floating ? 'on' : 'off'}`;

        test(`accepts a solved submission (${label})`, async ({ page }) => {
          applyCombo({ auto, floating });
          driver.reset?.(ctx);
          const marker = `e2e ${driver.key} ${label} #${++counter} ${Date.now()}`;

          await driver.open(page, ctx);
          await driver.fill(page, ctx, marker);

          // Pre-solve where the mode supports it. The rows that don't:
          //   - auto=onsubmit: nothing to pre-solve, the widget starts the
          //     solve from the submit itself and replays it when done.
          //   - floating on: the widget is anchored to the submit button, so
          //     these rows submit straight away and land mid-solve. Floating
          //     UI only implies onsubmit when auto is unset — with
          //     auto=onload/onfocus the click hits a required, still-unticked
          //     checkbox and is swallowed by constraint validation unless the
          //     capture-phase glue in public/script.js holds and replays it.
          //     That race is exactly what these rows cover.
          if (!floating && auto !== 'onsubmit') {
            if (auto === '') {
              await clickWidgetCheckbox(page);
            }
            if (auto === 'onfocus') {
              // fill() already focused the fields; solving should be running.
            }
            await waitForVerified(page);
          }

          await driver.submit(page, ctx);
          await driver.expectAccepted(page, ctx, marker);
        });
      }
    }

    test('rejects a submission that bypasses the widget', async ({ page }) => {
      // One negative control per integration: without it, a combo could
      // "pass" simply because server-side verification is not wired up.
      applyCombo({ auto: '', floating: 0 });
      driver.reset?.(ctx);
      const marker = `e2e ${driver.key} bypass #${++counter} ${Date.now()}`;

      await driver.open(page, ctx);
      await driver.fill(page, ctx, marker);
      await removeWidgets(page);
      await driver.submit(page, ctx);
      await driver.expectRejected(page, ctx);
    });
  });
}
