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
    // G1 regression guard: in Custom mode the Expiration field is disabled,
    // a disabled field submits null, and absint(null) would be 0 — the worst
    // possible replay configuration. null must keep the stored value.
    OpenPorte_Test_Env::$options['openporte_expires'] = 1800;

    $this->assertSame(1800, openporte_sanitize_expires(null));
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
}
