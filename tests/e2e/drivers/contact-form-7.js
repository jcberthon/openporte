const { expect } = require('@playwright/test');
const { wp } = require('../helpers');

/**
 * Contact Form 7 (AJAX riding the native form submit pipeline).
 * Uses the "Contact Us" page provisioned by tests/bin/wp-init.sh.
 *
 * Status mapping (form element's data-status): captcha pass = 'sent', or
 * 'failed' when the bench has no mailer (mail failure happens AFTER the spam
 * check, so it still proves the captcha was accepted); captcha fail = 'spam'.
 */
module.exports = {
  key: 'contact-form-7',
  option: 'openporte_integration_contact_form_7',

  ensure() {
    wp('plugin activate contact-form-7');
    const pageId = wp('post list --post_type=page --name=contact-us --field=ID');
    if (!pageId) {
      throw new Error(
        "Fixture page 'contact-us' not found — run the wp-env afterStart hook (tests/bin/wp-init.sh) first."
      );
    }
    return { path: `/?page_id=${pageId}` };
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
  },

  async fill(page, ctx, marker) {
    await page.locator('[name="your-name"]').fill('E2E Tester');
    await page.locator('[name="your-email"]').fill('e2e@example.com');
    // The default CF7 template marks Subject required — leaving it empty
    // makes every submission come back data-status="invalid".
    await page.locator('[name="your-subject"]').fill('E2E matrix');
    await page.locator('[name="your-message"]').fill(marker);
  },

  async submit(page) {
    await page.locator('form.wpcf7-form [type="submit"]').click();
  },

  async expectAccepted(page) {
    await expect(page.locator('form.wpcf7-form')).toHaveAttribute(
      'data-status',
      /^(sent|failed)$/
    );
  },

  async expectRejected(page) {
    await expect(page.locator('form.wpcf7-form')).toHaveAttribute('data-status', 'spam');
  },
};
