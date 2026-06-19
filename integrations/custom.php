<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
  'wp_enqueue_scripts',
  function () {
    $plugin = OpenPortePlugin::$instance;
    $mode = $plugin->get_integration_custom();
    if ($mode === 'captcha' || $mode === 'captcha_spamfilter') {
      // Register the base widget script first
      openporte_enqueue_scripts();

      // Now enqueue the custom script with its dependency
      wp_enqueue_script(
        'altcha-widget-custom',
        OpenPortePlugin::$custom_script_src,
        array('altcha-widget'),
        OPENPORTE_VERSION,
        true
      );
      // JSON_HEX_* so the encoded value is safe to embed in the inline <script>
      // below: it escapes <, >, &, ' and " and so cannot break out of the script
      // context (e.g. a literal "</script>" in any attribute value).
      $attrs = wp_json_encode(
        $plugin->get_widget_attrs($mode),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
      );
      wp_register_script(
        'altcha-widget-custom-options',
        '',
        array(),
        OPENPORTE_VERSION,
        false,
      );
      wp_enqueue_script('altcha-widget-custom-options');
      wp_add_inline_script(
        'altcha-widget-custom-options',
        "(() => { window.OPENPORTE_WIDGET_ATTRS = $attrs; })();",
      );
    }
  },
  10,
  0
);
