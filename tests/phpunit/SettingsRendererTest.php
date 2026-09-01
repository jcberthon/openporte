<?php
/**
 * The select renderers in admin/options.php: the Custom-mode markup that
 * public/admin.js reacts to. This covers both plain dropdowns and the preset
 * plus Custom-number shape shared by Expiration and Replay limit.
 *
 * @package OpenPorte\Tests
 */
class SettingsRendererTest extends OpenPorteTestCase
{
  /** Run a renderer and capture the HTML it echoes. */
  private function render(callable $renderer)
  {
    ob_start();
    $renderer();
    return (string) ob_get_clean();
  }

  public function test_the_select_renders_a_visible_note_when_disabled()
  {
    $html = $this->render(function () {
      openporte_settings_select_callback(array(
        'name' => 'openporte_complexity',
        'disabled' => true,
        'disabled_note' => 'Disabled in Custom mode: the backend sets the complexity.',
        'options' => array('low' => 'Low', 'medium' => 'Medium', 'high' => 'High'),
      ));
    });

    $this->assertStringContainsString('data-selfhosted-note', $html);
    $this->assertStringContainsString('Disabled in Custom mode', $html);
    // Disabled => the note is visible, so no inline display:none.
    $this->assertStringNotContainsString('data-selfhosted-note style="display:none"', $html);
  }

  public function test_the_select_hides_the_note_while_enabled()
  {
    $html = $this->render(function () {
      openporte_settings_select_callback(array(
        'name' => 'openporte_complexity',
        'disabled' => false,
        'disabled_note' => 'Disabled in Custom mode: the backend sets the complexity.',
        'options' => array('low' => 'Low', 'medium' => 'Medium', 'high' => 'High'),
      ));
    });

    $this->assertStringContainsString('data-selfhosted-note style="display:none"', $html);
  }

  public function test_the_select_omits_the_note_when_none_is_given()
  {
    $html = $this->render(function () {
      openporte_settings_select_callback(array(
        'name' => 'openporte_algorithm',
        'options' => array('SHA-256' => 'SHA-256', 'SHA-512' => 'SHA-512'),
      ));
    });

    $this->assertStringNotContainsString('data-selfhosted-note', $html);
  }

  public function test_a_selfhosted_only_select_is_marked_in_the_served_markup()
  {
    // The attribute has to be in the HTML the server sends, not added by
    // public/admin.js on load: with JavaScript off, the markup is the only
    // thing that says this control is inert in Custom mode.
    $html = $this->render(function () {
      openporte_settings_select_callback(array(
        'name' => 'openporte_complexity',
        'selfhosted_only' => true,
        'options' => array('low' => 'Low', 'high' => 'High'),
      ));
    });

    $this->assertStringContainsString('data-selfhosted-api', $html);
  }

  public function test_a_select_is_not_marked_selfhosted_only_by_default()
  {
    $html = $this->render(function () {
      openporte_settings_select_callback(array(
        'name' => 'openporte_algorithm',
        'options' => array('SHA-256' => 'SHA-256', 'SHA-512' => 'SHA-512'),
      ));
    });

    $this->assertStringNotContainsString('data-selfhosted-api', $html);
  }

  public function test_disabled_expiration_markup_keeps_both_controls_inert()
  {
    OpenPorte_Test_Env::$options['openporte_expires'] = 300;
    $html = $this->render(function () {
      openporte_settings_expires_callback(array(
        'name' => 'openporte_expires',
        'disabled' => true,
        'disabled_note' => 'Disabled in Custom mode: the backend sets the challenge expiry.',
      ));
    });

    $this->assertSame(2, substr_count($html, 'data-selfhosted-api'));
    $this->assertSame(2, substr_count($html, ' disabled'));
    $this->assertStringContainsString('data-selfhosted-note', $html);
    $this->assertStringNotContainsString('data-selfhosted-note style="display:none"', $html);
    $this->assertStringContainsString('min="0" max="14400" step="1"', $html);
  }

  public function test_a_non_preset_expiration_selects_and_shows_custom_input()
  {
    OpenPorte_Test_Env::$options['openporte_expires'] = 777;
    $html = $this->render(function () {
      openporte_settings_expires_callback(array('name' => 'openporte_expires'));
    });

    $this->assertStringContainsString("value=\"custom\"  selected='selected'", $html);
    $this->assertStringContainsString('value="777" data-selfhosted-api', $html);
    $this->assertStringNotContainsString('value="777" data-selfhosted-api style="display:none"', $html);
  }

  public function test_replay_limit_markup_uses_its_own_bounds_and_stays_active()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 5;
    $html = $this->render(function () {
      openporte_settings_replaylimit_callback(array('name' => 'openporte_replaylimit'));
    });

    $this->assertStringContainsString('min="0" max="100" step="1"', $html);
    $this->assertStringNotContainsString('data-selfhosted-api', $html);
  }
}
