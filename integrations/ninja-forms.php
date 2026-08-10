<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('ninja-forms')) {

  /**
   * Whether a Ninja Forms form already carries a manually placed widget.
   *
   * Scans the form's field settings (HTML-field content, mostly) for the
   * [openporte]/[altcha] shortcodes or a hand-written <altcha-widget> tag.
   * The same needle list as the html-forms and Contact Form 7 backstops, and
   * used for the same two reasons: skip auto-injection when a widget is
   * already there (two widgets means two required checkboxes and duplicate
   * "altcha" inputs), and keep verifying a manually placed widget even while
   * the integration toggle is off — otherwise the captcha shown to visitors
   * would be decorative and bots could submit right past it.
   *
   * @since 1.29.0
   *
   * @param int|string $form_id Ninja Forms form id (a multi-instance suffix
   *                            like "3_2" is reduced to the real id).
   * @return bool
   */
  function openporte_ninja_forms_has_widget($form_id)
  {
    foreach (Ninja_Forms()->form(absint($form_id))->get_fields() as $field) {
      foreach ((array) $field->get_settings() as $setting) {
        if (!is_string($setting)) {
          continue;
        }
        if (
          strpos($setting, '[openporte') !== false
          || strpos($setting, '[altcha') !== false
          || strpos($setting, '<altcha-widget') !== false
        ) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * The field id to attach a verification error to.
   *
   * Ninja Forms has no form-level error channel in the ninja_forms_submit_data
   * filter — only errors keyed to a real field id are read back (and halt the
   * submission) during the field-processing loop. The submit button is itself
   * a field on every stock form and sits right next to the widget, so the
   * message lands where the visitor is looking; if a form somehow has no
   * submit field, any submitted field keeps the error visible.
   *
   * @since 1.29.0
   *
   * @param array $form_data Decoded submission payload (the filter's argument).
   * @param int   $form_id   Ninja Forms form id.
   * @return int Field id, or 0 when the form has no usable field.
   */
  function openporte_ninja_forms_error_field_id($form_data, $form_id)
  {
    foreach (Ninja_Forms()->form($form_id)->get_fields() as $field) {
      if ($field->get_setting('type') === 'submit') {
        return (int) $field->get_id();
      }
    }
    if (isset($form_data['fields']) && is_array($form_data['fields'])) {
      foreach ($form_data['fields'] as $field) {
        if (isset($field['id'])) {
          return (int) $field['id'];
        }
      }
    }
    return 0;
  }

  add_filter(
    'ninja_forms_display_after_fields',
    function ($after_fields, $form_id) {
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_ninja_forms();
      $has_widget = openporte_ninja_forms_has_widget($form_id);
      if ($active || $has_widget) {
        // Ninja Forms submits through its Backbone app: the AJAX payload is
        // built from field models plus an "extra" object, so the widget's
        // hidden "altcha" input is never serialized into it on its own. The
        // helper script copies the solved-challenge payload into "extra"
        // before submit; without it, verification below would reject every
        // submission.
        wp_enqueue_script(
          'openporte-ninja-forms',
          OpenPortePlugin::$ninja_forms_script_src,
          array('nf-front-end'),
          OPENPORTE_VERSION,
          true
        );
      }
      if ($active && !$has_widget) {
        $after_fields .= wp_kses($plugin->render_widget($active), OpenPortePlugin::$html_espace_allowed_tags);
      }
      return $after_fields;
    },
    10,
    2
  );

  add_filter(
    'ninja_forms_submit_data',
    function ($form_data) {
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_ninja_forms();
      // absint() also reduces a multi-instance "3_2" id to the real form id.
      $form_id = isset($form_data['id']) ? absint($form_data['id']) : 0;
      if (!$form_id) {
        return $form_data;
      }
      // Same backstop as html-forms and Contact Form 7: a manually placed
      // widget is verified even with the integration switched off. Keyed off
      // the stored form definition, never the submitted payload — a bot
      // simply omits the field.
      if (!$active && !openporte_ninja_forms_has_widget($form_id)) {
        return $form_data;
      }
      $altcha = isset($form_data['extra']['altcha']) && is_string($form_data['extra']['altcha'])
        ? trim(sanitize_text_field($form_data['extra']['altcha']))
        : '';
      if ($plugin->verify($altcha) === false) {
        $field_id = openporte_ninja_forms_error_field_id($form_data, $form_id);
        if ($field_id) {
          $form_data['errors']['fields'][$field_id] = array(
            'slug' => 'openporte_invalid',
            'message' => __('Could not verify you are not a robot.', 'openporte'),
          );
        }
      }
      return $form_data;
    }
  );
}
