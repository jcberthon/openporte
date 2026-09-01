<?php
/**
 * The settings sanitizers in includes/settings.php: the null (disabled field)
 * guard, the clamp bounds, the companion Custom input, and the advisories.
 *
 * @package OpenPorte\Tests
 */
class SettingsSanitizerTest extends OpenPorteTestCase
{
  public function test_a_null_expires_preserves_the_stored_value()
  {
    // G1 regression guard: the browser omits the disabled Expiration field in
    // Custom mode, then wp-admin/options.php updates the registered setting
    // with null. absint(null) would be 0 — the worst possible replay
    // configuration — so null must keep the stored value.
    OpenPorte_Test_Env::$options['openporte_expires'] = 1800;

    $this->assertSame(1800, openporte_sanitize_expires(null));
  }

  public function test_a_null_expires_returns_an_int_like_every_other_path()
  {
    // The option is stored as a string by WordPress; every other path through
    // the sanitizer returns int, and this one used to hand back the raw value.
    OpenPorte_Test_Env::$options['openporte_expires'] = '1800';

    $this->assertSame(1800, openporte_sanitize_expires(null));
  }

  public function test_the_expires_advisory_thresholds_are_classified_once()
  {
    // One classifier feeds both surfaces — the save-time _doing_it_wrong()
    // advisory and the settings-screen notice — so the thresholds cannot drift
    // apart, and #103 changes them in one place.
    $this->assertSame('error', openporte_expires_advisory_level(0));
    $this->assertSame('warning', openporte_expires_advisory_level(30));
    $this->assertSame('', openporte_expires_advisory_level(60));
    $this->assertSame('', openporte_expires_advisory_level(300));
  }

  public function test_a_null_expires_does_not_warn()
  {
    OpenPorte_Test_Env::$options['openporte_expires'] = 1800;

    openporte_sanitize_expires(null);

    $this->assertSame(array(), OpenPorte_Test_Env::$deprecations);
  }

  public function test_expires_is_clamped_to_the_maximum()
  {
    $this->assertSame(14400, openporte_sanitize_expires(99999));
  }

  public function test_an_expires_of_zero_warns()
  {
    $this->assertSame(0, openporte_sanitize_expires(0));

    $this->assertContains('openporte_expires', OpenPorte_Test_Env::$deprecations);
  }

  public function test_an_expires_below_sixty_warns()
  {
    $this->assertSame(30, openporte_sanitize_expires(30));

    $this->assertContains('openporte_expires', OpenPorte_Test_Env::$deprecations);
  }

  public function test_an_expires_of_sixty_does_not_warn()
  {
    $this->assertSame(60, openporte_sanitize_expires(60));

    $this->assertSame(array(), OpenPorte_Test_Env::$deprecations);
  }

  public function test_expires_reads_the_companion_input_when_custom()
  {
    $_POST = array('openporte_expires_custom' => '90');

    $this->assertSame(90, openporte_sanitize_expires('custom'));
  }

  public function test_a_null_replaylimit_preserves_the_stored_value()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 7;

    $this->assertSame(7, openporte_sanitize_replaylimit(null));
  }

  public function test_a_negative_replaylimit_becomes_zero_not_its_absolute_value()
  {
    // intval() on purpose, not absint(): a mistyped "-5" must become 0, which
    // the admin sees is wrong, never 5, which silently re-enables protection.
    $this->assertSame(0, openporte_sanitize_replaylimit(-5));
  }

  public function test_replaylimit_is_clamped_to_one_hundred()
  {
    $this->assertSame(100, openporte_sanitize_replaylimit(250));
  }

  public function test_a_replaylimit_of_zero_is_preserved()
  {
    $this->assertSame(0, openporte_sanitize_replaylimit(0));
  }

  public function test_replaylimit_reads_the_companion_input_when_custom()
  {
    $_POST = array('openporte_replaylimit_custom' => '3');

    $this->assertSame(3, openporte_sanitize_replaylimit('custom'));
  }

  public function test_a_null_complexity_preserves_the_stored_value()
  {
    // G1 regression guard: the browser omits the disabled Complexity field in
    // Custom mode, then wp-admin/options.php updates the registered setting
    // with null. Without the guard sanitize_text_field(null) would wipe it to
    // '', silently downgrading the stored level to 'low' on the next
    // Self-hosted save. null must keep the stored value.
    OpenPorte_Test_Env::$options['openporte_complexity'] = 'high';

    $this->assertSame('high', openporte_sanitize_complexity(null));
  }

  public function test_a_complexity_value_is_trimmed()
  {
    $this->assertSame('high', openporte_sanitize_complexity('  high  '));
  }

  public function test_an_unknown_complexity_falls_back_to_low()
  {
    $this->assertSame('low', openporte_sanitize_complexity('extreme'));
  }

  public function test_a_complexity_level_added_via_the_matrix_filter_is_accepted()
  {
    OpenPorte_Test_Env::$filters['openporte_complexity_matrix'] = function ($matrix) {
      $matrix['extreme'] = array('min' => 1, 'max' => 2);
      return $matrix;
    };

    $this->assertSame('extreme', openporte_sanitize_complexity('extreme'));
  }
}
