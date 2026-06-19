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
      array( 'sanitize_callback' => 'sanitize_text_field' )
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
      OpenPortePlugin::$option_blockspam,
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

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_coblocks,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_contact_form_7,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_custom,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_elementor,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_enfold_theme,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_formidable,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_forminator,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_gravityforms,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_woocommerce_login,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_woocommerce_register,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_woocommerce_reset_password,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_html_forms,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_wordpress_comments,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_wordpress_login,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_wordpress_register,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_wordpress_reset_password,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_wpdiscuz,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    register_setting(
      'openporte_options',
      OpenPortePlugin::$option_integration_wpforms,
      array( 'sanitize_callback' => 'sanitize_text_field' )
    );

    // Section
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

    add_settings_field(
      'openporte_settings_challenge_url_field',
      __('Challenge URL', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "custom" => true,
        "name" => OpenPortePlugin::$option_api_custom_url,
        "hint" => __('Configure your custom Challenge URL.', 'openporte'),
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

    add_settings_field(
      'openporte_settings_complexity_field',
      __('Complexity', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_complexity,
        "hint" => __('Select the PoW complexity for the widget.', 'openporte'),
        "options" => array(
          "low" => __('Low', 'openporte'),
          "medium" => __('Medium', 'openporte'),
          "high" => __('High', 'openporte'),
        )
      )
    );

    add_settings_field(
      'openporte_settings_expires_field',
      __('Expiration', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_expires,
        "hint" => __('Select the life-span of the challenge.', 'openporte'),
        "options" => array(
          "3600" => __('1 hour', 'openporte'),
          "14400" => __('4 hours', 'openporte'),
          "0" => __('None', 'openporte'),
        )
      )
    );

    // Section
    add_settings_section(
      'openporte_spamfilter_settings_section',
      __('Spam Filter', 'openporte'),
      'openporte_spam_filter_section_callback',
      'openporte_admin'
    );

    add_settings_field(
      'openporte_settings_blockspam_field',
      __('Block Spam Submissions', 'openporte'),
      'openporte_settings_field_callback',
      'openporte_admin',
      'openporte_spamfilter_settings_section',
      array(
        "spamfilter" => true,
        "name" => OpenPortePlugin::$option_blockspam,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Don\'t allow form submissions if the Spam Filter detects potential spam.', 'openporte'),
        "type" => "checkbox"
      )
    );

    // Section
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

    // Section
    add_settings_section(
      'openporte_integrations_settings_section',
      __('Integrations', 'openporte'),
      'openporte_integrations_section_callback',
      'openporte_admin'
    );

    add_settings_field(
        'openporte_settings_coblocks_integration_field',
        __('CoBlocks', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_coblocks,
            "disabled" => !openporte_plugin_active('coblocks'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_contact_form_7_integration_field',
        __('Contact Form 7', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_contact_form_7,
            "disabled" => !openporte_plugin_active('contact-form-7'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
              "shortcode" => __('Shortcode', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_elementor_integration_field',
        __('Elementor Pro Forms', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_elementor,
            "disabled" => !openporte_plugin_active('elementor'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
      'openporte_settings_enfold_theme_integration_field',
      __('Enfold Theme', 'openporte'),
      'openporte_settings_select_callback',
      'openporte_admin',
      'openporte_integrations_settings_section',
      array(
        "name" => OpenPortePlugin::$option_integration_enfold_theme,
        "disabled" => empty(array_filter(wp_get_themes(), function($theme) { 
          return stripos($theme, 'enfold') !== false;
          })),
        "spamfilter_options" => array(
          "spamfilter",
          "captcha_spamfilter",
        ),
        "options" => array(
          "" => __('Disable', 'openporte'),
          "captcha" => __('Captcha', 'openporte'),
          "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
        ),
      )
    );

    add_settings_field(
        'openporte_settings_formidable_integration_field',
        __('Formidable Forms', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_formidable,
            "disabled" => !openporte_plugin_active('formidable'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_forminator_integration_field',
        __('Forminator', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_forminator,
            "disabled" => !openporte_plugin_active('forminator'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_gravityforms_integration_field',
        __('Gravity Forms', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_gravityforms,
            "disabled" => !openporte_plugin_active('gravityforms'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_html_forms_integration_field',
        __('HTML Forms', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_html_forms,
            "disabled" => !openporte_plugin_active('html-forms'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
              "shortcode" => __('Shortcode', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_wpdiscuz_integration_field',
        __('WPDiscuz', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wpdiscuz,
            "disabled" => !openporte_plugin_active('wpdiscuz'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_wpforms_integration_field',
        __('WP Forms', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wpforms,
            "disabled" => !openporte_plugin_active('wpforms'),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_woocommerce_register_integration_field',
        __('WooCommerce register page', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_woocommerce_register,
            "disabled" => !openporte_plugin_active('woocommerce'),
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_woocommerce_reset_password_integration_field',
        __('WooCommerce reset password page', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_woocommerce_reset_password,
            "disabled" => !openporte_plugin_active('woocommerce'),
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_woocommerce_login_integration_field',
        __('WooCommerce login page', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_woocommerce_login,
            "disabled" => !openporte_plugin_active('woocommerce'),
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_custom_integration_field',
        __('Custom HTML', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_custom,
            "hint" => sprintf(
              /* translators: the placeholder will be replaced with the shortcode */
              __('Use %s shortcode anywhere in your HTML.', 'openporte'), '[openporte]',
            ),
            "spamfilter_options" => array(
              "spamfilter",
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    do_action('openporte_settings_integrations');
    do_action_deprecated('altcha_settings_integrations', array(), '1.27.0', 'openporte_settings_integrations');

    // Section
    add_settings_section(
      'openporte_wordpress_settings_section',
      __('WordPress', 'openporte'),
      'openporte_wordpress_section_callback',
      'openporte_admin'
    );

    add_settings_field(
        'openporte_settings_wordpress_register_integration_field',
        __('Register page', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wordpress_register,
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_wordpress_reset_password_integration_field',
        __('Reset password page', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wordpress_reset_password,
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_wordpress_login_integration_field',
        __('Login page', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wordpress_login,
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );

    add_settings_field(
        'openporte_settings_wordpress_comments_integration_field',
        __('Comments', 'openporte'),
        'openporte_settings_select_callback',
        'openporte_admin',
        'openporte_wordpress_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wordpress_comments,
            "spamfilter_options" => array(
              "captcha_spamfilter",
            ),
            "options" => array(
              "" => __('Disable', 'openporte'),
              "captcha" => __('Captcha', 'openporte'),
              "captcha_spamfilter" => __('Captcha + Spam Filter', 'openporte'),
            ),
        )
    );
  }
}
