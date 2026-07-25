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
      '<label for="' . OpenPortePlugin::$option_api . '">' . __('API Mode', 'openporte') . '</label>',
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
      '<label for="' . OpenPortePlugin::$option_api_custom_url . '">' . __('Challenge URL', 'openporte') . '</label>',
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
      '<label for="' . OpenPortePlugin::$option_secret . '">' . __('Shared secret', 'openporte') . '</label>',
      'openporte_settings_password_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_secret,
        "tooltip" => __('A secret key used to sign and verify challenges.', 'openporte'),
        "hint" => __('OpenPorte generates a random secret automatically. Change it only if another application needs to use the same secret.<br/>In Custom API Mode, this value <strong>must exactly match</strong> the shared secret (sometimes called the HMAC secret) configured in your backend.', 'openporte'),
        "display_toggle" => true
      )
    );

    $openporte_algorithms = OpenPortePlugin::get_allowed_algorithms();
    add_settings_field(
      'openporte_settings_algorithm_field',
      '<label for="' . OpenPortePlugin::$option_algorithm . '" title="' . __('Hash algorithm for the challenges.', 'openporte') . '">' . __('Algorithm', 'openporte') . '</label>',
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_algorithm,
        "tooltip" => __('Hash algorithm for the challenges.', 'openporte'),
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
      '<label for="' . OpenPortePlugin::$option_complexity . '">' . __('Complexity', 'openporte') . '</label>',
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
      '<label for="' . OpenPortePlugin::$option_expires . '">' . __('Expiration', 'openporte') . '</label>',
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
      '<label for="' . OpenPortePlugin::$option_auto . '">' . __('Auto verification', 'openporte') . '</label>',
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
      '<label for="' . OpenPortePlugin::$option_floating . '">' . __('Display Mode', 'openporte') . '</label>',
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_floating,
        "hint" => __('Choose whether the widget sits inline in the page or floats near the submit button.', 'openporte'),
        "options" => array(
          "" => __('Inline', 'openporte'),
          "1" => __('Floating', 'openporte'),
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
        "hint" => __('Some visitors perceive a slower check as more trustworthy, and that is this pause\'s only effect. It does not make spam-blocking any stronger; to raise the bar for bots, increase Complexity instead.', 'openporte'),
        "toggle_label" => sprintf(
          /* translators: %s is the added delay in seconds, e.g. "0.5" */
          __('Add %s second delay', 'openporte'),
          number_format_i18n(OpenPortePlugin::$delay_ms / 1000, 1)
        ),
      )
    );

    add_settings_field(
      'openporte_settings_hidelogo_field',
      __('Acknowledgement', 'openporte'),
      'openporte_settings_checkbox_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidelogo,
        "hint" => __('This applies to the small ALTCHA logo shown inside the widget. ALTCHA is free, open-source software — we would appreciate you keeping it visible, but the choice is yours.', 'openporte'),
        "toggle_label" => __('Hide the ALTCHA logo', 'openporte'),
      )
    );

    add_settings_field(
      'openporte_settings_hidefooter_field',
      // Deliberately empty, and deliberately not translatable: this row is the
      // second half of the "Acknowledgement" group above, so WordPress renders it with
      // an empty `th` and the two toggles read as one block. Nothing is missing.
      '',
      'openporte_settings_checkbox_callback',
      'openporte_admin',
      'openporte_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidefooter,
        "hint" => __('This applies to the text shown inside the widget. Same as the logo: keeping it visible helps credit the project this plugin is built on, but it has no effect on how OpenPorte works.', 'openporte'),
        "toggle_label" => __('Hide the "Protected by ALTCHA" footer', 'openporte'),
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
        'title' => __('Register page', 'openporte'),
        'label' => __('Protect WordPress registration page.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_wordpress_reset_password => array(
        'title' => __('Reset password page', 'openporte'),
        'label' => __('Protect WordPress password reset page.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_wordpress_login => array(
        'title' => __('Login page', 'openporte'),
        'label' => __('Protect WordPress login page.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_wordpress_comments => array(
        'title' => __('Comments', 'openporte'),
        'label' => __('Protect WordPress comments section.', 'openporte'),
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
        $openporte_label['title'],
        'openporte_settings_checkbox_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
          "name" => $openporte_option_name,
          "toggle_label" => $openporte_label['label'],
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
        'title' => __('CoBlocks', 'openporte'),
        'label' => __('Protect CoBlocks forms.', 'openporte'),
        'requires' => 'coblocks',
      ),
      OpenPortePlugin::$option_integration_contact_form_7 => array(
        'title' => __('Contact Form 7', 'openporte'),
        'label' => __('Protect Contact Form 7 forms.', 'openporte'),
        'requires' => 'contact-form-7',
      ),
      OpenPortePlugin::$option_integration_elementor => array(
        'title' => __('Elementor Pro Forms', 'openporte'),
        'label' => __('Protect Elementor Pro forms.', 'openporte'),
        'requires' => 'elementor',
      ),
      OpenPortePlugin::$option_integration_enfold_theme => array(
        'title' => __('Enfold Theme', 'openporte'),
        'label' => __('Protect Enfold theme forms.', 'openporte'),
        'requires' => 'enfold-theme',
        'inactive_hint' => __('Theme not active.', 'openporte'),
      ),
      OpenPortePlugin::$option_integration_formidable => array(
        'title' => __('Formidable Forms', 'openporte'),
        'label' => __('Protect Formidable Forms submissions.', 'openporte'),
        'requires' => 'formidable',
      ),
      OpenPortePlugin::$option_integration_forminator => array(
        'title' => __('Forminator', 'openporte'),
        'label' => __('Protect Forminator forms.', 'openporte'),
        'requires' => 'forminator',
      ),
      OpenPortePlugin::$option_integration_gravityforms => array(
        'title' => __('Gravity Forms', 'openporte'),
        'label' => __('Protect Gravity Forms submissions.', 'openporte'),
        'requires' => 'gravityforms',
      ),
      OpenPortePlugin::$option_integration_html_forms => array(
        'title' => __('HTML Forms', 'openporte'),
        'label' => __('Protect HTML Forms submissions.', 'openporte'),
        'requires' => 'html-forms',
      ),
      OpenPortePlugin::$option_integration_wpdiscuz => array(
        'title' => __('wpDiscuz', 'openporte'),
        'label' => __('Protect wpDiscuz comments section.', 'openporte'),
        'requires' => 'wpdiscuz',
      ),
      OpenPortePlugin::$option_integration_wpforms => array(
        'title' => __('WPForms', 'openporte'),
        'label' => __('Protect WPForms submissions.', 'openporte'),
        'requires' => 'wpforms',
      ),
      OpenPortePlugin::$option_integration_woocommerce_register => array(
        'title' => __('WooCommerce register page', 'openporte'),
        'label' => __('Protect the WooCommerce register page.', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_woocommerce_reset_password => array(
        'title' => __('WooCommerce reset password page', 'openporte'),
        'label' => __('Protect the WooCommerce reset password page.', 'openporte'),
        'requires' => 'woocommerce',
      ),
      OpenPortePlugin::$option_integration_woocommerce_login => array(
        'title' => __('WooCommerce login page', 'openporte'),
        'label' => __('Protect the WooCommerce login page.', 'openporte'),
        'requires' => 'woocommerce',
      ),
      // Deprecated (#62), removal in the next major release. The toggle stays
      // functional through the deprecation window so affected sites can
      // re-enable it while they migrate to the shortcode.
      OpenPortePlugin::$option_integration_custom => array(
        'title' => __('Custom HTML', 'openporte'),
        'label' => __('Configure hand-written widget tags.', 'openporte'),
        'class' => 'openporte-deprecated',
        'hint' => sprintf(
          /* translators: %1$s and %2$s are opening and closing bold tags, %3$s will be replaced with the shortcode */
          __('%1$sDeprecated%2$s, and scheduled for removal in the next major release — place the %3$s shortcode in your content instead.', 'openporte'),
          '<strong>',
          '</strong>',
          '<code>[openporte]</code>',
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
        $openporte_integration['title'],
        'openporte_settings_checkbox_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
          "name" => $openporte_option_name,
          "disabled" => !$openporte_available,
          "toggle_label" => $openporte_integration['label'],
          "hint" => $openporte_hint,
          // WP core copies this onto the row's <tr> (empty = no attribute), so
          // deprecated entries get their warning styling with no logic in the
          // field callback — see tr.openporte-deprecated in public/admin.css.
          "class" => $openporte_integration['class'] ?? '',
        )
      );
    }

    do_action('openporte_settings_integrations');
    do_action_deprecated('altcha_settings_integrations', array(), '1.27.0', 'openporte_settings_integrations');
  }
}
