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
}
