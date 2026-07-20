const { expect } = require('@playwright/test');
const { wp } = require('../helpers');

/**
 * WPForms Lite (native form POST; the page reloads with either a confirmation
 * message or the form re-rendered with an error header).
 *
 * Uses the "WPForms Test" page + minimal fixture form provisioned by the
 * wp-env afterStart hook (tests/bin/wp-init.sh). The fixture keeps WPForms'
 * own anti-spam token and honeypot disabled, so the only spam gate on the
 * form is OpenPorte — exactly what these tests must isolate.
 *
 * Accepted: the fixture's confirmation message replaces the form.
 * Rejected: integrations/wpforms.php sets the form error header to
 * "Could not verify you are not a robot."
 */
module.exports = {
  key: 'wpforms',
  option: 'openporte_integration_wpforms',

  ensure() {
    wp('plugin activate wpforms-lite');
    const pageId = wp('post list --post_type=page --name=wpforms-test --field=ID');
    if (!pageId) {
      throw new Error(
        "Fixture page 'wpforms-test' not found — run the wp-env afterStart hook (tests/bin/wp-init.sh) first."
      );
    }
    return { path: `/?page_id=${pageId}` };
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
    await page.locator('form.wpforms-form').waitFor();
  },

  async fill(page, ctx, marker) {
    await page.locator('form.wpforms-form textarea').fill(marker);
  },

  async submit(page) {
    await page.locator('form.wpforms-form button.wpforms-submit').click();
  },

  async expectAccepted(page) {
    // Confirmation message from the fixture form JSON (wp-init.sh).
    await expect(page.locator('body')).toContainText('Thanks for contacting us!');
  },

  async expectRejected(page) {
    await expect(page.locator('body')).toContainText(/could not verify/i);
  },
};
