const { wp } = require('../helpers');

/**
 * Shared fixtures for the two WooCommerce drivers (not a driver itself —
 * drivers/index.js only loads what it lists explicitly).
 *
 * Both drivers work on one page holding [woocommerce_my_account]: logged out
 * it renders the login form, plus the register form once registration is
 * enabled. Values below are deliberately space-free (see the wp() quoting
 * caveat in ../helpers.js); the register password never crosses the shell
 * boundary, so it lives in the register driver instead.
 */

const CUSTOMER_LOGIN = 'e2e-customer';
const CUSTOMER_PASS = 'E2e-Fixture-Passw0rd-2026';

/** Activate WooCommerce and return the my-account fixture page path. */
function ensureWooCommerce() {
  wp('plugin activate woocommerce');
  let pageId = wp('post list --post_type=page --name=wc-account --field=ID');
  if (!pageId) {
    pageId = wp(
      'post create --post_type=page --post_title=WC-Account --post_status=publish --post_content=[woocommerce_my_account] --porcelain'
    );
  }
  return { path: `/?page_id=${pageId}` };
}

/** Create the login fixture customer if absent; returns its user ID ('' on failure). */
function ensureCustomer() {
  try {
    return wp(`user get ${CUSTOMER_LOGIN} --field=ID`);
  } catch (e) {
    return wp(
      `user create ${CUSTOMER_LOGIN} ${CUSTOMER_LOGIN}@example.com --role=customer --user_pass=${CUSTOMER_PASS} --porcelain`
    );
  }
}

/** The fixture customer's ID if it exists, '' otherwise (never creates). */
function customerIdIfPresent() {
  try {
    return wp(`user get ${CUSTOMER_LOGIN} --field=ID`);
  } catch (e) {
    return '';
  }
}

module.exports = {
  CUSTOMER_LOGIN,
  CUSTOMER_PASS,
  ensureWooCommerce,
  ensureCustomer,
  customerIdIfPresent,
};
