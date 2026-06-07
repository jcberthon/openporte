<?php

if (!defined('ABSPATH')) exit;

class OpenPortePlugin
{
  public static $instance;

  public static $language = "";

  public static $widget_script_src = "";

  public static $wp_script_src = "";

  public static $admin_script_src = "";

  public static $admin_css_src = "";

  public static $custom_script_src = "";

  public static $widget_style_src = "";

  public static $version = "0.0.0";

  public static $widget_version = "0.0.0";

  public static $option_api = "openporte_api";

  public static $option_api_custom_url = "openporte_api_custom_url";

  public static $option_secret = "openporte_secret";

  public static $option_complexity = "openporte_complexity";

  public static $option_expires = "openporte_expires";

  public static $option_blockspam = "openporte_blockspam";

  public static $option_auto = "openporte_auto";

  public static $option_floating = "openporte_floating";

  public static $option_delay = "openporte_delay";

  public static $option_hidefooter = "openporte_hidefooter";

  public static $option_hidelogo = "openporte_hidelogo";

  public static $option_integration_coblocks = "openporte_integration_coblocks";

  public static $option_integration_contact_form_7 = "openporte_integration_contact_form_7";

  public static $option_integration_custom = "openporte_integration_custom";

  public static $option_integration_elementor = "openporte_integration_elementor";

  public static $option_integration_formidable = "openporte_integration_formidable";

  public static $option_integration_forminator = "openporte_integration_forminator";

  public static $option_integration_gravityforms = "openporte_integration_gravityforms";

  public static $option_integration_woocommerce_login = "openporte_integration_woocommerce_login";

  public static $option_integration_woocommerce_register = "openporte_integration_woocommerce_register";

  public static $option_integration_woocommerce_reset_password = "openporte_integration_woocommerce_reset_password";

  public static $option_integration_html_forms = "openporte_integration_html_forms";

  public static $option_integration_wordpress_login = "openporte_integration_wordpress_login";

  public static $option_integration_wordpress_register = "openporte_integration_wordpress_register";

  public static $option_integration_wordpress_reset_password = "openporte_integration_wordpress_reset_password";

  public static $option_integration_wordpress_comments = "openporte_integration_wordpress_comments";

  public static $option_integration_wpdiscuz = "openporte_integration_wpdiscuz";

  public static $option_integration_wpforms = "openporte_integration_wpforms";

  public static $option_integration_enfold_theme = "openporte_integration_enfold_theme";

  public static $html_espace_allowed_tags = array(
    'altcha-widget' => array(
      'debug' => array(),
      'challengeurl' => array(),
      'strings' => array(),
      'auto' => array(),
      'floating' => array(),
      'delay' => array(),
      'hidelogo' => array(),
      'hidefooter' => array(),
      'blockspam' => array(),
      'spamfilter' => array(),
      'name' => array(),
    ),
    'div' => array(
      'class' => array(),
      'style' => array(),
    ),
    'input' => array(
      'class' => array(),
      'id' => array(),
      'name' => array(),
      'type' => array(),
      'value' => array(),
      'style' => array(),
    ),
    'noscript' => array(),
  );

  public $spamfilter_result = null;

  public function init()
  {
    OpenPortePlugin::$instance = $this;
    OpenPortePlugin::$language = get_locale();
    if (defined('OPENPORTE_VERSION')) {
      OpenPortePlugin::$version = OPENPORTE_VERSION;
    }
    if (defined('OPENPORTE_WIDGET_VERSION')) {
      OpenPortePlugin::$widget_version = OPENPORTE_WIDGET_VERSION;
    }
  }

  public function get_api()
  {
    return trim(get_option(OpenPortePlugin::$option_api));
  }

  public function get_api_custom_url()
  {
    return trim(get_option(OpenPortePlugin::$option_api_custom_url));
  }

  public function get_complexity()
  {
    return trim(get_option(OpenPortePlugin::$option_complexity));
  }

  public function get_expires()
  {
    return get_option(OpenPortePlugin::$option_expires);
  }

  public function get_secret()
  {
    return trim(get_option(OpenPortePlugin::$option_secret));
  }

  public function get_hidelogo()
  {
    return get_option(OpenPortePlugin::$option_hidelogo);
  }

  public function get_hidefooter()
  {
    return get_option(OpenPortePlugin::$option_hidefooter);
  }

  public function get_blockspam()
  {
    return get_option(OpenPortePlugin::$option_blockspam);
  }

  public function get_auto()
  {
    return trim(get_option(OpenPortePlugin::$option_auto));
  }

  public function get_floating()
  {
    return trim(get_option(OpenPortePlugin::$option_floating));
  }

