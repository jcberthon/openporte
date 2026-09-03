const { test, expect } = require('@playwright/test');
const drivers = require('./drivers');
const {
  wpSetOption,
  applyBaseline,
  applyCombo,
  waitForVerified,
  clickWidgetCheckbox,
  tamperWidgetToken,
  removeWidgets,
  captureWidgetToken,
  injectToken,
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

    test('rejects a tampered widget token', async ({ page }) => {
      // Second negative control: the bypass test covers a *missing* token,
      // this one a *forged* token — a genuinely solved payload whose HMAC
      // signature is corrupted after the solve. Client-side everything looks
      // verified; only the server's signature check can catch it.
      applyCombo({ auto: '', floating: 0 });
      driver.reset?.(ctx);
      const marker = `e2e ${driver.key} tampered #${++counter} ${Date.now()}`;

      await driver.open(page, ctx);
      await driver.fill(page, ctx, marker);
      await clickWidgetCheckbox(page);
      await waitForVerified(page);
      await tamperWidgetToken(page);
      await driver.submit(page, ctx);
      await driver.expectRejected(page, ctx);
    });

    if (driver.key === 'wordpress-comments') {
      test('rejects an expired (stale, replayed) token', async ({ page }) => {
        // Expiry is enforced in OpenPortePlugin::verify_solution() from the
        // expires timestamp the server signed into the salt — the check is
        // integration-agnostic, so one driver covers it for the whole suite.
        applyCombo({ auto: '', floating: 0 });
        wpSetOption('openporte_expires', '2');
        try {
          driver.reset?.(ctx);
          const marker = `e2e ${driver.key} expired #${++counter} ${Date.now()}`;

          await driver.open(page, ctx);
          await driver.fill(page, ctx, marker);
          await clickWidgetCheckbox(page);
          await waitForVerified(page);
          // Capture the solved token and detach the widget: a live widget
          // auto-refetches expired challenges (refetchonexpire defaults on)
          // and would swap in a fresh token before we submit. Detaching turns
          // this into the real attack shape — replaying a stale token.
          const stale = await captureWidgetToken(page);
          await removeWidgets(page);
          // Outlive the 2 s validity window, then replay the stale token.
          await page.waitForTimeout(4000);
          await injectToken(page, stale);
          await driver.submit(page, ctx);
          await driver.expectRejected(page, ctx);
        } finally {
          wpSetOption('openporte_expires', '300'); // restore the baseline
        }
      });
    }

    if (driver.key === 'ninja-forms') {
      test('recovers after a rejected submission once corrected', async ({ page }) => {
        // Ninja Forms bypasses the widget's native required-checkbox
        // validation entirely — clicking Submit while unverified reaches the
        // server and is genuinely rejected, not just blocked client-side like
        // the other drivers' forms. That rejection attaches an error to a
        // Ninja Forms field (the submit field — see
        // openporte_ninja_forms_error_field_id() in
        // integrations/ninja-forms.php), and Ninja Forms itself refuses any
        // further submission while that field carries an error. Nothing
        // clears it automatically: the field has no value a visitor can edit
        // to trigger Ninja Forms' usual revalidate-on-change path. Solving
        // the widget afterwards did nothing until public/ninja-forms.js
        // started explicitly clearing this specific error once the widget
        // reports 'verified' — without that, one rejected attempt silently
        // wedges the form for the rest of the page's life.
        applyCombo({ auto: '', floating: 0 });
        driver.reset?.(ctx);
        const marker = `e2e ${driver.key} recovers #${++counter} ${Date.now()}`;

        await driver.open(page, ctx);
        await driver.fill(page, ctx, marker);
        await driver.submit(page, ctx);
        await driver.expectRejected(page, ctx);

        await clickWidgetCheckbox(page);
        await waitForVerified(page);
        await driver.submit(page, ctx);
        await driver.expectAccepted(page, ctx, marker);
      });

      test('recovers when the rejection lands after the widget verified', async ({ page }) => {
        // The sibling above covers the ordering the recovery was first written
        // for: the rejection round-trips, THEN the visitor solves, and the
        // widget's 'verified' clears the error. This covers the inverted
        // ordering — the visitor solves while the rejection is still in flight,
        // so 'verified' fires before public/ninja-forms.js has a rejected
        // field id to clear. Nothing fires afterwards, so unless the
        // response handler itself clears an already-verified form, the error
        // sits there and Ninja Forms wedges every later submission for the
        // life of the page load.
        //
        // A fast bench never produces this ordering on its own — the rejection
        // beats the solve every time — so the response is held until the solve
        // has verified, making the ordering deterministic instead of a race
        // the test would usually lose. Holding until 'verified' (not a fixed
        // delay) keeps it robust to solve speed.
        //
        // Generalising this response-timing coverage to the other AJAX
        // integrations, behind a reusable helper, is issue #114.
        applyCombo({ auto: '', floating: 0 });
        driver.reset?.(ctx);
        const marker = `e2e ${driver.key} late-reject #${++counter} ${Date.now()}`;

        let releaseResponse;
        const held = new Promise((resolve) => {
          releaseResponse = resolve;
        });
        let alreadyHeld = false;
        // What the server actually replied to the unsolved submission. Checked
        // below: without it this test would pass just as happily if
        // verification stopped rejecting anything at all, since a submission
        // that is never rejected also never wedges the form.
        let heldBody = null;
        let rejectionDelivered = Promise.resolve(null);
        await page.route('**/admin-ajax.php', async (route) => {
          const body = route.request().postData() || '';
          // Hold only the first Ninja Forms submission — the rejected one.
          if (alreadyHeld || !body.includes('nf_ajax_submit')) {
            return route.continue();
          }
          alreadyHeld = true;
          const response = await route.fetch();
          heldBody = await response.text();
          await held;
          await route.fulfill({ response });
        });

        try {
          await driver.open(page, ctx);
          await driver.fill(page, ctx, marker);

          // Armed before the submission: the fulfilment happens the moment we
          // release below, so waiting for it afterwards could miss it and hang.
          // The catch keeps a failure here from surfacing as an unhandled
          // rejection when an assertion below fails first.
          rejectionDelivered = page
            .waitForResponse(
              (res) =>
                res.url().includes('admin-ajax.php') &&
                (res.request().postData() || '').includes('nf_ajax_submit')
            )
            .catch(() => null);

          // Submit unsolved: the server rejects, but the response is held.
          await driver.submit(page, ctx);
          // Solve while it is still in flight — 'verified' fires now, before
          // the rejection has been delivered.
          await clickWidgetCheckbox(page);
          await waitForVerified(page);
        } finally {
          // Always release, even if the solve above threw: a route handler
          // left parked on `held` hangs the request and turns a clear failure
          // into a suite-level timeout.
          releaseResponse();
        }

        // The submission we held must really have been rejected, and by us.
        expect(heldBody).toContain('openporte_invalid');

        // Wait for the rejection to be delivered rather than sleeping for it.
        await rejectionDelivered;
        // Ninja Forms applies the error while handling that response and the
        // fix clears it a tick later, so poll for the end state. Non-vacuous
        // only because the assertion above proved a rejection came back:
        // without it, "no error present" would also be true before one was
        // ever applied.
        await expect(page.locator('.nf-form-cont .nf-error-openporte_invalid')).toHaveCount(0);

        // If the form is wedged, this second submit never reaches the server
        // and expectAccepted times out — the failure this test exists for.
        await driver.submit(page, ctx);
        await driver.expectAccepted(page, ctx, marker);
      });
    }
  });
}
