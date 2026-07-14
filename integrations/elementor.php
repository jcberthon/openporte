<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('elementor')){
  function openporte_register_form_field( $form_fields_registrar ) {
    require_once(__DIR__ . '/elementor/field.php');

    $form_fields_registrar->register(new \OpenPorte_Elementor_Form_Field());
  }
  $openporte_plugin = OpenPortePlugin::$instance;
  $openporte_active = $openporte_plugin->get_integration_elementor();
  // Truthy check: the option is a checkbox (0/1) since the spam-filter removal;
  // legacy string values ('captcha', …) are normalized on upgrade.
  if ($openporte_active) {
    add_action('elementor_pro/forms/fields/register', 'openporte_register_form_field');
  }
}