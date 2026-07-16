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

    test.beforeAll(() => {
      applyBaseline();
      ctx = driver.ensure();
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

          // Pre-solve where the mode supports it. With floating on, or
          // auto=onsubmit, solving is submit-triggered (Floating UI implies
          // onsubmit when auto is unset) — submit first and let the widget /
          // the capture-phase glue hold the submission. That submit-triggered
          // path is exactly the race under test.
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
