<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: OpenPorte Spam Protection
 * Description: OpenPorte is a free, open-source CAPTCHA alternative that offers robust spam and bot protection without using cookies, ensuring full GDPR compliance by design. A community-maintained fork of the ALTCHA Spam Protection plugin (v1).
 * Author: OpenPorte
 * Author URI: https://github.com/jcberthon/openporte
 * Version: 1.27.0
 * Stable tag: 1.27.0
 * Requires at least: 5.6
 * Requires PHP: 8.0
 * Tested up to: 7.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: altcha-spam-protection
 */

define('OPENPORTE_VERSION', '1.27.0');
define('OPENPORTE_WIDGET_VERSION', '2.2.2');

// Upstream ALTCHA widget attribution: the visible "Protected by ALTCHA" footer
// link. Intentionally points at altcha.org and is out of scope for the rebrand.
define('ALTCHA_WEBSITE', 'https://altcha.org/');

// Deprecated ALTCHA_* aliases kept for backward compatibility with third-party
// code; scheduled for removal in a future release.
define('ALTCHA_VERSION', OPENPORTE_VERSION);
define('ALTCHA_WIDGET_VERSION', OPENPORTE_WIDGET_VERSION);


// Define the base name of the plugin for use in hooks and filters
if ( ! defined( 'WPDOCS_PLUGIN_BASE' ) ) {
        define( 'WPDOCS_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}

// required for is_plugin_active
require_once ABSPATH . 'wp-admin/includes/plugin.php';

require plugin_dir_path(__FILE__) . 'includes/helpers.php';
require plugin_dir_path(__FILE__) . 'includes/core.php';
require plugin_dir_path( __FILE__ ) . './public/widget.php';

require plugin_dir_path( __FILE__ ) . './integrations/coblocks.php';
require plugin_dir_path( __FILE__ ) . './integrations/contact-form-7.php';
require plugin_dir_path( __FILE__ ) . './integrations/custom.php';
require plugin_dir_path( __FILE__ ) . './integrations/elementor.php';
require plugin_dir_path( __FILE__ ) . './integrations/enfold-theme.php';
require plugin_dir_path( __FILE__ ) . './integrations/formidable.php';
require plugin_dir_path( __FILE__ ) . './integrations/forminator.php';
require plugin_dir_path( __FILE__ ) . './integrations/html-forms.php';
require plugin_dir_path( __FILE__ ) . './integrations/gravityforms.php';
require plugin_dir_path( __FILE__ ) . './integrations/wpdiscuz.php';
require plugin_dir_path( __FILE__ ) . './integrations/wpforms.php';
require plugin_dir_path( __FILE__ ) . './integrations/wpmembers.php';
require plugin_dir_path( __FILE__ ) . './integrations/woocommerce.php';
require plugin_dir_path( __FILE__ ) . './integrations/wordpress.php';

OpenPortePlugin::$widget_script_src = plugin_dir_url(__FILE__) . "public/altcha.min.js";
OpenPortePlugin::$widget_style_src = plugin_dir_url(__FILE__) . "public/altcha.css";
OpenPortePlugin::$wp_script_src = plugin_dir_url(__FILE__) . "public/script.js";
OpenPortePlugin::$admin_script_src = plugin_dir_url(__FILE__) . "public/admin.js";
OpenPortePlugin::$admin_css_src = plugin_dir_url(__FILE__) . "public/admin.css";
OpenPortePlugin::$custom_script_src = plugin_dir_url(__FILE__) . "public/custom.js";

register_activation_hook(__FILE__, 'openporte_activate');
register_deactivation_hook(__FILE__, 'openporte_deactivate');

add_action('init', 'openporte_init');
add_action('after_plugin_row_' . plugin_basename(__FILE__), 'openporte_plugin_custom_message');

add_shortcode(
  'altcha',
  function ($attrs) {
    $plugin = OpenPortePlugin::$instance;
    $default = array(
      'language' => null,
      'mode' => $plugin->get_integration_custom(),
    );
    $a = shortcode_atts($default, $attrs);
    return wp_kses($plugin->render_widget($a['mode'], true, $a['language']), OpenPortePlugin::$html_espace_allowed_tags);
  }
);

function openporte_init() {
  load_plugin_textdomain(
    'altcha-spam-protection',
    false,
    dirname( plugin_basename( __FILE__ ) ) . '/languages/'
  );
}

function openporte_activate()
{
  openporte_migrate_legacy_options();

  // Seed defaults only when the option is absent (add_option is a no-op when it
  // already exists), so a freshly migrated or a pre-existing configuration is
  // preserved across (re)activation. In particular the signing secret must not
  // be regenerated, or previously issued challenges would stop verifying.
  add_option(OpenPortePlugin::$option_api, 'selfhosted');
  add_option(OpenPortePlugin::$option_api_custom_url, '');
  add_option(OpenPortePlugin::$option_expires, '3600');
  add_option(OpenPortePlugin::$option_secret, OpenPortePlugin::$instance->random_secret());
  add_option(OpenPortePlugin::$option_hidefooter, true);
  add_option(OpenPortePlugin::$option_integration_custom, 'captcha');
}

/**
 * Copy any legacy ALTCHA (altcha_*) option values into the OpenPorte
 * (openporte_*) namespace on activation.
 *
 * Copy-only and guarded: an existing openporte_* value is never overwritten,
 * and the original altcha_* options are left in place so a user can roll back
 * to the original ALTCHA v1 plugin without losing their configuration.
 */
function openporte_migrate_legacy_options()
{
  $option_keys = array(
    OpenPortePlugin::$option_api,
    OpenPortePlugin::$option_api_custom_url,
    OpenPortePlugin::$option_secret,
    OpenPortePlugin::$option_complexity,
    OpenPortePlugin::$option_expires,
    OpenPortePlugin::$option_blockspam,
    OpenPortePlugin::$option_auto,
    OpenPortePlugin::$option_floating,
    OpenPortePlugin::$option_delay,
    OpenPortePlugin::$option_hidefooter,
    OpenPortePlugin::$option_hidelogo,
    OpenPortePlugin::$option_integration_coblocks,
    OpenPortePlugin::$option_integration_contact_form_7,
    OpenPortePlugin::$option_integration_custom,
    OpenPortePlugin::$option_integration_elementor,
    OpenPortePlugin::$option_integration_formidable,
    OpenPortePlugin::$option_integration_forminator,
    OpenPortePlugin::$option_integration_gravityforms,
    OpenPortePlugin::$option_integration_woocommerce_login,
    OpenPortePlugin::$option_integration_woocommerce_register,
    OpenPortePlugin::$option_integration_woocommerce_reset_password,
    OpenPortePlugin::$option_integration_html_forms,
    OpenPortePlugin::$option_integration_wordpress_login,
    OpenPortePlugin::$option_integration_wordpress_register,
    OpenPortePlugin::$option_integration_wordpress_reset_password,
    OpenPortePlugin::$option_integration_wordpress_comments,
    OpenPortePlugin::$option_integration_wpdiscuz,
    OpenPortePlugin::$option_integration_wpforms,
    OpenPortePlugin::$option_integration_enfold_theme,
  );

  foreach ($option_keys as $new_key) {
    $legacy_key = 'altcha_' . substr($new_key, strlen('openporte_'));
    $legacy_value = get_option($legacy_key, null);
    if ($legacy_value !== null && get_option($new_key, null) === null) {
      update_option($new_key, $legacy_value);
    }
  }
}

function openporte_deactivate()
{
}

function openporte_plugin_custom_message()
{

}
