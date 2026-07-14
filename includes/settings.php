<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Null-safe sanitizer for the custom Challenge URL option.
 *
 * When API mode is "Self-hosted" the Challenge URL input is disabled client-side
 * (see public/admin.js), so the browser does not submit it. WordPress then passes
 * null to the sanitize callback. Calling esc_url_raw(null) would hand null to
 * ltrim() and raise a PHP 8.1+ "Passing null to parameter #1" deprecation, whose
 * output breaks the post-save redirect ("headers already sent" / blank page).
 *
 * For a missing field (null) we keep the previously stored URL, so it survives a
 * save made while in Self-hosted mode and is still there when the user switches
 * back to Custom. A submitted empty string is honoured and clears the value.
 *
 * @param string|null $value Raw submitted value, or null when the field was disabled.
 * @return string Sanitized URL.
 */
function openporte_sanitize_challenge_url( $value ) {
  if ( null === $value ) {
    return (string) get_option( OpenPortePlugin::$option_api_custom_url, '' );
  }
  return esc_url_raw( (string) $value );
}

/**
 * Sanitize the Expiration setting.
 *
 * The field is a preset <select> (values in seconds) plus a "Custom" choice
 * backed by a companion number input (form field openporte_expires_custom —
 * not a registered option, see openporte_settings_expires_callback). When
 * "Custom" is selected the <select> submits the literal string 'custom' and
 * the real value comes from the number input. Allowed range: 0–14400 seconds,
 * where 0 means no expiry (None) and 14400 (4 hours) is the historical maximum.
 */
