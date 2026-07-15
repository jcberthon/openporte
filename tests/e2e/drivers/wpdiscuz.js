const { expect } = require('@playwright/test');
const { wp, wpSetOption } = require('../helpers');

/**
 * wpDiscuz (jQuery click-delegated AJAX; never fires a native form submit —
 * the integration that exposed the auto="onsubmit"/Floating UI race).
 *
 * NOTE: selectors follow wpDiscuz 7.x guest-form markup (wc_name/wc_email
 * fields, .wc_comm_submit button, Quill .ql-editor or plain textarea for the
 * message). This driver is the one most likely to need a tuning pass against
 * the live DOM — see README.
 */
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
    let postId = wp('post list --post_type=post --name=e2e-comments --field=ID');
    if (!postId) {
      postId = wp(
        "post create --post_title='E2E Comments' --post_name=e2e-comments --post_status=publish --post_content='e2e fixture' --porcelain"
      );
    }
    wp(`post update ${postId} --comment_status=open`);
    wpSetOption('comment_moderation', '0');
    wpSetOption('comment_previously_approved', '0');
    return { path: `/?p=${postId}` };
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
    await page.locator('#wpcomm').waitFor();
  },

  async fill(page, ctx, marker) {
    // Message: rich editor (Quill) in default wpDiscuz 7, plain textarea in
    // "minimal" layouts — support both.
    const rich = page.locator('#wpcomm .ql-editor').first();
    if (await rich.count()) {
      await rich.click();
      await rich.fill(marker);
    } else {
      await page.locator('#wpcomm textarea').first().fill(marker);
    }
    // Guest fields appear once the editor is focused.
    const name = page.locator('#wpcomm [name="wc_name"]').first();
    if (await name.count()) {
      await name.fill('E2E Tester');
      await page.locator('#wpcomm [name="wc_email"]').first().fill('e2e@example.com');
    }
  },

  async submit(page) {
    await page.locator('#wpcomm .wc_comm_submit').first().click();
  },

  async expectAccepted(page, ctx, marker) {
    // The AJAX flow inserts the new comment into the thread.
    await expect(page.locator('#wpcomm')).toContainText(marker);
  },

  async expectRejected(page) {
    // Server wp_die() text is surfaced through wpDiscuz's AJAX error message.
    await expect(page.locator('body')).toContainText(/could not verify|robot/i);
  },
};
