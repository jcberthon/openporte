<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (is_admin()) {
  add_action('admin_init', 'altcha_settings_init');

  function altcha_settings_init()
  {
    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_api
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_api_custom_url
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_secret
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_complexity
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_expires
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_hidefooter
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_hidelogo
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_blockspam
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_auto
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_floating
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_delay
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_coblocks
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_contact_form_7
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_custom
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_elementor
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_enfold_theme
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_formidable
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_forminator
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_gravityforms
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_woocommerce_login
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_woocommerce_register
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_woocommerce_reset_password
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_html_forms
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_wordpress_comments
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_wordpress_login
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_wordpress_register
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_wordpress_reset_password
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_wpdiscuz
    );

    register_setting(
      'altcha_options',
      OpenPortePlugin::$option_integration_wpforms
    );

    // Section
    add_settings_section(
      'altcha_general_settings_section',
      __('General', 'openporte'),
      'altcha_general_section_callback',
      'altcha_admin'
    );

    add_settings_field(
      'altcha_settings_api_field',
      __('API Mode', 'openporte'),
      'altcha_settings_select_callback',
      'altcha_admin',
      'altcha_general_settings_section',
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
      'altcha_settings_challenge_url_field',
      __('Challenge URL', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_general_settings_section',
      array(
        "custom" => true,
        "name" => OpenPortePlugin::$option_api_custom_url,
        "hint" => __('Configure your custom Challenge URL.', 'openporte'),
        "type" => "text"
      )
    );

    add_settings_field(
      'altcha_settings_secret_field',
      __('Signing secret', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_general_settings_section',
      array(
        "name" => OpenPortePlugin::$option_secret,
        "hint" => __('Configure your HMAC signing secret.', 'openporte'),
        "type" => "text"
      )
    );

    add_settings_field(
      'altcha_settings_complexity_field',
      __('Complexity', 'openporte'),
      'altcha_settings_select_callback',
      'altcha_admin',
      'altcha_general_settings_section',
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
      'altcha_settings_expires_field',
      __('Expiration', 'openporte'),
      'altcha_settings_select_callback',
      'altcha_admin',
      'altcha_general_settings_section',
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
      'altcha_spamfilter_settings_section',
      __('Spam Filter', 'openporte'),
      'altcha_spam_filter_section_callback',
      'altcha_admin'
    );

    add_settings_field(
      'altcha_settings_blockspam_field',
      __('Block Spam Submissions', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_spamfilter_settings_section',
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
      'altcha_widget_settings_section',
      __('Widget Customization', 'openporte'),
      'altcha_widget_section_callback',
      'altcha_admin'
    );

    add_settings_field(
      'altcha_settings_auto_field',
      __('Auto verification', 'openporte'),
      'altcha_settings_select_callback',
      'altcha_admin',
      'altcha_widget_settings_section',
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
      'altcha_settings_floating_field',
      __('Floating UI', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_floating,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Enable Floating UI.', 'openporte'),
        "type" => "checkbox"
      )
    );

    add_settings_field(
      'altcha_settings_delay_field',
      __('Delay', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_delay,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Add a delay of 1.5 seconds to verification.', 'openporte'),
        "type" => "checkbox"
      )
    );

    add_settings_field(
      'altcha_settings_hidelogo_field',
      __('Hide logo', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidelogo,
        "description" => __('Yes', 'openporte'),
        "type" => "checkbox"
      )
    );

    add_settings_field(
      'altcha_settings_hidefooter_field',
      __('Hide footer', 'openporte'),
      'altcha_settings_field_callback',
      'altcha_admin',
      'altcha_widget_settings_section',
      array(
        "name" => OpenPortePlugin::$option_hidefooter,
        "description" => __('Yes', 'openporte'),
        "hint" => __('Hide Powered by ALTCHA.', 'openporte'),
        "type" => "checkbox"
      )
    );

    // Section
    add_settings_section(
      'altcha_integrations_settings_section',
      __('Integrations', 'openporte'),
      'altcha_integrations_section_callback',
      'altcha_admin'
    );

    add_settings_field(
        'altcha_settings_coblocks_integration_field',
        __('CoBlocks', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_coblocks,
            "disabled" => !altcha_plugin_active('coblocks'),
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
        'altcha_settings_contact_form_7_integration_field',
        __('Contact Form 7', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_contact_form_7,
            "disabled" => !altcha_plugin_active('contact-form-7'),
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
        'altcha_settings_elementor_integration_field',
        __('Elementor Pro Forms', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_elementor,
            "disabled" => !altcha_plugin_active('elementor'),
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
      'altcha_settings_enfold_theme_integration_field',
      __('Enfold Theme', 'openporte'),
      'altcha_settings_select_callback',
      'altcha_admin',
      'altcha_integrations_settings_section',
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
        'altcha_settings_formidable_integration_field',
        __('Formidable Forms', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_formidable,
            "disabled" => !altcha_plugin_active('formidable'),
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
        'altcha_settings_forminator_integration_field',
        __('Forminator', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_forminator,
            "disabled" => !altcha_plugin_active('forminator'),
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
        'altcha_settings_gravityforms_integration_field',
        __('Gravity Forms', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_gravityforms,
            "disabled" => !altcha_plugin_active('gravityforms'),
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
        'altcha_settings_html_forms_integration_field',
        __('HTML Forms', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_html_forms,
            "disabled" => !altcha_plugin_active('html-forms'),
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
        'altcha_settings_wpdiscuz_integration_field',
        __('WPDiscuz', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wpdiscuz,
            "disabled" => !altcha_plugin_active('wpdiscuz'),
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
        'altcha_settings_wpforms_integration_field',
        __('WP Forms', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_wpforms,
            "disabled" => !altcha_plugin_active('wpforms'),
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
        'altcha_settings_woocommerce_register_integration_field',
        __('WooCommerce register page', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_woocommerce_register,
            "disabled" => !altcha_plugin_active('woocommerce'),
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
        'altcha_settings_woocommerce_reset_password_integration_field',
        __('WooCommerce reset password page', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_woocommerce_reset_password,
            "disabled" => !altcha_plugin_active('woocommerce'),
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
        'altcha_settings_woocommerce_login_integration_field',
        __('WooCommerce login page', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_woocommerce_login,
            "disabled" => !altcha_plugin_active('woocommerce'),
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
        'altcha_settings_custom_integration_field',
        __('Custom HTML', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_integrations_settings_section',
        array(
            "name" => OpenPortePlugin::$option_integration_custom,
            "hint" => sprintf(
              /* translators: the placeholder will be replaced with the shortcode */
              __('Use %s shortcode anywhere in your HTML.', 'openporte'), '[altcha]',
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
      'altcha_wordpress_settings_section',
      __('Wordpress', 'openporte'),
      'altcha_wordpress_section_callback',
      'altcha_admin'
    );

    add_settings_field(
        'altcha_settings_wordpress_register_integration_field',
        __('Register page', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_wordpress_settings_section',
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
        'altcha_settings_wordpress_reset_password_integration_field',
        __('Reset password page', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_wordpress_settings_section',
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
        'altcha_settings_wordpress_login_integration_field',
        __('Login page', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_wordpress_settings_section',
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
        'altcha_settings_wordpress_comments_integration_field',
        __('Comments', 'openporte'),
        'altcha_settings_select_callback',
        'altcha_admin',
        'altcha_wordpress_settings_section',
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