function openporte_sanitize_expires( $value ) {
  if ( 'custom' === $value && isset( $_POST['openporte_expires_custom'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp-admin/options.php verifies the settings nonce before sanitize callbacks run; absint() below is the sanitizer.
    $value = wp_unslash( $_POST['openporte_expires_custom'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
  }
  return min( absint( $value ), 14400 );
}

/**
 * Only the ALTCHA-standard hash algorithms are accepted; anything else falls
 * back to SHA-256, mirroring OpenPortePlugin::get_algorithm().
 */
function openporte_sanitize_algorithm( $value ) {
  return in_array( $value, OpenPortePlugin::get_allowed_algorithms(), true ) ? $value : 'SHA-256';
}

if (is_admin()) {
  add_action('admin_init', 'openporte_settings_init');

  function openporte_settings_init()
  {
    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_api,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_api_custom_url,
      array( 'sanitize_callback' => 'openporte_sanitize_challenge_url' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_secret,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_complexity,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_expires,
      array( 'sanitize_callback' => 'openporte_sanitize_expires' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_algorithm,
      array( 'sanitize_callback' => 'openporte_sanitize_algorithm' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_hidefooter,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_hidelogo,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_auto,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_floating,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_delay,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    /*
     * ================ Section - General ================
    */
    add_settings_section(
      'openporte_general_settings_section',
      __('General', 'openporte'),
      'openporte_general_section_callback',
      'openporte_admin'
    );

    add_settings_field(
      'openporte_settings_api_field',
      __('API Mode', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_api,
        "hint" => __('Select the API mode. Use Self-hosted for the built-in WordPress REST API, or Custom to point to your own ALTCHA-compatible backend.', 'openporte'),
        "options" => array(
          "selfhosted" => __('Self-hosted', 'openporte'),
          "custom" => __('Custom', 'openporte'),
        )
      )
    );

    $custom_api_mode_active = (get_option(OpenPortePlugin::$option_api, 'selfhosted') === 'custom');

    add_settings_field(
      'openporte_settings_challenge_url_field',
      __('Challenge URL', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "custom" => true,
        "name" => OpenPortePlugin::$option_api_custom_url,
        'disabled' => !$custom_api_mode_active,
        "hint" => $custom_api_mode_active ? __('Configure your custom Challenge URL.', 'openporte') : __('Disabled in Self-hosted mode.', 'openporte'),
        "type" => "text"
      )
    );

    add_settings_field(
      'openporte_settings_secret_field',
      __('Signing secret', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_secret,
        "hint" => __('Configure your HMAC signing secret.', 'openporte'),
        "type" => "text"
      )
    );

    $openporte_algorithms = OpenPortePlugin::get_allowed_algorithms();
    add_settings_field(
      'openporte_settings_algorithm_field',
      __('Algorithm', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_algorithm,
        "hint" => __('Hash algorithm for the challenges. In Self-hosted mode this is the algorithm used to generate and verify the proof-of-work challenges. In Custom mode it must match the algorithm your backend uses — most ALTCHA-compatible backends default to SHA-256.', 'openporte'),
        // Algorithm identifiers are proper nouns — not translatable.
        "options" => array_combine($openporte_algorithms, $openporte_algorithms),
      )
    );

    // The select options follow the complexity matrix (one authoritative
    // definition in core.php, filterable via openporte_complexity_matrix);
    // levels added through the filter fall back to their raw key as label.
    $openporte_complexity_labels = array(
      "low" => __('Low', 'openporte'),
      "medium" => __('Medium', 'openporte'),
      "high" => __('High', 'openporte'),
    );
    $openporte_complexity_options = array();
    foreach (array_keys(OpenPortePlugin::get_complexity_matrix()) as $openporte_level) {
      $openporte_complexity_options[$openporte_level] = isset($openporte_complexity_labels[$openporte_level])
        ? $openporte_complexity_labels[$openporte_level]
        : ucfirst($openporte_level);
    }
    add_settings_field(
      'openporte_settings_complexity_field',
      __('Complexity', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_complexity,
        "hint" => __('Select the PoW complexity for the widget: the higher the complexity, the longer visitors (and bots) work to solve the challenge.', 'openporte'),
        "options" => $openporte_complexity_options,
      )
    );

    add_settings_field(
      'openporte_settings_expires_field',
      __('Expiration', 'openporte'),
      'openporte_settings_expires_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_expires,
        "hint" => __('Life-span of a challenge. Custom accepts 0 to 14400 seconds, where 0 means no expiry (None) and 14400 is 4 hours.', 'openporte'),
      )
    );

    /*
     * ================ Section - Widget Customisation ================
    */
    add_settings_section(
      'openporte_widget_settings_section',
      __('Widget Customization', 'openporte'),
      'openporte_widget_section_callback',
      'openporte_admin'
    );

    add_settings_field(
      'openporte_settings_auto_field',
      __('Auto verification', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_auto,
        "hint" => __('Select auto-verification behaviour.', 'openporte'),
        "options" => array(
          "" => __('Disabled', 'openporte'),
          "onload" => __('On page load', 'openporte'),
          "onfocus" => __('On form focus', 'openporte'),
          "onsubmit" => __('On form submit', 'openporte'),
        )
      )
    );

    add_settings_field(
      'openporte_settings_floating_field',
      __('Floating UI', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_floating,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Enable Floating UI.', 'openporte'),
        "type" => "checkbox"
      )
    );

    add_settings_field(
      'openporte_settings_delay_field',
      __('Delay', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_delay,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Add a delay of 1.5 seconds to verification.', 'openporte'),
        "type" => "checkbox"
      )
    );

    add_settings_field(
      'openporte_settings_hidelogo_field',
      __('Hide logo', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidelogo,
        "description" => __('Yes', 'openporte'),
        "type" => "checkbox"
      )
    );

    add_settings_field(
      'openporte_settings_hidefooter_field',
      __('Hide footer', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidefooter,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Hide Powered by ALTCHA.', 'openporte'),
        "type" => "checkbox"
      )
    );

    /*
     * ================ Section - WordPress Built-in ================
    */
    add_settings_section(
      'openporte_wordpress_settings_section',
      __('WordPress', 'openporte'),
      'openporte_wordpress_section_callback',
      'openporte_admin'
    );

    // One checkbox per core-WordPress surface; registration and field share a
    // single loop (see the Integrations section below for the pattern source).
    $openporte_wordpress_integrations = array(
      OpenPortePlugin::$option_integration_wordpress_register => __('Register page', 'openporte'),
      OpenPortePlugin::$option_integration_wordpress_reset_password => __('Reset password page', 'openporte'),
      OpenPortePlugin::$option_integration_wordpress_login => __('Login page', 'openporte'),
      OpenPortePlugin::$option_integration_wordpress_comments => __('Comments', 'openporte'),
    );
    foreach ($openporte_wordpress_integrations as $openporte_option_name => $openporte_label) {
      register_setting(
        'openporte_options',
        $openporte_option_name,
        array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0 )
      );
      add_settings_field(
        $openporte_option_name . '_field',
        $openporte_label,
        'openporte_settings_field_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
          "name" => $openporte_option_name,
          "type" => "checkbox"
        )
      );
    }

    /*
     * ================ Section - Integrations ================
    */
    add_settings_section(
      'openporte_integrations_settings_section',
      __('Integrations', 'openporte'),
      'openporte_integrations_section_callback',
      'openporte_admin'
    );

    /*
     * One entry per third-party integration; the loop below registers the
     * option and its checkbox field in one place. 'requires' is the
     * openporte_plugin_active() name gating availability; omit it for
     * integrations that are always available. This registration-loop pattern
     * is adapted from the GPL-licensed plugin "GateCHA for WordPress" by
     * Upellift99 (includes/class-gatecha-admin.php) — thank you!
     * https://github.com/Upellift99/GateCHA-WordPress
     */
    $openporte_integrations = array(
      OpenPortePlugin::$option_integration_coblocks => array(
        'label' => __('CoBlocks', 'openporte'),
        'requires' => 'coblocks',
      ),
      OpenPortePlugin::$option_integration_contact_form_7 => array(
        'label' => __('Contact Form 7', 'openporte'),
        'requires' => 'contact-form-7',
      ),
      OpenPortePlugin::$option_integration_elementor => array(
        'label' => __('Elementor Pro Forms', 'openporte'),
        'requires' => 'elementor',
      ),
      OpenPortePlugin::$option_integration_enfold_theme => array(
        'label' => __('Enfold Theme', 'openporte'),
        'requires' => 'enfold-theme',
        'inactive_hint' => __('Theme not active.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_formidable => array(
        'label' => __('Formidable Forms', 'openporte'),
        'requires' => 'formidable',
      ),
      OpenPortePlugin::$option_integration_forminator => array(
        'label' => __('Forminator', 'openporte'),
        'requires' => 'forminator',
      ),
      OpenPortePlugin::$option_integration_gravityforms => array(
        'label' => __('Gravity Forms', 'openporte'),
        'requires' => 'gravityforms',
      ),
      OpenPortePlugin::$option_integration_html_forms => array(
        'label' => __('HTML Forms', 'openporte'),
        'requires' => 'html-forms',
      ),
      OpenPortePlugin::$option_integration_wpdiscuz => array(
        'label' => __('WPDiscuz', 'openporte'),
        'requires' => 'wpdiscuz',
      ),
      OpenPortePlugin::$option_integration_wpforms => array(
        'label' => __('WP Forms', 'openporte'),
        'requires' => 'wpforms',
      ),
      OpenPortePlugin::$option_integration_woocommerce_register => array(
        'label' => __('WooCommerce register page', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_woocommerce_reset_password => array(
        'label' => __('WooCommerce reset password page', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_woocommerce_login => array(
        'label' => __('WooCommerce login page', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_custom => array(
        'label' => __('Custom HTML', 'openporte'),
        'hint' => sprintf(
          /* translators: the placeholder will be replaced with the shortcode */
          __('Or use %s shortcode anywhere in your HTML.', 'openporte'), '[openporte]',
        ),
      ),
    );

    foreach ($openporte_integrations as $openporte_option_name => $openporte_integration) {
      register_setting(
        'openporte_options',
        $openporte_option_name,
        array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0 )
      );
      $openporte_available = !isset($openporte_integration['requires'])
        || openporte_plugin_active($openporte_integration['requires']);
      if ($openporte_available) {
        $openporte_hint = isset($openporte_integration['hint']) ? $openporte_integration['hint'] : '';
      } else {
        $openporte_hint = isset($openporte_integration['inactive_hint'])
          ? $openporte_integration['inactive_hint']
          : __('Plugin not active.', 'openporte');
      }
      add_settings_field(
        $openporte_option_name . '_field',
        $openporte_integration['label'],
        'openporte_settings_field_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
          "name" => $openporte_option_name,
          "disabled" => !$openporte_available,
          "hint" => $openporte_hint,
          "type" => "checkbox"
        )
      );
    }

    do_action('openporte_settings_integrations');
    do_action_deprecated('altcha_settings_integrations', array(), '1.27.0', 'openporte_settings_integrations');
  }
}
