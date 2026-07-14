<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('contact-form-7')) {
  add_filter('wpcf7_form_elements', 'do_shortcode');

  add_filter(
    'wpcf7_form_elements',
    function ($elements) {
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_contact_form_7();
      if ($active) {
        $input = '<input class="wpcf7-form-control wpcf7-submit ';
        $button = '<button class="wpcf7-form-control wpcf7-submit ';
        $widget = wp_kses($plugin->render_widget($active, true, OpenPortePlugin::$language), OpenPortePlugin::$html_espace_allowed_tags);
        if (strpos($elements, $input) !== false) {
          $elements = str_replace($input, $widget . $input, $elements);
        } else if (strpos($elements, $button) !== false) {
          $elements = str_replace($button, $widget . $button, $elements);
        } else {
          $elements .= $widget;
        }
      }
      return $elements;
    },
    100,
    1
  );

  add_filter(
    'wpcf7_spam',
    function ($spam) {
      if ($spam) {
        return $spam;
      }
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_contact_form_7();
      if ($active) {
        $altcha = isset($_POST['altcha']) ? trim(sanitize_text_field(wp_unslash($_POST['altcha']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return $plugin->verify($altcha) === false;
      }
      return $spam;
    },
    9,
    1
  );
}
