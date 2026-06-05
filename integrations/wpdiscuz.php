<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
  'wpdiscuz_button_actions',
  function () {
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_wpdiscuz();
    if (!empty($mode)) {
      $plugin = OpenPortePlugin::$instance;
      $output = "<div class=\"altcha-widget-wrap-wpdiscuz\">";
      $output .= $plugin->render_widget($mode, false);
      $output .= "</div>";
      echo wp_kses($output, OpenPortePlugin::$html_espace_allowed_tags);
    }
  },
  10,
  0
);
