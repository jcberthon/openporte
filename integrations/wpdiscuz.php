<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
  'wpdiscuz_button_actions',
  function () {
    $plugin = OpenPortePlugin::$instance;
    $active = $plugin->get_integration_wpdiscuz();
    if ($active) {
      $plugin = OpenPortePlugin::$instance;
      $output = "<div class=\"altcha-widget-wrap-wpdiscuz\">";
      $output .= $plugin->render_widget($active, false);
      $output .= "</div>";
      echo wp_kses($output, OpenPortePlugin::$html_espace_allowed_tags);
    }
  },
  10,
  0
);
