<?php
/**
 * OpenPorte uninstall handler.
 *
 * Runs when the user deletes the plugin from WordPress. It removes ONLY
 * OpenPorte's own (openporte_*) options. The legacy altcha_* options belong to
 * the original ALTCHA Spam Protection plugin and are intentionally left
 * untouched, so a user can keep using — or roll back to — ALTCHA v1 without
 * losing their configuration.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove every openporte_* option in a single pass (no hard-coded key list, so
// new openporte_* options are cleaned up automatically). Note: this targets the
// current site's options table only; network-wide cleanup on multisite is not
// handled here.
$openporte_like = $wpdb->esc_like( 'openporte_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off bulk delete of the plugin's own options on uninstall; there is no core API for prefix deletion and caching is irrelevant during uninstall.
$wpdb->query(
	$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $openporte_like )
);

wp_cache_flush();
