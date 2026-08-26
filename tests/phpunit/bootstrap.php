<?php
/**
 * PHPUnit bootstrap for the OpenPorte unit suite.
 *
 * Loads the WordPress stand-in (wp-shim.php), then the plugin's own code.
 * includes/core.php pulls in includes/admin.php, which pulls in
 * admin/options.php and admin/healthcheck.php, and includes/settings.php — so
 * one require makes every function under test available. The shim's
 * is_admin() returns false, so nothing registers an admin screen on load.
 *
 * @package OpenPorte\Tests
 */

require_once __DIR__ . '/wp-shim.php';
require_once __DIR__ . '/OpenPorteTestCase.php';
require_once dirname(__DIR__, 2) . '/includes/core.php';
