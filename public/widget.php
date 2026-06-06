<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter('script_loader_tag', 'openporte_script_tags', 10, 3);

function openporte_script_tags($tag, $handle, $src)
{
	if ('altcha-widget' == $handle) {
		return str_replace('<script', '<script async defer type="module"', $tag);
	}
	return $tag;
}
