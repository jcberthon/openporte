const { expect } = require('@playwright/test');
const { wp, waitForFrontEnd } = require('../helpers');

/**
 * Ninja Forms (client-rendered Backbone app, AJAX submit to admin-ajax).
 * Uses the "Ninja Test" page provisioned by tests/bin/wp-init.sh, which
 * carries the default "Contact Me" form Ninja Forms seeds on activation
 * (name, email, message — all required — plus submit).
 *
 * Status mapping: acceptance = the success-message action fills
 * .nf-response-msg ("Form submitted successfully."). The form's two email
 * actions are harmless on the mailer-less bench: Ninja Forms surfaces email
 * failures only to logged-in administrators, never to the anonymous visitor
 * these tests submit as. Rejection = OpenPorte's field error, which the
 * integration attaches to the submit field and Ninja Forms renders as an
 * .nf-error-msg under the submit button.
 */
module.exports = {
  key: 'ninja-forms',
  option: 'openporte_integration_ninja_forms',

  async ensure() {
    wp('plugin activate ninja-forms');
    const pageId = wp('post list --post_type=page --name=ninja-test --field=ID');
    if (!pageId) {
      throw new Error(
        "Fixture page 'ninja-test' not found — run the wp-env afterStart hook (tests/bin/wp-init.sh) first."
      );
    }
    const path = `/?page_id=${pageId}`;
    // The server renders only the empty form container (the fields arrive via
    // the client-side Backbone render); its presence proves Ninja Forms is
    // live on this page — same warm-up rationale as the wpDiscuz driver.
    await waitForFrontEnd(path, 'nf-form-cont');
    return { path };
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
    // Wait for the client-side render to replace the loading spinner — the
    // fill() locators would auto-wait too, but failing here names the real
    // problem (form never rendered) instead of a missing input.
    await page.locator('.nf-form-cont form').waitFor();
  },

  async fill(page, ctx, marker) {
    await page.locator('.nf-form-cont input[type="text"]').first().fill('E2E Tester');
    await page.locator('.nf-form-cont input[type="email"]').first().fill('e2e@example.com');
    await page.locator('.nf-form-cont textarea').first().fill(marker);
  },

  async submit(page) {
    await page.locator('.nf-form-cont input[type="submit"]').first().click();
  },

  async expectAccepted(page) {
    await expect(page.locator('.nf-form-cont .nf-response-msg')).toContainText(
      'Form submitted successfully'
    );
  },

  async expectRejected(page) {
    // Scoped to our own error's class, not the generic .nf-error-msg: Ninja
    // Forms 3.15 also renders an aggregate "fix the errors before
    // submitting" banner under that same generic class whenever any field
    // error is present, and the two together make .nf-error-msg ambiguous.
    // "openporte_invalid" is the 'slug' this integration attaches its error
    // with (integrations/ninja-forms.php); Ninja Forms turns a field error's
    // slug into "nf-error-<slug>" on the rendered message unconditionally, so
    // this stays exact regardless of which other errors are showing.
    await expect(page.locator('.nf-form-cont .nf-error-openporte_invalid')).toContainText(
      'Could not verify you are not a robot.'
    );
  },
};
