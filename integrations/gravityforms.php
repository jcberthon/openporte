<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (openporte_plugin_active('gravityforms')) {
  add_action(
    'gform_loaded',
    function () {
      $plugin = OpenPortePlugin::$instance;
      $mode = $plugin->get_integration_gravityforms();
      if ($mode === 'captcha' || $mode === 'captcha_spamfilter') {
        require_once('gravityforms/addon.php');
        GFAddOn::register('OPENPORTE_GFFormsAddOn');
      }
    },
    5
  );

  add_filter(
    'gform_entry_is_spam',
    function ($is_spam, $form, $entry) {
      if ($is_spam) {
        return $is_spam;
      }
      $plugin = OpenPortePlugin::$instance;
      $mode = $plugin->get_integration_gravityforms();
      if (!empty($mode)) {
        if ($plugin->spamfilter_result && $plugin->spamfilter_result['classification'] === 'BAD') {
          $is_spam = true;
        }
      }
      if ($is_spam && method_exists('GFCommon', 'set_spam_filter')) {
        $reason = "";
        if ($plugin->spamfilter_result) {
          $score =  $plugin->spamfilter_result['score'];
          $reason = "score: $score, " . implode(', ', (array) $plugin->spamfilter_result['reasons']);
        }
        GFCommon::set_spam_filter(rgar($form, 'id'), 'OpenPorte Spam Filter', $reason);
      }
      return $is_spam;
    },
    10,
    3
  );
}
