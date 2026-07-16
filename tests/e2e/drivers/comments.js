const { expect } = require('@playwright/test');
const { wp, ensureCommentsPost, purgeComments } = require('../helpers');

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
    const postId = ensureCommentsPost();
    return { path: `/?p=${postId}`, postId };
  },

  // Runs before every test: core's 15-s same-IP comment throttle measures
  // against the latest existing comment, so start from zero each time.
  reset(ctx) {
    purgeComments(ctx.postId);
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
