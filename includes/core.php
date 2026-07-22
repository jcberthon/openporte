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
  public static $option_version = "openporte_version";
  public static $option_api = "openporte_api";
  public static $option_api_custom_url = "openporte_api_custom_url";
  public static $option_secret = "openporte_secret";
  public static $option_complexity = "openporte_complexity";
  public static $option_expires = "openporte_expires";
  public static $option_algorithm = "openporte_algorithm";
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

  /**
   * Extra pause, in milliseconds, added to verification when the Verification
   * Delay setting is on. Single source of truth: the widget attribute and the
   * settings-page hint are both derived from it.
   *
   * This is a perception knob, not a security control. ALTCHA v2's `delay` is a
   * flat sleep stacked on top of the proof-of-work, not a minimum-duration floor
   * (that is a v3 attribute the bundled widget does not have), so it costs an
   * attacker nothing — a bot simply runs more threads while others wait. It is
   * offered because a visible pause reads as work being done, which raises
   * perceived trustworthiness. Complexity is the setting that actually raises
   * the cost for bots.
   *
   * 500 is a deliberate guess, not a measured or tuned value.
   *
   * @since 1.28.0
   * @var int
   */
  public static $delay_ms = 500;

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

  // Formatting tags allowed in settings hints (see admin/options.php).
  // Everything else is stripped.
  public static $hint_allowed_tags = array(
    'br'     => array(),
    'em'     => array(),
    'strong' => array(),
    'code'   => array(),
    'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
  );

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

  public static function get_allowed_algorithms()
  {
    // Mirrors the ALTCHA spec's permitted algorithms. The vendored widget
    // enforces the same set internally (a module-scope constant in
    // public/altcha.min.js, not exposed on its public API — and a browser
    // runtime value would be unreachable from PHP anyway), so this array is
    // our server-side copy of the spec constant. Re-check on widget upgrade;
    // see docs/agents/altcha-upstream.md (upgrade procedure).
    return array('SHA-256', 'SHA-384', 'SHA-512');
  }

  public function get_algorithm()
  {
    $algorithm = get_option(OpenPortePlugin::$option_algorithm);
    // Default to SHA-256 when unset or invalid: every release before 1.28
    // hardcoded it, so upgraded sites keep verifying their in-flight
    // challenges, and custom ALTCHA-compatible backends default to it too.
    // New installs are seeded with SHA-512 on activation.
    return in_array($algorithm, OpenPortePlugin::get_allowed_algorithms(), true)
      ? $algorithm
      : 'SHA-256';
  }

  /**
   * Map an ALTCHA algorithm label to the PHP hash() identifier.
   * 'SHA-256' -> 'sha256', 'SHA-384' -> 'sha384', 'SHA-512' -> 'sha512'.
   */
  public static function hash_ident($algorithm)
  {
    return strtolower(str_replace('-', '', $algorithm));
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

  public function get_auto()
  {
    return trim(get_option(OpenPortePlugin::$option_auto));
  }

  public function get_floating()
  {
    return get_option(OpenPortePlugin::$option_floating);
  }

  public function get_delay()
  {
    return get_option(OpenPortePlugin::$option_delay);
  }

  public function get_integration_coblocks()
  {
    return get_option(OpenPortePlugin::$option_integration_coblocks);
  }

  public function get_integration_contact_form_7()
  {
    return get_option(OpenPortePlugin::$option_integration_contact_form_7);
  }

  /**
   * Returns the "Custom HTML" integration option value.
   *
   * @since 1.28.0 Deprecated — the Custom HTML integration is scheduled for
   *               removal in the next major release; place the [openporte]
   *               shortcode instead.
   * @deprecated 1.28.0
   */
  public function get_integration_custom()
  {
    _deprecated_function(__METHOD__, '1.28.0', 'the [openporte] shortcode');
    return get_option(OpenPortePlugin::$option_integration_custom);
  }

  public function get_integration_elementor()
  {
    return get_option(OpenPortePlugin::$option_integration_elementor);
  }

  public function get_integration_enfold_theme() {
    return get_option(OpenPortePlugin::$option_integration_enfold_theme);
  }

  public function get_integration_formidable()
  {
    return get_option(OpenPortePlugin::$option_integration_formidable);
  }

  public function get_integration_forminator()
  {
    return get_option(OpenPortePlugin::$option_integration_forminator);
  }

  public function get_integration_gravityforms()
  {
    return get_option(OpenPortePlugin::$option_integration_gravityforms);
  }

  public function get_integration_woocommerce_register()
  {
    return get_option(OpenPortePlugin::$option_integration_woocommerce_register);
  }

  public function get_integration_woocommerce_reset_password()
  {
    return get_option(OpenPortePlugin::$option_integration_woocommerce_reset_password);
  }

  public function get_integration_woocommerce_login()
  {
    return get_option(OpenPortePlugin::$option_integration_woocommerce_login);
  }

  public function get_integration_html_forms()
  {
    return get_option(OpenPortePlugin::$option_integration_html_forms);
  }

  public function get_integration_wordpress_register()
  {
    return get_option(OpenPortePlugin::$option_integration_wordpress_register);
  }

  public function get_integration_wordpress_reset_password()
  {
    return get_option(OpenPortePlugin::$option_integration_wordpress_reset_password);
  }

  public function get_integration_wordpress_login()
  {
    return get_option(OpenPortePlugin::$option_integration_wordpress_login);
  }

  public function get_integration_wordpress_comments()
  {
    return get_option(OpenPortePlugin::$option_integration_wordpress_comments);
  }

  public function get_integration_wpdiscuz()
  {
    return get_option(OpenPortePlugin::$option_integration_wpdiscuz);
  }

  public function get_integration_wpforms()
  {
    return get_option(OpenPortePlugin::$option_integration_wpforms);
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

  /**
   * Collects every integration option value into one list.
   *
   * Existed solely to feed has_active_integrations(); dead code together with
   * it since upstream 1.21.0 removed the global script-enqueue gate (see #62).
   * The openporte_integrations filter inside only ever fired through this
   * method, so it goes with it.
   *
   * @since 1.28.0 Deprecated, no replacement — removal in the next major release.
   * @deprecated 1.28.0
   */
  public function get_integrations()
  {
    _deprecated_function(__METHOD__, '1.28.0');
    $integrations = array(
      $this->get_integration_contact_form_7(),
      // Reads the option directly: the deprecated getter would log a second,
      // misleading "use the shortcode" notice for callers of this method.
      get_option(OpenPortePlugin::$option_integration_custom),
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

  /**
   * Whether at least one integration option is enabled.
   *
   * Dead code since upstream 1.21.0: its only caller was the global
   * script-enqueue gate removed in that release (see #62).
   *
   * @since 1.28.0 Deprecated, no replacement — removal in the next major release.
   * @deprecated 1.28.0
   */
  public function has_active_integrations()
  {
    _deprecated_function(__METHOD__, '1.28.0');
    $integrations = $this->get_integrations();

    // Checkbox era: any truthy option value means the integration is enabled
    // (legacy mode strings are normalized to 0/1 on upgrade).
    return array_filter($integrations) !== array();
  }

  public function random_secret()
  {
    // 32 bytes → a 256-bit HMAC key. Only seeds NEW installs: add_option() is a
    // no-op once the option exists, so existing secrets — and the challenges
    // already signed with them — are left untouched.
    return bin2hex(random_bytes(32));
  }

  /**
   * Strictly decode a submitted token into an object, or null if malformed.
   *
   * Tokens are base64(JSON). Decoding defensively here lets the verify methods
   * bail out before reading properties, instead of emitting PHP warnings
   * ("Attempt to read property … on null/false") on every junk submission.
   */
  private function decode_payload($payload)
  {
    if (!is_string($payload) || $payload === '') {
      return null;
    }
    $decoded = base64_decode($payload, true); // strict: reject non-base64 input
    if ($decoded === false) {
      return null;
    }
    $data = json_decode($decoded);
    if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
      return null;
    }
    return $data;
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
    $data = $this->decode_payload($payload);
    if ($data === null) {
      // Malformed token: fail closed (and fire the result hooks) without
      // reaching the property reads in the verify_* methods below.
      do_action('openporte_verify_result', false);
      do_action_deprecated('altcha_verify_result', array(false), '1.27.0', 'openporte_verify_result');

      return false;
    }
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
    $data = $this->decode_payload($payload);
    if ($data === null
      || !isset($data->algorithm, $data->verificationData, $data->signature)) {
      return false;
    }
    // Same configured algorithm as verify_solution(). Preserve the true (raw
    // binary) flag on hash() — removing it breaks all verification.
    $algorithm = $this->get_algorithm();
    $hash_ident = OpenPortePlugin::hash_ident($algorithm);
    $alg_ok = ($data->algorithm === $algorithm);
    $calculated_hash = hash($hash_ident, $data->verificationData, true);
    $calculated_signature = hash_hmac($hash_ident, $calculated_hash, $hmac_key);
    // hash_equals: constant-time comparison so the HMAC can't be recovered via timing.
    $signature_ok = hash_equals($calculated_signature, $data->signature);
    if (!($alg_ok && $signature_ok)) {
      return false;
    }
    // The spam-filter classification is gone (issue #6), but expire/verified
    // are protocol-level validity checks, not spam plumbing. Mirror the ALTCHA
    // reference (verified === true && expire > now): a signed server payload is
    // only valid while unexpired and explicitly verified — without the expire
    // check a once-valid token could be replayed forever. Each check applies
    // only when the backend supplies the field, so a minimal custom backend
    // that omits them keeps working.
    $verification = array();
    parse_str($data->verificationData, $verification);
    if (isset($verification['expire'])) {
      $expire = intval($verification['expire'], 10);
      if ($expire > 0 && $expire < time()) {
        return false;
      }
    }
    if (isset($verification['verified'])) {
      $verified_flag = strtolower((string) $verification['verified']);
      if (in_array($verified_flag, array('', '0', 'false', 'no'), true)) {
        return false;
      }
    }
    return true;
  }

  public function verify_solution($payload, $hmac_key = null)
  {
    if ($hmac_key === null) {
      $hmac_key = $this->get_secret();
    }
    $data = $this->decode_payload($payload);
    if ($data === null
      || !isset($data->algorithm, $data->salt, $data->number, $data->challenge, $data->signature)) {
      return false;
    }
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
    // The payload must use the configured algorithm (see get_algorithm(); in
    // Custom mode this must match the backend, hence the settings hint).
    $algorithm = $this->get_algorithm();
    $hash_ident = OpenPortePlugin::hash_ident($algorithm);
    $alg_ok = ($data->algorithm === $algorithm);
    $calculated_challenge = hash($hash_ident, $data->salt . $data->number);
    $challenge_ok = ($data->challenge === $calculated_challenge);
    $calculated_signature = hash_hmac($hash_ident, $data->challenge, $hmac_key);
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
    $matrix = OpenPortePlugin::get_complexity_matrix();
    // 'low' is the fallback for unknown/legacy stored values.
    $range = isset($matrix[$complexity]) ? $matrix[$complexity] : $matrix['low'];
    $secret_number = random_int($range['min'], $range['max']);
    $algorithm = $this->get_algorithm();
    $hash_ident = OpenPortePlugin::hash_ident($algorithm);
    $challenge = hash($hash_ident, $salt . $secret_number);
    $signature = hash_hmac($hash_ident, $challenge, $hmac_key);
    $response = [
      'algorithm' => $algorithm,
      'challenge' => $challenge,
      'maxnumber' => $range['max'],
      'salt' => $salt,
      'signature' => $signature
    ];
    return $response;
  }

  /**
   * Proof-of-work complexity matrix — the single authoritative definition of
   * the difficulty ranges. The widget brute-forces 0..maxnumber, so 'max'
   * bounds the worst case and the min..max window sets the average work.
   *
   * Worst-case solve times measured on real devices (SHA-512, worker pool,
   * top of each band) — see local/benchmarks/pow-benchmarks-2026-07.md:
   *
   *                                       Low        Medium     High
   *   desktop/laptop, 2019 or newer       0.1–0.8s   0.2–1.2s   0.3–1.7s
   *   phone/tablet, current, Low Power    1.0s       1.6s       2.3s
   *   phone, ~10 years old                1.6s       2.5s       3.7s
   *
   * Device age dominates, not the browser engine — the spread across engines
   * on one machine is far smaller than the spread across device age. The
   * widget sizes its worker pool from navigator.hardwareConcurrency, which is
   * commonly capped for fingerprinting resistance: WebKit returns a fixed 4 on
   * iOS and 8 on macOS whatever the SoC, while Firefox and Brave clamp it
   * under user-adjustable privacy settings. The times above are therefore the
   * capped case — the conservative one, and the one privacy-conscious visitors
   * actually get.
   *
   * Sized for hardware back to ~2016; pre-2015 devices still work but are
   * deliberately not sized for (they land near 2.6 / 4.1 / 5.9s).
   *
   * Filterable so a site can tune difficulty without forking the plugin:
   * add_filter('openporte_complexity_matrix', ...). A 'low' entry must always
   * exist — it is the fallback for unknown/legacy stored values.
   */
  public static function get_complexity_matrix()
  {
    $matrix = array(
      'low'    => array('min' =>  50000, 'max' =>  70000),
      'medium' => array('min' =>  90000, 'max' => 110000),
      'high'   => array('min' => 140000, 'max' => 160000),
    );
    return apply_filters('openporte_complexity_matrix', $matrix);
  }

  public function get_widget_attrs($mode, $language = null, $name = null)
  {
    $challengeurl = $this->get_challengeurl();
    $floating = $this->get_floating();
    $delay = $this->get_delay();
    $hidelogo = $this->get_hidelogo();
    $hidefooter = $this->get_hidefooter();
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
      $attrs['delay'] = (string) OpenPortePlugin::$delay_ms;
    }
    if ($hidelogo) {
      $attrs['hidelogo'] = '1';
    }
    if ($hidefooter) {
      $attrs['hidefooter'] = '1';
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
