<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: OpenPorte Spam Protection
 * Description: OpenPorte is a free, open-source CAPTCHA alternative that offers robust spam and bot protection without using cookies, ensuring full GDPR compliance by design. A community-maintained fork of the ALTCHA Spam Protection plugin (v1).
 * Author: OpenPorte
 * Author URI: https://github.com/jcberthon/openporte
 * Version: 1.27.1
 * Stable tag: 1.27.1
 * Requires at least: 5.6
 * Requires PHP: 8.0
 * Tested up to: 7.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openporte
 * Domain Path: /languages
 */

/*
 * OpenPorte is a fork of ALTCHA Spam Protection. For backward compatibility it
 * still defines the ALTCHA_* constant aliases and the AltchaPlugin class alias,
 * and registers the [altcha] shortcode and altcha/v1 REST route. If the original
 * ALTCHA plugin is also active these collide (PHP "already defined" warnings, a
 * duplicate widget, …). ALTCHA loads first (alphabetically), so detect it here
 * and bail out with a clear message instead of running both at once.
 */
if ( defined( 'ALTCHA_VERSION' ) || function_exists( 'altcha_plugin_active' ) ) {

	function openporte_conflict_message() {
		return sprintf(
			/* translators: %s: link to the OpenPorte plugin page. */
			__( 'OpenPorte is a fork of ALTCHA Spam Protection and cannot run while the original ALTCHA plugin is active — the two share internal code. Please deactivate "ALTCHA Spam Protection" first, then activate OpenPorte. See the %s for details.', 'openporte' ),
			'<a href="https://wordpress.org/plugins/openporte/" target="_blank" rel="noopener noreferrer">OpenPorte plugin page</a>'
		);
	}

	// Block activation with a readable message instead of a fatal error.
	register_activation_hook( __FILE__, function () {
		wp_die(
			wp_kses_post( openporte_conflict_message() ),
			esc_html__( 'OpenPorte cannot be activated', 'openporte' ),
			array( 'back_link' => true )
		);
	} );

	// Belt and braces: if OpenPorte ends up active alongside ALTCHA, show a notice.
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p>' . wp_kses_post( openporte_conflict_message() ) . '</p></div>';
	} );

	return;
}

define('OPENPORTE_VERSION', '1.27.1');
define('OPENPORTE_WIDGET_VERSION', '2.2.2');

// Upstream ALTCHA widget attribution: the visible "Protected by ALTCHA" footer
// link. Intentionally points at altcha.org and is out of scope for the rebrand.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Upstream-compat constant, intentionally kept.
define('ALTCHA_WEBSITE', 'https://altcha.org/');

// Deprecated ALTCHA_* aliases kept for backward compatibility with third-party
// code; scheduled for removal in a future release.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Documented deprecated back-compat alias.
define('ALTCHA_VERSION', OPENPORTE_VERSION);
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Documented deprecated back-compat alias.
define('ALTCHA_WIDGET_VERSION', OPENPORTE_WIDGET_VERSION);


// Define the base name of the plugin for use in hooks and filters
if ( ! defined( 'OPENPORTE_PLUGIN_BASE' ) ) {
        define( 'OPENPORTE_PLUGIN_BASE', plugin_basename( __FILE__ ) );
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

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- These are static properties of the prefixed OpenPortePlugin class, not global variables.
OpenPortePlugin::$widget_script_src = plugin_dir_url(__FILE__) . "public/altcha.min.js";
OpenPortePlugin::$widget_style_src = plugin_dir_url(__FILE__) . "public/altcha.css";
OpenPortePlugin::$wp_script_src = plugin_dir_url(__FILE__) . "public/script.js";
OpenPortePlugin::$admin_script_src = plugin_dir_url(__FILE__) . "public/admin.js";
OpenPortePlugin::$admin_css_src = plugin_dir_url(__FILE__) . "public/admin.css";
OpenPortePlugin::$custom_script_src = plugin_dir_url(__FILE__) . "public/custom.js";
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

register_activation_hook(__FILE__, 'openporte_activate');
register_deactivation_hook(__FILE__, 'openporte_deactivate');

add_action('after_plugin_row_' . plugin_basename(__FILE__), 'openporte_plugin_custom_message');

$openporte_shortcode = function ($attrs) {
  $plugin = OpenPortePlugin::$instance;
  $default = array(
    'language' => null,
    'mode' => $plugin->get_integration_custom(),
  );
  $a = shortcode_atts($default, $attrs);
  return wp_kses($plugin->render_widget($a['mode'], true, $a['language']), OpenPortePlugin::$html_espace_allowed_tags);
};
add_shortcode('openporte', $openporte_shortcode);
// Deprecated [altcha] alias kept for back-compat; remove in a future release.
add_shortcode('altcha', $openporte_shortcode);

// Note: we intentionally do NOT call load_plugin_textdomain(). Since WordPress
// 4.6 (and we require 5.6+), translations are loaded automatically via core's
// just-in-time mechanism — both translate.wordpress.org language packs and the
// .mo files bundled in ./languages/ (the latter auto-discovered on modern WP).
// See https://make.wordpress.org/core/2024/10/21/i18n-improvements-6-7/.

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
