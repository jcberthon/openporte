<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
  'woocommerce_register_form',
  function () {
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_woocommerce_register();
    if (!empty($mode)) {
      openporte_woocommerce_comments_render_widget($mode, 'openporte_register');
    }
  },
  10,
  0
);

add_action(
  'woocommerce_register_post',
  function ($user_login, $user_email, $errors) {
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_woocommerce_register();
    if (!empty($mode)) {
      $altcha = isset($_POST['openporte_register']) ? trim(sanitize_text_field(wp_unslash($_POST['openporte_register']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
      if ($plugin->verify($altcha) === false) {
        return $errors->add(
          'openporte_error_message',
          esc_html__('Could not verify you are not a robot.', 'openporte')
        );
      }
    }
    return $errors;
  },
  10,
  3
);

add_action(
  'woocommerce_login_form',
  function () {
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_woocommerce_login();
    if (!empty($mode)) {
      openporte_woocommerce_comments_render_widget($mode);
    }
  },
  10,
  0
);

add_filter(
  'authenticate',
  function ($user) {
    if ($user instanceof WP_Error) {
      return $user;
    }
    if(defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST) {
      return $user; // Skip XMLRPC
    }
    if(defined( 'REST_REQUEST' ) && REST_REQUEST) {
      return $user; // Skip REST API
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence check only, to route WooCommerce submissions; the nonce itself is verified by WooCommerce.
    if(!isset($_POST['woocommerce-login-nonce'])) {
      return $user; // Only handle WooCommerce form submissions
    }

    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_woocommerce_login();
    if (!empty($mode)) {
      $altcha = isset($_POST['altcha']) ? trim(sanitize_text_field(wp_unslash($_POST['altcha']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
      if ($plugin->verify($altcha) === false) {
        return new WP_Error(
          'altcha-error',
          esc_html__('Could not verify you are not a robot.', 'openporte')
        );
      }
    }
    return $user;
  },
  20,
  1
);

add_action(
  'woocommerce_lostpassword_form',
  function () {
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_woocommerce_reset_password();
    if (!empty($mode)) {
      openporte_woocommerce_comments_render_widget($mode);
    }
  },
  10,
  0
);

add_filter(
  'lostpassword_post',
  function ($errors) {
    if (is_user_logged_in()) {
      return $errors;
    }
    // Only handle WooCommerce form submissions
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence check only, to route WooCommerce submissions; the nonce itself is verified by WooCommerce.
    if(!isset($_POST['woocommerce-lost-password-nonce'])) {
      return $errors;
    }
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_woocommerce_reset_password();
    if (!empty($mode)) {
      $altcha = isset($_POST['altcha']) ? trim(sanitize_text_field(wp_unslash($_POST['altcha']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
      if ($plugin->verify($altcha) === false) {
        $errors->add(
          'openporte_error_message',
          esc_html__('Could not verify you are not a robot.', 'openporte')
        );
      }
    }
    return $errors;
  },
  10,
  1
);

function openporte_woocommerce_comments_render_widget($mode, $name = null)
{
  $plugin = OpenPortePlugin::$instance;
  echo wp_kses($plugin->render_widget($mode, true, null, $name), OpenPortePlugin::$html_espace_allowed_tags);
}