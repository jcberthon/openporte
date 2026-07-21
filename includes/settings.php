<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Null-safe sanitizer for the custom Challenge URL option.
 *
 * When API Mode is "Self-hosted" the Challenge URL input is disabled client-side
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
        "hint" => __('Select the API Mode. Use Self-hosted for the built-in WordPress REST API, or Custom to point to your own ALTCHA-compatible backend.', 'openporte'),
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
      'openporte_settings_text_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "custom" => true,
        "name" => OpenPortePlugin::$option_api_custom_url,
        'disabled' => !$custom_api_mode_active,
        "hint" => __('Only available when API Mode is set to Custom.<br/>The URL is made up of the domain and path to your <strong>custom ALTCHA-compatible backend</strong>, and optionally an API key (it starts with <code>gk_</code>).', 'openporte'),
        // Example URL, deliberately not translatable.
        "placeholder" => 'https://your-backend.com/api/v1/challenge?apiKey=gk_959c...',
        "type" => "url"
      )
    );

    add_settings_field(
      'openporte_settings_secret_field',
      __('Shared secret', 'openporte'),
      'openporte_settings_password_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_secret,
        "description" => __('A secret key used to sign and verify challenges.', 'openporte'),
        "hint" => __('OpenPorte generates a random secret automatically. Change it only if another application needs to use the same secret.<br/>In Custom API Mode, this value <strong>must exactly match</strong> the shared secret (sometimes called the HMAC secret) configured in your backend.', 'openporte'),
        "display_toggle" => true
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
        "description" => __('Hash algorithm for the challenges.', 'openporte'),
        "hint" => __('In Self-hosted API Mode this is the algorithm used to generate and verify the proof-of-work challenges.<br/>In Custom API Mode it must match the algorithm your backend uses — most ALTCHA-compatible backends default to SHA-256.', 'openporte'),
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
        "hint" => __('Select the PoW complexity for the widget: the higher the complexity, the longer visitors (and bots) work to solve the challenge.<br/>Even High is usually solved in under a second on a recent computer; on older phones it can take a few seconds. High is roughly 2–3× the work of Low.', 'openporte'),
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
      __('Widget Customisation', 'openporte'),
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
      __('Display Mode', 'openporte'),
      'openporte_settings_checkbox_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_floating,
        "hint" => __('Choose whether the widget sits inline in the page or floats near the submit button.', 'openporte'),
        "toggle_labels" => array(
          "off" => __('Inline', 'openporte'),
          "on" => __('Floating', 'openporte'),
        ),
      )
    );

    add_settings_field(
      'openporte_settings_delay_field',
      __('Verification Delay', 'openporte'),
      'openporte_settings_checkbox_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_delay,
        // The duration comes from OpenPortePlugin::$delay_ms so the hint cannot
        // drift from what the widget actually does; seconds, not milliseconds,
        // because this is read by site owners rather than developers.
        "hint" => sprintf(
          /* translators: %s is the added delay in seconds, e.g. "0.5" */
          __('Some visitors perceive a slower check as more trustworthy, and this adds about %s seconds for that reason alone. It does not make spam-blocking any stronger — to raise the bar for bots, increase Complexity instead.', 'openporte'),
          number_format_i18n(OpenPortePlugin::$delay_ms / 1000, 1)
        ),
        "toggle_labels" => array(
          "off" => __('Instant', 'openporte'),
          "on" => __('Delayed', 'openporte'),
        ),
      )
    );

    add_settings_field(
      'openporte_settings_hidelogo_field',
      __('Branding', 'openporte'),
      'openporte_settings_checkbox_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidelogo,
        "hint" => __('Hides the small ALTCHA logo shown inside the widget. ALTCHA is free, open-source software — we would appreciate you keeping it visible, but the choice is yours.', 'openporte'),
        "toggle_labels" => array(
          "off" => __('Show', 'openporte'),
          "on" => __('Hide Logo', 'openporte'),
        ),
      )
    );

    add_settings_field(
      'openporte_settings_hidefooter_field',
      // Deliberately empty, and deliberately not translatable: this row is the
      // second half of the "Branding" group above, so WordPress renders it with
      // an empty `th` and the two toggles read as one block. Nothing is missing.
      '',
      'openporte_settings_checkbox_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidefooter,
        "hint" => __('Hides the "Protected by ALTCHA" text shown inside the widget. Same as the logo: keeping it visible helps credit the project this plugin is built on, but it has no effect on how OpenPorte works.', 'openporte'),
        "toggle_labels" => array(
          "off" => __('Show', 'openporte'),
          "on" => __('Hide Footer', 'openporte'),
        ),
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
      OpenPortePlugin::$option_integration_wordpress_register => array(
        'label' => __('Register page', 'openporte'),
        'hint' => __('Enable OpenPorte on the WordPress registration page.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_wordpress_reset_password => array(
        'label' => __('Reset password page', 'openporte'),
        'hint' => __('Enable OpenPorte on the WordPress password reset page.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_wordpress_login => array(
        'label' => __('Login page', 'openporte'),
        'hint' => __('Enable OpenPorte on the WordPress login page.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_wordpress_comments => array(
        'label' => __('Comments', 'openporte'),
        'hint' => __('Enable OpenPorte on the WordPress comments section.', 'openporte'),
      ),
    );
    foreach ($openporte_wordpress_integrations as $openporte_option_name => $openporte_label) {
      register_setting(
        'openporte_options',
        $openporte_option_name,
        array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0 )
      );
      add_settings_field(
        $openporte_option_name . '_field',
        $openporte_label['label'],
        'openporte_settings_checkbox_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
          "name" => $openporte_option_name,
          "hint" => $openporte_label['hint'],
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
        'hint' => __('Enable OpenPorte on CoBlocks forms.', 'openporte'),
        'requires' => 'coblocks',
      ),
      OpenPortePlugin::$option_integration_contact_form_7 => array(
        'label' => __('Contact Form 7', 'openporte'),
        'hint' => __('Enable OpenPorte on Contact Form 7 forms.', 'openporte'),
        'requires' => 'contact-form-7',
      ),
      OpenPortePlugin::$option_integration_elementor => array(
        'label' => __('Elementor Pro Forms', 'openporte'),
        'hint' => __('Enable OpenPorte on Elementor Pro forms.', 'openporte'),
        'requires' => 'elementor',
      ),
      OpenPortePlugin::$option_integration_enfold_theme => array(
        'label' => __('Enfold Theme', 'openporte'),
        'hint' => __('Enable OpenPorte on Enfold theme forms.', 'openporte'),
        'requires' => 'enfold-theme',
        'inactive_hint' => __('Theme not active.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_formidable => array(
        'label' => __('Formidable Forms', 'openporte'),
        'hint' => __('Enable OpenPorte on Formidable Forms forms.', 'openporte'),
        'requires' => 'formidable',
      ),
      OpenPortePlugin::$option_integration_forminator => array(
        'label' => __('Forminator', 'openporte'),
        'hint' => __('Enable OpenPorte on Forminator forms.', 'openporte'),
        'requires' => 'forminator',
      ),
      OpenPortePlugin::$option_integration_gravityforms => array(
        'label' => __('Gravity Forms', 'openporte'),
        'hint' => __('Enable OpenPorte on Gravity Forms forms.', 'openporte'),
        'requires' => 'gravityforms',
      ),
      OpenPortePlugin::$option_integration_html_forms => array(
        'label' => __('HTML Forms', 'openporte'),
        'hint' => __('Enable OpenPorte on HTML Forms forms.', 'openporte'),
        'requires' => 'html-forms',
      ),
      OpenPortePlugin::$option_integration_wpdiscuz => array(
        'label' => __('wpDiscuz', 'openporte'),
        'hint' => __('Enable OpenPorte on wpDiscuz comment forms.', 'openporte'),
        'requires' => 'wpdiscuz',
      ),
      OpenPortePlugin::$option_integration_wpforms => array(
        'label' => __('WPForms', 'openporte'),
        'hint' => __('Enable OpenPorte on WPForms forms.', 'openporte'),
        'requires' => 'wpforms',
      ),
      OpenPortePlugin::$option_integration_woocommerce_register => array(
        'label' => __('WooCommerce register page', 'openporte'),
        'hint' => __('Enable OpenPorte on the WooCommerce register page.', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_woocommerce_reset_password => array(
        'label' => __('WooCommerce reset password page', 'openporte'),
        'hint' => __('Enable OpenPorte on the WooCommerce reset password page.', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_woocommerce_login => array(
        'label' => __('WooCommerce login page', 'openporte'),
        'hint' => __('Enable OpenPorte on the WooCommerce login page.', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_custom => array(
        'label' => __('Custom HTML', 'openporte'),
        'hint' => sprintf(
          /* translators: the placeholder will be replaced with the shortcode */
          __('Or use the %s shortcode anywhere in your HTML.', 'openporte'), '<code>[openporte]</code>',
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
        'openporte_settings_checkbox_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
          "name" => $openporte_option_name,
          "disabled" => !$openporte_available,
          "hint" => $openporte_hint,
        )
      );
    }

    do_action('openporte_settings_integrations');
    do_action_deprecated('altcha_settings_integrations', array(), '1.27.0', 'openporte_settings_integrations');
  }
}
