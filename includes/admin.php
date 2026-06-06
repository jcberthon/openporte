<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require plugin_dir_path(__FILE__) . '../admin/options.php';

if (is_admin()) {

    add_action('admin_menu', 'altcha_options_page');

    // Add link to settings page in the navbar
    function altcha_options_page()
    {
        add_options_page(
            __('OpenPorte Spam Protection', 'openporte'),
            __('OpenPorte Anti-spam', 'openporte'),
            'manage_options',
            'altcha_admin',
            'altcha_options_page_html',
            30
        );
    }

    // Add link to settings in the plugin list
    // uses WPDOCS_PLUGIN_BASE which is defined in openporte.php, which is required before this file
    add_filter('plugin_action_links_' . WPDOCS_PLUGIN_BASE, 'altcha_settings_link');

    function altcha_settings_link($links)
    {
        $url = esc_url(add_query_arg(
            'page',
            'altcha_admin',
            get_admin_url() . 'options-general.php'
        ));
        $settings_link = "<a href='$url'>" . __('Settings', 'openporte') . '</a>';

        array_unshift(
            $links,
            $settings_link
        );
        return $links;
    }
}
