<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
  'wpmem_pre_register_data',
  function () {
    $plugin = OpenPortePlugin::$instance;
    // WP-members uses native wordpress integration and does not have an activation select
    // If this hook is being called and wordpress register is enabled, validate altcha
    $mode = $plugin->get_integration_wordpress_register();
    if (!empty($mode)) {
      $altcha = isset($_POST['openporte_register']) ? trim(sanitize_text_field(wp_unslash($_POST['openporte_register']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
      if ($plugin->verify($altcha) === false) {
        global $wpmem_themsg;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $wpmem_themsg is WP-Members' own global, used to surface the error message.
        $wpmem_themsg = esc_html__('Registration failed. Please try again later.', 'openporte');
      }
    }
  },
  10,
  0
);