<?php
/**
 * "Custom HTML" integration: configures hand-written <altcha-widget> tags by
 * loading the widget scripts and a global attrs object on every front-end page.
 *
 * Deprecated since 1.28.0, removal in the next major release (see #62): the
 * [openporte] shortcode covers the use cases without the every-page script
 * cost, and nothing verifies a hand-placed form's submissions server-side.
 * Behaviour is kept intact for the deprecation window so affected sites can
 * re-enable the toggle while they migrate to the shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
  'wp_enqueue_scripts',
  function () {
    $plugin = OpenPortePlugin::$instance;
    // Read the option directly — through the deprecated getter, merely loading
    // a page would log a deprecation notice even with the feature turned off.
    $active = get_option(OpenPortePlugin::$option_integration_custom);
    if ($active) {
      // With WP_DEBUG on, this logs on every front-end page load — the
      // intended signal that this site still depends on a deprecated feature.
      // WP renders this as: Function OpenPorte's "Custom HTML" integration is
      // deprecated since version 1.28.0! Use the [openporte] shortcode instead.
      _deprecated_function('OpenPorte\'s "Custom HTML" integration', '1.28.0', 'the [openporte] shortcode');
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
        $plugin->get_widget_attrs($active),
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
