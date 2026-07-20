<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom-mode endpoint health check.
 *
 * When API mode is "Custom", fetch one challenge from the configured
 * Challenge URL while the OpenPorte settings page loads, and surface the
 * result as an admin notice: unreachable endpoint, non-ALTCHA response,
 * unsupported algorithm, signing-secret mismatch, or a backend algorithm
 * that differs from the configured one.
 *
 * A single request is enough to validate all three settings because an
 * ALTCHA challenge declares its own algorithm, and
 * hmac(algorithm, challenge, secret) must equal the served signature.
 * There is no need to probe each supported algorithm separately.
 *
 * The result is cached in a short transient keyed on (url, secret,
 * algorithm) so reloading the settings page does not hammer the backend;
 * changing any of the three settings busts the key, so the check re-runs
 * right after a save. Each uncached check consumes one challenge on the
 * backend (it will show up as issued-but-never-verified in e.g. GateCHA's
 * statistics dashboard).
 */

add_action('current_screen', 'openporte_maybe_check_custom_endpoint');

function openporte_maybe_check_custom_endpoint($screen)
{
  // Only on OpenPorte's own settings screen (Settings > OpenPorte Anti-spam).
  if (!isset($screen->id) || $screen->id !== 'settings_page_openporte_admin') {
    return;
  }
  $plugin = OpenPortePlugin::$instance;
  if ($plugin->get_api() !== 'custom') {
    return;
  }
  $result = openporte_check_custom_endpoint(
    $plugin->get_api_custom_url(),
    $plugin->get_secret(),
    $plugin->get_algorithm()
  );
  add_action('admin_notices', function () use ($result) {
    printf(
      '<div class="notice notice-%1$s is-dismissible"><p><strong>%2$s</strong> %3$s</p></div>',
      esc_attr($result['level']),
      esc_html__('OpenPorte endpoint check:', 'openporte'),
      esc_html($result['message'])
    );
  });
}

/**
 * Fetch one challenge from the custom endpoint and evaluate it.
 *
 * @param string $url                  Configured custom Challenge URL.
 * @param string $secret               Configured signing secret.
 * @param string $configured_algorithm Configured algorithm (e.g. 'SHA-256').
 * @return array{level:string,message:string} Notice level + message.
 */
function openporte_check_custom_endpoint($url, $secret, $configured_algorithm)
{
  if (empty($url)) {
    return array(
      'level' => 'warning',
      'message' => __('API mode is Custom but no Challenge URL is configured.', 'openporte'),
    );
  }
  $cache_key = 'openporte_endpoint_check_' . md5($url . '|' . $secret . '|' . $configured_algorithm);
  $cached = get_transient($cache_key);
  if (is_array($cached)) {
    return $cached;
  }
  // wp_remote_get, not wp_safe_remote_get: the URL is set by an administrator
  // (manage_options), and private-network backends (a LAN host or NAS running
  // e.g. GateCHA) are a primary use case that the "safe" variant would block.
  $response = wp_remote_get($url, array(
    'timeout' => 5,
    'headers' => array('accept' => 'application/json'),
  ));
  $result = openporte_evaluate_challenge_response($response, $secret, $configured_algorithm);
  set_transient($cache_key, $result, 2 * MINUTE_IN_SECONDS);
  return $result;
}

/**
 * Evaluate a challenge-endpoint HTTP response against the configured
 * secret and algorithm. Split from the fetch so it stays testable.
 *
 * @param array|WP_Error $response             Return value of wp_remote_get().
 * @param string         $secret               Configured signing secret.
 * @param string         $configured_algorithm Configured algorithm.
 * @return array{level:string,message:string}
 */
function openporte_evaluate_challenge_response($response, $secret, $configured_algorithm)
{
  if (is_wp_error($response)) {
    return array(
      'level' => 'error',
      'message' => sprintf(
        /* translators: %s is the connection error message */
        __('The Challenge URL is unreachable: %s', 'openporte'),
        $response->get_error_message()
      ),
    );
  }
  $status = wp_remote_retrieve_response_code($response);
  if ($status !== 200) {
    return array(
      'level' => 'error',
      'message' => sprintf(
        /* translators: %d is the HTTP status code returned by the backend */
        __('The Challenge URL responded with HTTP %d instead of a challenge. Check the URL (including any apiKey parameter) and the backend\'s domain/origin restrictions.', 'openporte'),
        $status
      ),
    );
  }
  $challenge = json_decode(wp_remote_retrieve_body($response), true);
  if (!is_array($challenge)
    || !isset($challenge['algorithm'], $challenge['challenge'], $challenge['salt'], $challenge['signature'])) {
    return array(
      'level' => 'error',
      'message' => __('The Challenge URL did not return an ALTCHA challenge (JSON with algorithm, challenge, salt and signature fields expected).', 'openporte'),
    );
  }
  $served_algorithm = (string) $challenge['algorithm'];
  if (!in_array($served_algorithm, OpenPortePlugin::get_allowed_algorithms(), true)) {
    return array(
      'level' => 'error',
      'message' => sprintf(
        /* translators: %s is the algorithm name reported by the backend */
        __('The backend issues challenges with the unsupported algorithm "%s".', 'openporte'),
        $served_algorithm
      ),
    );
  }
  // The signature is hmac(algorithm, challenge, secret) per the ALTCHA spec;
  // recomputing it with the backend's own declared algorithm isolates the
  // secret check from any algorithm mismatch handled below.
  $calculated = hash_hmac(
    OpenPortePlugin::hash_ident($served_algorithm),
    (string) $challenge['challenge'],
    $secret
  );
  if (!hash_equals($calculated, (string) $challenge['signature'])) {
    return array(
      'level' => 'error',
      'message' => __('The shared secret does not match the backend\'s HMAC secret — every form submission would fail verification. Copy the backend\'s HMAC secret into the Signing secret field.', 'openporte'),
    );
  }
  if ($served_algorithm !== $configured_algorithm) {
    return array(
      'level' => 'warning',
      'message' => sprintf(
        /* translators: %1$s is the algorithm used by the backend, %2$s the algorithm configured in OpenPorte */
        __('The backend issues %1$s challenges but the Algorithm setting is %2$s — form submissions would fail verification. Set Algorithm to %1$s.', 'openporte'),
        $served_algorithm,
        $configured_algorithm
      ),
    );
  }
  return array(
    'level' => 'success',
    'message' => sprintf(
      /* translators: %s is the algorithm confirmed with the backend */
      __('The Challenge URL responds with a valid %s challenge and the shared secret matches.', 'openporte'),
      $served_algorithm
    ),
  );
}
