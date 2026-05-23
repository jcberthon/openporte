<?php
/*
 * Plugin Name: ALTCHA: Spam Protection (Installer)
 * Plugin URI: https://altcha.org
 * Description: ALTCHA for WordPress delivers professional, invisible spam protection that works with any form plugin, handles heavy traffic, and keeps your site safe without annoying visitors. With built-in firewall, rate limiting, and GDPR-compliant security, it’s the all-in-one solution for fast, reliable, and privacy-first WordPress protection.
 * Version: 3.0.0
 * Stable tag: 3.0.0
 * Author: Altcha.org
 * Author URI: https://altcha.org
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: altcha-spam-protection
 * Requires at least: 5.0
 * Requires PHP: 8.1
 * Tested up to: 7.0
 */

if (!defined('ABSPATH')) exit;

define('ALTCHA_DOWNLOADER_GH_REPO', 'altcha-org/altcha-wordpress-next');
define('ALTCHA_DOWNLOADER_PLUGIN_SLUG', 'altcha');
define('ALTCHA_DOWNLOADER_PLUGIN_FILE', 'altcha/altcha.php');

register_activation_hook(__FILE__, 'altcha_downloader_on_activate');
add_action('admin_notices', 'altcha_downloader_notice');
add_action('admin_init', 'altcha_downloader_maybe_deactivate_self');

function altcha_downloader_on_activate()
{
    // Already installed — just activate.
    if (file_exists(WP_PLUGIN_DIR . '/' . ALTCHA_DOWNLOADER_PLUGIN_FILE)) {
        $result = activate_plugin(ALTCHA_DOWNLOADER_PLUGIN_FILE);
        if (is_wp_error($result)) {
            set_transient('altcha_downloader_notice', [
                'type'    => 'error',
                'message' => $result->get_error_message(),
            ], 60);
        } else {
            set_transient('altcha_downloader_notice', ['type' => 'activated'], 60);
            set_transient('altcha_downloader_deactivate_self', true, 60);
        }
        return;
    }

    $result = altcha_downloader_install_plugin();

    if (is_wp_error($result)) {
        set_transient('altcha_downloader_notice', [
            'type'    => 'error',
            'message' => $result->get_error_message(),
        ], 60);
    } else {
        set_transient('altcha_downloader_notice', ['type' => 'success'], 60);
        set_transient('altcha_downloader_deactivate_self', true, 60);
    }
}

function altcha_downloader_install_plugin()
{
    if (!class_exists('ZipArchive')) {
        return new WP_Error('no_zip', __('ZipArchive PHP extension is required but not available.', 'altcha-spam-protection'));
    }

    // Fetch latest release metadata.
    $response = wp_remote_get(
        'https://api.github.com/repos/' . ALTCHA_DOWNLOADER_GH_REPO . '/releases/latest',
        [
            'timeout' => 30,
            'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
        ]
    );

    if (is_wp_error($response)) {
        return $response;
    }
    if (wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_Error('api_error', __('Failed to fetch release information from GitHub.', 'altcha-spam-protection'));
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);

    // Build codeload URL from tag (same approach as updater.php — avoids redirect issues).
    $tag = $release['tag_name'] ?? null;
    if (!$tag) {
        return new WP_Error('no_tag', __('Could not determine release tag from GitHub response.', 'altcha-spam-protection'));
    }
    $zip_url = 'https://codeload.github.com/' . ALTCHA_DOWNLOADER_GH_REPO . '/zip/refs/tags/' . $tag;

    // Download to a temporary file.
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $tmp = download_url($zip_url, 60);
    if (is_wp_error($tmp)) {
        return $tmp;
    }

    $target = WP_PLUGIN_DIR . '/' . ALTCHA_DOWNLOADER_PLUGIN_SLUG;

    // Extract to a temp directory — codeload zips contain a single subfolder.
    $tmp_dir = $target . '_tmp_' . uniqid();
    wp_mkdir_p($tmp_dir);

    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) {
        @unlink($tmp);
        altcha_downloader_rmdir($tmp_dir);
        return new WP_Error('zip_open', __('Failed to open the downloaded zip file.', 'altcha-spam-protection'));
    }

    $extracted = $zip->extractTo($tmp_dir);
    $zip->close();
    @unlink($tmp);

    if (!$extracted) {
        altcha_downloader_rmdir($tmp_dir);
        return new WP_Error('zip_extract', __('Failed to extract the plugin zip.', 'altcha-spam-protection'));
    }

    // Find the single subfolder codeload creates (e.g. altcha-wordpress-next-3.0.2/).
    $subdirs = array_filter(
        array_diff(scandir($tmp_dir), ['.', '..']),
        fn($item) => is_dir($tmp_dir . DIRECTORY_SEPARATOR . $item)
    );
    $subdir = count($subdirs) === 1 ? $tmp_dir . DIRECTORY_SEPARATOR . reset($subdirs) : null;

    if (!$subdir) {
        altcha_downloader_rmdir($tmp_dir);
        return new WP_Error('zip_structure', __('Unexpected zip structure from GitHub.', 'altcha-spam-protection'));
    }

    // Replace existing plugin directory with extracted content.
    if (is_dir($target)) {
        altcha_downloader_rmdir($target);
    }
    rename($subdir, $target);
    altcha_downloader_rmdir($tmp_dir);

    if (!file_exists(WP_PLUGIN_DIR . '/' . ALTCHA_DOWNLOADER_PLUGIN_FILE)) {
        return new WP_Error('missing_file', __('Plugin file not found after extraction.', 'altcha-spam-protection'));
    }

    // Clear plugin cache so get_plugins() sees the newly extracted plugin.
    wp_cache_delete('plugins', 'plugins');

    // Activate the real plugin.
    $activate = activate_plugin(ALTCHA_DOWNLOADER_PLUGIN_FILE);
    if (is_wp_error($activate)) {
        return $activate;
    }

    return true;
}

function altcha_downloader_maybe_deactivate_self()
{
    if (!get_transient('altcha_downloader_deactivate_self')) {
        return;
    }
    delete_transient('altcha_downloader_deactivate_self');

    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    deactivate_plugins(plugin_basename(__FILE__));
    altcha_downloader_rmdir(plugin_dir_path(__FILE__));
}

function altcha_downloader_notice()
{
    $notice = get_transient('altcha_downloader_notice');
    if (!$notice) {
        return;
    }
    delete_transient('altcha_downloader_notice');

    if ($notice['type'] === 'success') {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__('ALTCHA Spam Protection has been installed and activated successfully.', 'altcha-spam-protection')
        );
    } elseif ($notice['type'] === 'activated') {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__('ALTCHA Spam Protection has been activated successfully.', 'altcha-spam-protection')
        );
    } elseif ($notice['type'] === 'error') {
        printf(
            '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
            esc_html__('ALTCHA installation failed:', 'altcha-spam-protection'),
            esc_html($notice['message'])
        );
    }
}

function altcha_downloader_rmdir(string $dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? altcha_downloader_rmdir($path) : unlink($path);
    }
    rmdir($dir);
}
