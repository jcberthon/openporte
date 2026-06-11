<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('elementor')){
  function openporte_register_form_field( $form_fields_registrar ) {
    require_once(__DIR__ . '/elementor/field.php');

    $form_fields_registrar->register(new \OpenPorte_Elementor_Form_Field());
  }
  $plugin = OpenPortePlugin::$instance;
  $mode = $plugin->get_integration_elementor();
  if ($mode === 'captcha' || $mode === 'captcha_spamfilter') {
    add_action('elementor_pro/forms/fields/register', 'openporte_register_form_field');
  }
}