const { expect } = require('@playwright/test');
const { wp, wpSetOption } = require('../helpers');

/**
 * WordPress core comments (native form POST to wp-comments-post.php).
 * The widget's auto="onsubmit" interception works on this native pipeline,
 * so this driver doubles as the control for the wpDiscuz comparison.
 */
module.exports = {
  key: 'wordpress-comments',
  option: 'openporte_integration_wordpress_comments',

  ensure() {
    // wpDiscuz takes over the comment area when active — keep it out of
    // this driver's runs.
    try {
      wp('plugin deactivate wpdiscuz');
    } catch (e) {
      /* not installed/active — fine */
    }
    let postId = wp('post list --post_type=post --name=e2e-comments --field=ID');
    if (!postId) {
      postId = wp(
        "post create --post_title='E2E Comments' --post_name=e2e-comments --post_status=publish --post_content='e2e fixture' --porcelain"
      );
    }
    wp(`post update ${postId} --comment_status=open`);
    // Approve anonymous comments immediately so acceptance is assertable.
    wpSetOption('comment_moderation', '0');
    wpSetOption('comment_previously_approved', '0');
    return { path: `/?p=${postId}` };
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
  },

  async fill(page, ctx, marker) {
    await page.locator('#comment').fill(marker);
    await page.locator('#author').fill('E2E Tester');
    await page.locator('#email').fill('e2e@example.com');
  },

  async submit(page) {
    await page.locator('#submit').click();
  },

  async expectAccepted(page, ctx, marker) {
    // Native POST navigates back to the post with the comment rendered.
    await expect(page.locator('body')).toContainText(marker);
  },

  async expectRejected(page) {
    // Server-side failure is a wp_die() page.
    await expect(page.locator('body')).toContainText(/could not verify/i);
  },
};
