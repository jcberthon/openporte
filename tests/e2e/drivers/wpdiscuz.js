const { expect, request } = require('@playwright/test');
const {
  BASE_URL,
  wp,
  waitForFrontEnd,
  ensureCommentsPost,
  purgeComments,
} = require('../helpers');

// wp-env's default administrator.
const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';

/**
 * Make wpDiscuz build its "Default Form", without which it renders *nothing*.
 *
 * wpDiscuz creates that form only in pluginNewVersion(), which is hooked on
 * `admin_init` and gated on the plugin file's version being greater than the
 * stored `wc_plugin_version` option. A bench provisioned by wp-cli and driven
 * through the front end never fires `admin_init`, so neither the form nor the
 * `wpdiscuz_form_content_type_rel` option that binds it to the "post" type is
 * ever created — wpDiscuz stays silent and the theme falls back to core's
 * comment form, so #wpdcom never appears and every combo here times out.
 *
 * Worse, the gate is one-shot: once `wc_plugin_version` reaches the current
 * version the form can never be regenerated, so a bench that half-created it
 * stays broken until the option is rolled back. Reset the gate, then issue one
 * authenticated wp-admin request to let wpDiscuz build the form itself (rather
 * than hand-rolling its meta, which would rot against upstream's schema).
 */
async function primeDefaultForm() {
  try {
    if (wp('option get wpdiscuz_form_content_type_rel')) {
      return; // Already bound — nothing to do.
    }
  } catch (e) {
    // Option absent (wp-cli exits non-zero) — fall through and build the form.
  }
  wp('option update wc_plugin_version 1.0.0');
  const ctx = await request.newContext({ baseURL: BASE_URL });
  try {
    // newContext keeps the login cookie for the follow-up admin request.
    await ctx.post('/wp-login.php', {
      form: { log: ADMIN_USER, pwd: ADMIN_PASS, 'wp-submit': 'Log In' },
    });
    await ctx.get('/wp-admin/');
  } finally {
    await ctx.dispose();
  }
}

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

  async ensure() {
    try {
      wp('plugin is-installed wpdiscuz');
    } catch (e) {
      wp('plugin install wpdiscuz');
    }
    wp('plugin activate wpdiscuz');
    await primeDefaultForm();
    const postId = ensureCommentsPost();
    const path = `/?p=${postId}`;
    // Belt and braces: primeDefaultForm() has already bound the form, but the
    // first render still has to come up. Wait for it before test one.
    await waitForFrontEnd(path, 'wpdcom');
    return { path, postId };
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
