const { expect } = require('@playwright/test');
const { wp, ensureCommentsPost, purgeComments } = require('../helpers');

/**
 * wpDiscuz (jQuery click-delegated AJAX; never fires a native form submit —
 * the integration that exposed the auto="onsubmit"/Floating UI race).
 *
 * Selectors verified against wpDiscuz 7.6 on the bench: container #wpdcom,
 * visible main form .wpd_main_comm_form (a hidden secondary template form
 * duplicates every field — always scope to the main form), Quill .ql-editor
 * for the message, [name=wc_name]/[name=wc_email] guest fields, and the
 * .wc_comm_submit button (the same class public/script.js's capture-phase
 * race glue targets).
 */
const FORM = '#wpdcom form.wpd_main_comm_form';

module.exports = {
  key: 'wpdiscuz',
  option: 'openporte_integration_wpdiscuz',

  ensure() {
    try {
      wp('plugin is-installed wpdiscuz');
    } catch (e) {
      wp('plugin install wpdiscuz');
    }
    wp('plugin activate wpdiscuz');
    const postId = ensureCommentsPost();
    return { path: `/?p=${postId}`, postId };
  },

  // See comments.js — same core comment-flood throttle applies to the
  // wpDiscuz AJAX pipeline (it ends in wp_new_comment() too).
  reset(ctx) {
    purgeComments(ctx.postId);
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
    await page.locator('#wpdcom').waitFor();
  },

  async fill(page, ctx, marker) {
    // Message: rich editor (Quill) in default wpDiscuz 7, plain textarea in
    // "minimal" layouts — support both.
    const rich = page.locator(`${FORM} .ql-editor`).first();
    if (await rich.count()) {
      await rich.click();
      await rich.fill(marker);
    } else {
      await page.locator(`${FORM} textarea[name="wc_comment"]`).fill(marker);
    }
    const name = page.locator(`${FORM} [name="wc_name"]`);
    if (await name.count()) {
      await name.fill('E2E Tester');
      await page.locator(`${FORM} [name="wc_email"]`).fill('e2e@example.com');
    }
  },

  async submit(page) {
    await page.locator(`${FORM} .wc_comm_submit`).click();
  },

  async expectAccepted(page, ctx, marker) {
    // The AJAX flow inserts the new comment into the thread.
    await expect(page.locator('#wpdcom')).toContainText(marker);
  },

  async expectRejected(page) {
    // Server wp_die() text is surfaced through wpDiscuz's AJAX error message.
    await expect(page.locator('body')).toContainText(/could not verify|robot/i);
  },
};
