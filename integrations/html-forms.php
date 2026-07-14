<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('html-forms')) {
  add_filter(
    'hf_form_html',
    'do_shortcode'
  );

  add_filter(
    'hf_form_html',
    function ($html) {
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_html_forms();
      if ($active) {
        return str_replace('</form>', wp_kses($plugin->render_widget($active), OpenPortePlugin::$html_espace_allowed_tags) . '</form>', $html);
      }
      return $html;
    }
  );

  add_filter(
    'hf_validate_form',
    function ($error_code, $form, $data) {
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_html_forms();
      if (!$active) {
        if (strpos($form, "<altcha-widget ") === false) {
          // If the integration isn't activated and when the altcha widget is not found in the form markup then the verification is skipped
          return $error_code;
        }
      } else {
        $altcha = isset($_POST['altcha']) ? trim(sanitize_text_field(wp_unslash($_POST['altcha']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ($plugin->verify($altcha ) === false) {
          return "openporte_invalid";
        }
      }
      return $error_code;
    },
    10,
    3
  );

  add_filter(
    'hf_form_message_openporte_invalid',
    function ($message) {
      return __('Could not verify you are not a robot.', 'openporte');
    }
  );

  add_filter(
    'hf_form_message_openporte_spam',
    function ($message) {
      return __('Could not verify you are not a robot.', 'openporte');
    }
  );
}
