<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('forminator')) {
  add_action(
    'forminator_render_button_markup',
    function ($html) {
      return openporte_forminator_render_widget($html);
    },
    10,
    2
  );

  add_action(
    'forminator_render_fields_markup',
    function ($html) {
      return openporte_forminator_render_widget($html);
    },
    10,
    2
  );

  add_filter(
    'forminator_cform_form_is_submittable',
    function ($can_show, $id, $form_settings) {
      $plugin = OpenPortePlugin::$instance;
      $mode = $plugin->get_integration_forminator();
      if (!empty($mode)) {
        if ($mode === "captcha" || $mode === "captcha_spamfilter") {
          $altcha = isset($_POST['altcha']) ? trim(sanitize_text_field(wp_unslash($_POST['altcha']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
          if ($plugin->verify($altcha) === false) {
            return [
              'can_submit' => false,
              'error' => __('Could not verify you are not a robot.', 'openporte'),
            ];
          }
        }
      }
      return $can_show;
    },
    10,
    3
  );
}

function openporte_forminator_render_widget($html)
{
  $plugin = OpenPortePlugin::$instance;
  $mode = $plugin->get_integration_forminator();
  if ($mode === "captcha" || $mode === "captcha_spamfilter") {
    $elements = wp_kses($plugin->render_widget($mode, true), OpenPortePlugin::$html_espace_allowed_tags);
    $target = '<div class="forminator-row forminator-row-last"';
    $pos = strpos($html, $target);

    if ($pos !== false) {
        $html = substr_replace($html, $elements, $pos, 0);
    } else {
        $target = '<button class="forminator-button ';
        $pos = strpos($html, $target);
        if ($pos !== false) {
            $html = substr_replace($html, $elements, $pos, 0);
        }
    }
  }
  return $html;
}