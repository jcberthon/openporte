<?php
/**
 * Proves the bootstrap works: the plugin class loads under the shim.
 *
 * @package OpenPorte\Tests
 */
class SmokeTest extends OpenPorteTestCase
{
  public function test_plugin_class_loads()
  {
    $this->assertInstanceOf('OpenPortePlugin', $this->plugin);
    $this->assertSame(5, $this->plugin->get_replaylimit());
  }

  public function test_a_fresh_token_verifies()
  {
    $this->assertTrue($this->plugin->verify($this->token(time() + 600)));
  }

  public function test_the_replay_key_prefix_stays_inside_the_uninstall_sweep()
  {
    // uninstall.php deletes _transient_openporte_% and
    // _transient_timeout_openporte_%, hardcoded because the class cannot be
    // loaded during uninstall. A prefix rename that dropped the openporte_
    // head would leave every counter row behind, silently.
    $this->assertStringStartsWith('openporte_', OpenPortePlugin::$replay_key_prefix);
    // …and this suite's own key builder must follow the property rather than
    // its own copy of the string.
    $this->assertStringStartsWith(
      OpenPortePlugin::$replay_key_prefix,
      $this->counter_key($this->token(time() + 600))
    );
  }
}
