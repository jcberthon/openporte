<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require plugin_dir_path(__FILE__) . '../admin/options.php';

if (is_admin()) {

    add_action('admin_menu', 'openporte_options_page');

    // Add link to settings page in the navbar
    function openporte_options_page()
    {
        add_options_page(
            __('OpenPorte Spam Protection', 'openporte'),
            __('OpenPorte Anti-spam', 'openporte'),
            'manage_options',
            'openporte_admin',
            'openporte_options_page_html',
            30
        );
    }

    // Add link to settings in the plugin list
    // uses WPDOCS_PLUGIN_BASE which is defined in openporte.php, which is required before this file
    add_filter('plugin_action_links_' . WPDOCS_PLUGIN_BASE, 'openporte_settings_link');

    function openporte_settings_link($links)
    {
        $url = esc_url(add_query_arg(
            'page',
            'openporte_admin',
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
