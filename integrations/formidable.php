<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function openporte_load_formidable_field() {
	spl_autoload_register( 'openporte_forms_autoloader' );
}
add_action( 'plugins_loaded', 'openporte_load_formidable_field' );

function openporte_forms_autoloader( $class_name ) {
	if ( ! preg_match( '/^OpenPorte.+$/', $class_name ) ) {
		return;
	}

	$filepath = dirname( __FILE__ );
	$filepath .= '/formidable/' . $class_name . '.php';

	if ( file_exists( $filepath ) ) {
		require( $filepath );
	}
}

function openporte_get_field_type_class( $class, $field_type ) {
	if ( $field_type === 'altcha' ) {
		$class = 'OpenPorteFieldType';
	}
	return $class;
}
add_filter( 'frm_get_field_type_class', 'openporte_get_field_type_class', 10, 2 );

function openporte_add_new_field( $fields ) {
	$fields['altcha'] = array(
		'name' => 'OpenPorte',
		'icon' => 'frm_icon_font frm_shield_check_icon',
	);
	return $fields;
}
add_filter( 'frm_available_fields', 'openporte_add_new_field' );