  public function get_delay()
  {
    return trim(get_option(OpenPortePlugin::$option_delay));
  }

  public function get_integration_coblocks()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_coblocks));
  }

  public function get_integration_contact_form_7()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_contact_form_7));
  }

  public function get_integration_custom()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_custom));
  }

  public function get_integration_elementor()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_elementor));
  }

  public function get_integration_enfold_theme() {
    return trim(get_option(OpenPortePlugin::$option_integration_enfold_theme));
  }

  public function get_integration_formidable()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_formidable));
  }

  public function get_integration_forminator()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_forminator));
  }

  public function get_integration_gravityforms()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_gravityforms));
  }

  public function get_integration_woocommerce_register()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_woocommerce_register));
  }

  public function get_integration_woocommerce_reset_password()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_woocommerce_reset_password));
  }

  public function get_integration_woocommerce_login()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_woocommerce_login));
  }

  public function get_integration_html_forms()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_html_forms));
  }

  public function get_integration_wordpress_register()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_wordpress_register));
  }

  public function get_integration_wordpress_reset_password()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_wordpress_reset_password));
  }

  public function get_integration_wordpress_login()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_wordpress_login));
  }

  public function get_integration_wordpress_comments()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_wordpress_comments));
  }

  public function get_integration_wpdiscuz()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_wpdiscuz));
  }

  public function get_integration_wpforms()
  {
    return trim(get_option(OpenPortePlugin::$option_integration_wpforms));
  }


  public function get_challengeurl()
  {
    $api = $this->get_api();
    if ($api === "custom") {
      $challenge_url = $this->get_api_custom_url();
    } else { /* default to selfhosted */
      $challenge_url = get_rest_url(null, "/openporte/v1/challenge");
    }

    $challenge_url = apply_filters('openporte_challenge_url', $challenge_url);
    // Deprecated alias kept for back-compat; remove in a future release.
    return apply_filters_deprecated('altcha_challenge_url', array($challenge_url), '1.27.0', 'openporte_challenge_url');
  }

  public function get_translations($language = null)
  {
    $originalLanguage = null;

    if ($language !== null) {
      $originalLanguage = get_locale();
      switch_to_locale($language);
    }

    $ALTCHA_WEBSITE = constant('ALTCHA_WEBSITE');
    $translations = array(
      "error" => __('Verification failed. Try again later.', 'openporte'),
      "footer" => sprintf(
        /* translators: %1$s and %2$s are the opening and closing tags for a link (<a> tag) */
        __('Protected by %1$sALTCHA%2$s', 'openporte'),
        '<a href="' . $ALTCHA_WEBSITE . '" target="_blank">',
        "</a>",
      ),
      "label" => __('I\'m not a robot', 'openporte'),
      "verified" => __('Verified', 'openporte'),
      "verifying" => __('Verifying...', 'openporte'),
      "waitAlert" => __('Verifying... please wait.', 'openporte'),
    );

    $translations = apply_filters('openporte_translations', $translations, $language);
    // Deprecated alias kept for back-compat; remove in a future release.
    $translations = apply_filters_deprecated('altcha_translations', array($translations, $language), '1.27.0', 'openporte_translations');

    if ($originalLanguage !== null) {
      switch_to_locale($originalLanguage);
    }

    return $translations;
  }

  public function get_integrations()
  {
    $integrations = array(
      $this->get_integration_contact_form_7(),
      $this->get_integration_custom(),
      $this->get_integration_elementor(),
      $this->get_integration_enfold_theme(),
      $this->get_integration_forminator(),
      $this->get_integration_gravityforms(),
      $this->get_integration_html_forms(),
      $this->get_integration_woocommerce_register(),
      $this->get_integration_woocommerce_login(),
      $this->get_integration_woocommerce_reset_password(),
      $this->get_integration_wordpress_register(),
      $this->get_integration_wordpress_login(),
      $this->get_integration_wordpress_reset_password(),
      $this->get_integration_wordpress_comments(),
      $this->get_integration_wpforms(),
    );

    $integrations = apply_filters('openporte_integrations', $integrations);
    // Deprecated alias kept for back-compat; remove in a future release.
    return apply_filters_deprecated('altcha_integrations', array($integrations), '1.27.0', 'openporte_integrations');
  }

  public function has_active_integrations()
  {
    $integrations = $this->get_integrations();

    return in_array("captcha", $integrations) || in_array("captcha_spamfilter", $integrations) || in_array("shortcode", $integrations);
  }

  public function random_secret()
  {
    return bin2hex(random_bytes(12));
  }

  public function verify($payload, $hmac_key = null)
  {
    if ($hmac_key === null) {
      $hmac_key = $this->get_secret();
    }
    if (empty($payload) || empty($hmac_key)) {
      do_action('openporte_verify_result', false);
      do_action_deprecated('altcha_verify_result', array(false), '1.27.0', 'openporte_verify_result');

      return false;
    }
    $data = json_decode(base64_decode($payload));
    if (isset($data->verificationData)) {
      $result = $this->verify_server_signature($payload, $hmac_key);
    } else {
      $result = $this->verify_solution($payload, $hmac_key);
    }

    do_action('openporte_verify_result', $result);
    do_action_deprecated('altcha_verify_result', array($result), '1.27.0', 'openporte_verify_result');

    return $result;
  }

  public function verify_server_signature($payload, $hmac_key = null)
  {
    if ($hmac_key === null) {
      $hmac_key = $this->get_secret();
    }
    $data = json_decode(base64_decode($payload));
    $alg_ok = ($data->algorithm === 'SHA-256');
    $calculated_hash = hash('sha256', $data->verificationData, true);
    $calculated_signature = hash_hmac('sha256', $calculated_hash, $hmac_key);
    // hash_equals: constant-time comparison so the HMAC can't be recovered via timing.
    $signature_ok = hash_equals($calculated_signature, $data->signature);
    $verified = ($alg_ok && $signature_ok);
    if ($verified) {
      $this->spamfilter_result = array();
      parse_str($data->verificationData, $this->spamfilter_result);
      return $this->spamfilter_result['classification'] !== 'BAD';
    }
    return $verified;
  }

  public function verify_solution($payload, $hmac_key = null)
  {
    if ($hmac_key === null) {
      $hmac_key = $this->get_secret();
    }
    $data = json_decode(base64_decode($payload));
    $salt_url = wp_parse_url($data->salt);
    if (isset($salt_url['query']) && !empty($salt_url['query'])) {
      parse_str($salt_url['query'], $salt_params);
      if (!empty($salt_params['expires'])) {
        $expires = intval($salt_params['expires'], 10);
        if ($expires > 0 && $expires < time()) {
          return false;
        }
      }
    }
    $alg_ok = ($data->algorithm === 'SHA-256');
    $calculated_challenge = hash('sha256', $data->salt . $data->number);
    $challenge_ok = ($data->challenge === $calculated_challenge);
    $calculated_signature = hash_hmac('sha256', $data->challenge, $hmac_key);
    // hash_equals: constant-time comparison so the HMAC can't be recovered via timing.
    $signature_ok = hash_equals($calculated_signature, $data->signature);
    $verified = ($alg_ok && $challenge_ok && $signature_ok);
    return $verified;
  }

  public function generate_challenge($hmac_key = null, $complexity = null, $expires = null)
  {
    if ($hmac_key === null) {
      $hmac_key = $this->get_secret();
    }
    if ($complexity === null) {
      $complexity = $this->get_complexity();
    }
    if ($expires === null) {
      $expires = intval($this->get_expires(), 10);
    }
    $salt = $this->random_secret();
    if ($expires > 0) {
      $salt = $salt . '?' . http_build_query(array(
        'expires' => time() + $expires
      ));
    }
    // Avoid str_ends_with() (PHP 8.0 / WP 5.9 polyfill) to keep compatibility
    // with the declared "Requires at least: 5.6"; a plain substr check is enough.
    if (substr($salt, -1) !== '&') {
      $salt .= '&';
    }
    switch ($complexity) {
      case 'low':
        $min_secret = 100;
        $max_secret = 1000;
        break;
      case 'medium':
        $min_secret = 1000;
        $max_secret = 20000;
        break;
      case 'high':
        $min_secret = 10000;
        $max_secret = 100000;
        break;
      default:
        $min_secret = 100;
        $max_secret = 10000;
    }
    $secret_number = random_int($min_secret, $max_secret);
    $challenge = hash('sha256', $salt . $secret_number);
    $signature = hash_hmac('sha256', $challenge, $hmac_key);
    $response = [
      'algorithm' => 'SHA-256',
      'challenge' => $challenge,
      'maxnumber' => $max_secret,
      'salt' => $salt,
      'signature' => $signature
    ];
    return $response;
  }

  public function get_widget_attrs($mode, $language = null, $name = null)
  {
    $challengeurl = $this->get_challengeurl();
    $api = $this->get_api();
    $floating = $this->get_floating();
    $delay = $this->get_delay();
    $can_hide_branding = $api === 'selfhosted' || $api === 'custom';
    $hidelogo = $can_hide_branding && $this->get_hidelogo();
    $hidefooter = $can_hide_branding && $this->get_hidefooter();
    $blockspam = $this->get_blockspam();
    $auto = $this->get_auto();
    $strings = wp_json_encode($this->get_translations($language));
    $attrs = array(
      'challengeurl' => $challengeurl,
      'strings' => $strings,
    );
    if ($name) {
      $attrs['name'] = $name;
    }
    if ($auto) {
      $attrs['auto'] = $auto;
    }
    if ($floating) {
      $attrs['floating'] = 'auto';
    }
    if ($delay) {
      $attrs['delay'] = '1500';
    }
    if ($hidelogo) {
      $attrs['hidelogo'] = '1';
    }
    if ($hidefooter) {
      $attrs['hidefooter'] = '1';
    }
    if ($blockspam) {
      $attrs['blockspam'] = '1';
    }
    if ($mode === "captcha_spamfilter") {
      $attrs['spamfilter'] = '1';
    }
    $attrs = apply_filters('openporte_widget_attrs', $attrs, $mode, $language, $name);
    // Deprecated alias kept for back-compat; remove in a future release.
    return apply_filters_deprecated('altcha_widget_attrs', array($attrs, $mode, $language, $name), '1.27.0', 'openporte_widget_attrs');
  }

  public function render_widget($mode, $wrap = false, $language = null, $name = null)
  {
    openporte_enqueue_scripts();
    openporte_enqueue_styles();
    $attrs = $this->get_widget_attrs($mode, $language, $name);
    $attributes = join(' ', array_map(function ($key) use ($attrs) {
      if (is_bool($attrs[$key])) {
        return $attrs[$key] ? $key : '';
      }
      return esc_attr($key) . '="' . esc_attr($attrs[$key]) . '"';
    }, array_keys($attrs)));
    $html =
      "<altcha-widget "
      . $attributes
      . "></altcha-widget>"
      . "<noscript>"
      /* translators: Displayed inside a <noscript> block when the visitor's browser has JavaScript disabled; the ALTCHA widget cannot function without it. */
      . "<div class=\"altcha-no-javascript\">" . esc_html__('This form requires JavaScript!', 'openporte') . "</div>"
      . "</noscript>";
    if ($wrap) {
      $html = '<div class="altcha-widget-wrap">' . $html . '</div>';
    }

    $html = apply_filters('openporte_widget_html', $html, $mode, $language, $name);
    // Deprecated alias kept for back-compat; remove in a future release.
    return apply_filters_deprecated('altcha_widget_html', array($html, $mode, $language, $name), '1.27.0', 'openporte_widget_html');
  }

  function remove_private_keys($array, $ignore_fields = array())
  {
    $filtered = array();
    foreach ($array as $key => $value) {
      if (strpos($key, '_') !== 0 && !isset($ignore_fields[$key])) {
        $filtered[$key] = $value;
      }
    }
    return $filtered;
  }

  function sanitize_data($post)
  {
    $data = $this->flatten_post($post);
    foreach ($data as $key => $value) {
      $data[$key] = sanitize_text_field($value);
    }
    return $data;
  }

  function flatten_post($post_data, $prefix = '')
  {
    $result = array();
    foreach ($post_data as $key => $value) {
      if (is_array($value)) {
        if ($prefix == '') {
          $result = $result + $this->flatten_post($value, $prefix . $key);
        } else {
          $result = $result + $this->flatten_post($value, $prefix . '[' . $key . ']');
        }
      } else {
        if ($prefix == '') {
          $result[$prefix . $key . ''] = $value;
        } else {
          $result[$prefix . '[' . $key . ']' . ''] = $value;
        }
      }
    }
    return $result;
  }
}

// Deprecated back-compat alias for third-party code referencing the old class
// name; scheduled for removal in a future release.
class_alias('OpenPortePlugin', 'AltchaPlugin');

if (!isset(OpenPortePlugin::$instance)) {
  $openporte_plugin_instance = new OpenPortePlugin();
  $openporte_plugin_instance->init();
}

require plugin_dir_path(__FILE__) . 'admin.php';
require plugin_dir_path(__FILE__) . 'settings.php';

add_action(
  'rest_api_init',
  function () {
    $route = 'challenge';
    $args  = array(
      'methods'   => WP_REST_Server::READABLE,
      'callback'  => 'openporte_generate_challenge_endpoint',
      'permission_callback' => '__return_true'
    );
    register_rest_route('openporte/v1', $route, $args);
    // Deprecated alias namespace kept for back-compat; remove in a future release.
    register_rest_route('altcha/v1', $route, $args);
  }
);

function openporte_generate_challenge_endpoint()
{
  $resp = new WP_REST_Response(OpenPortePlugin::$instance->generate_challenge());
  $resp->set_headers(array('Cache-Control' => 'no-cache, no-store, max-age=0'));
  return $resp;
}
