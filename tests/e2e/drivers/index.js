const comments = require('./comments');
const contactForm7 = require('./contact-form-7');
const wpdiscuz = require('./wpdiscuz');
const wpforms = require('./wpforms');
const woocommerceLogin = require('./woocommerce-login');
const woocommerceRegister = require('./woocommerce-register');

const ALL = [comments, contactForm7, wpdiscuz, wpforms, woocommerceLogin, woocommerceRegister];

// Filter with e.g. MATRIX_DRIVERS=wpdiscuz or MATRIX_DRIVERS=contact-form-7,wordpress-comments
const filter = (process.env.MATRIX_DRIVERS || '')
  .split(',')
  .map((s) => s.trim())
  .filter(Boolean);

module.exports = filter.length ? ALL.filter((d) => filter.includes(d.key)) : ALL;
