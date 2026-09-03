<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('ninja-forms')) {

  /**
   * Whether a piece of Ninja Forms field content carries a widget.
   *
   * The needle list is the one the html-forms and Contact Form 7 backstops
   * use: the [openporte]/[altcha] shortcodes, or a hand-written
   * <altcha-widget> tag.
   *
   * @since 1.29.0
   *
   * @param mixed $content Field content; anything but a string means no widget.
   * @return bool
   */
  function openporte_ninja_forms_content_has_widget($content)
  {
    if (!is_string($content)) {
      return false;
    }
    return strpos($content, '[openporte') !== false
      || strpos($content, '[altcha') !== false
      || strpos($content, '<altcha-widget') !== false;
  }

  /**
   * Whether the unsaved builder draft of a form carries a manually placed
   * widget.
   *
   * A preview renders the draft the form builder is holding, not the stored
   * form, so the stored form is the wrong thing to ask: a widget just added
   * to the draft would be injected a second time, one just removed would not
   * be injected at all. Reads the same user option Ninja Forms itself reads
   * to build the preview.
   *
   * @since 1.29.0
   *
   * @param int $form_id Ninja Forms form id.
   * @return bool|null Null when no draft is stored -- Ninja Forms then falls
   *                   back to rendering the saved form, and so should we.
   */
  function openporte_ninja_forms_draft_has_widget($form_id)
  {
    $draft = get_user_option('nf_form_preview_' . $form_id);
    if (!is_array($draft) || !isset($draft['fields']) || !is_array($draft['fields'])) {
      return null;
    }
    foreach ($draft['fields'] as $field) {
      if (!isset($field['settings']['type']) || $field['settings']['type'] !== 'html') {
        continue;
      }
      $content = isset($field['settings']['default']) ? $field['settings']['default'] : null;
      if (openporte_ninja_forms_content_has_widget($content)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Whether a Ninja Forms form already carries a manually placed widget.
   *
   * Only the HTML field is examined. Its content (the 'default' setting) is
   * the one place Ninja Forms both expands shortcodes and renders the result
   * raw, so there the needles mean a widget really appears. Everywhere else
   * they are inert text -- labels are wp_kses_post()'d, which strips
   * <altcha-widget>, and are never shortcode-expanded -- and matching them
   * would suppress injection while still enforcing verification, rejecting
   * every submission on that form.
   *
   * Used for the same two reasons as the other integrations' backstops: skip
   * auto-injection when a widget is already there (two widgets means two
   * required checkboxes and duplicate "altcha" inputs), and keep verifying a
   * manually placed widget even while the integration toggle is off --
   * otherwise the captcha shown to visitors would be decorative and bots
   * could submit right past it.
   *
   * @since 1.29.0
   *
   * @param int|string $form_id    Ninja Forms form id (a multi-instance suffix
   *                               like "3_2" is reduced to the real id).
   * @param bool       $is_preview Consult the unsaved builder draft first.
   *                               Only ever true when Ninja Forms itself says
   *                               so while rendering a preview.
   * @return bool
   */
  function openporte_ninja_forms_has_widget($form_id, $is_preview = false)
  {
    $form_id = absint($form_id);
    if ($is_preview) {
      $draft = openporte_ninja_forms_draft_has_widget($form_id);
      if ($draft !== null) {
        return $draft;
      }
    }
    foreach (Ninja_Forms()->form($form_id)->get_fields() as $field) {
      if ($field->get_setting('type') !== 'html') {
        continue;
      }
      if (openporte_ninja_forms_content_has_widget($field->get_setting('default'))) {
        return true;
      }
    }
    return false;
  }

  /**
   * The field id to attach a verification error to.
   *
   * Ninja Forms has no form-level error channel in the ninja_forms_submit_data
   * filter -- only errors keyed to a real field id are read back (and halt the
   * submission) during the field-processing loop. The submit button is itself
   * a field on every stock form and sits right next to the widget, so the
   * message lands where the visitor is looking.
   *
   * Every id considered here comes from the stored form, never from the
   * submitted payload: the controller's loop walks the server-side fields
   * unconditionally, so any of those ids is guaranteed to be visited, while a
   * forged one would be visited by nothing and the rejection would be dropped.
   *
   * @since 1.29.0
   *
   * @param int $form_id Ninja Forms form id.
   * @return int Field id, or 0 when the form has no field at all -- such a
   *             form collects nothing, so there is nothing to protect.
   */
  function openporte_ninja_forms_error_field_id($form_id)
  {
    $first_field_id = 0;
    foreach (Ninja_Forms()->form($form_id)->get_fields() as $field) {
      if ($field->get_setting('type') === 'submit') {
        return (int) $field->get_id();
      }
      if (!$first_field_id) {
        $first_field_id = (int) $field->get_id();
      }
    }
    // No submit field: unusual, but a form driven by custom JS can lack one.
    return $first_field_id;
  }

  add_filter(
    'ninja_forms_display_after_fields',
    function ($after_fields, $form_id, $is_preview = false) {
      $plugin = OpenPortePlugin::$instance;
      $active = $plugin->get_integration_ninja_forms();
      // Ninja Forms passes the preview flag itself (only from
      // NF_Display_Render::localize_preview()), so unlike the "is_preview"
      // setting in the submitted payload it cannot be forged by a client.
      $has_widget = openporte_ninja_forms_has_widget($form_id, (bool) $is_preview);
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
    3
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
      // the stored form definition, never the submitted payload -- a bot
      // simply omits the field. For the same reason the payload's
      // "is_preview" setting is ignored here: it is client-controlled, so
      // honouring it would hand every bot a way to skip verification.
      if (!$active && !openporte_ninja_forms_has_widget($form_id)) {
        return $form_data;
      }
      $altcha = isset($form_data['extra']['altcha']) && is_string($form_data['extra']['altcha'])
        ? trim(sanitize_text_field($form_data['extra']['altcha']))
        : '';
      if ($plugin->verify($altcha) === false) {
        $field_id = openporte_ninja_forms_error_field_id($form_id);
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
