const { expect } = require('@playwright/test');
const { wpSetOption } = require('../helpers');
const { CUSTOMER_LOGIN, CUSTOMER_PASS, ensureWooCommerce, ensureCustomer } = require('./_woocommerce');

/**
 * WooCommerce login form (native POST; server-side check runs in the
 * `authenticate` filter, see integrations/woocommerce.php).
 *
 * Accepted: WooCommerce redirects back and the my-account shortcode renders
 * the logged-in dashboard ("Log out" navigation). Each test runs in a fresh
 * browser context, so the auth cookie never leaks into the next combo.
 * Rejected: the WP_Error surfaces as a WooCommerce notice.
 */
module.exports = {
  key: 'woocommerce-login',
  option: 'openporte_integration_woocommerce_login',

  ensure() {
    const base = ensureWooCommerce();
    // The register form (and its widget) shares this page — keep the sibling
    // surfaces off so the .first()-based widget helpers hit our form's widget.
    wpSetOption('openporte_integration_woocommerce_register', '0');
    wpSetOption('openporte_integration_woocommerce_reset_password', '0');
    ensureCustomer();
    return base;
  },

  async open(page, ctx) {
    await page.goto(ctx.path);
    await page.locator('form.woocommerce-form-login').waitFor();
  },

  // The marker is unused: a login leaves no content to tag, the assertion is
  // the authenticated state itself.
  async fill(page) {
    await page.locator('#username').fill(CUSTOMER_LOGIN);
    await page.locator('#password').fill(CUSTOMER_PASS);
    // Blur defensively — see the register driver: focus leaving a WooCommerce
    // password field can shift the layout under the submit button mid-click.
    await page.locator('#password').blur();
  },

  async submit(page) {
    await page.locator('form.woocommerce-form-login button[name="login"]').click();
  },

  async expectAccepted(page) {
    await expect(page.locator('body')).toContainText(/log out/i);
  },

  async expectRejected(page) {
    await expect(page.locator('body')).toContainText(/could not verify/i);
  },
};
