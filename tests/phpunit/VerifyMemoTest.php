<?php
/**
 * Memoisation inside verify(): the per-request payload memo, the signature
 * memo, and the once-per-call result hook.
 *
 * @package OpenPorte\Tests
 */
class VerifyMemoTest extends OpenPorteTestCase
{
  public function test_two_verifications_in_one_request_count_as_one_use()
  {
    // No next_request() between the two calls: this is the dual
    // `authenticate` hook case (WordPress + WooCommerce fire in the same
    // request), where the token must be counted once, not twice.
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);

    $this->assertTrue($this->plugin->verify($token));
    $this->assertTrue($this->plugin->verify($token));
    $this->assertSame('1', $this->counter_value($token));
  }

  public function test_the_result_hook_fires_once_per_verify_call()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);

    $this->plugin->verify($token);
    $this->plugin->verify($token);

    $this->assertCount(2, OpenPorte_Test_Env::actions('openporte_verify_result'));
  }

  public function test_a_new_request_re_enforces_the_limit()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);

    $this->assertTrue($this->plugin->verify($token));
    $this->next_request();
    $this->assertFalse($this->plugin->verify($token));
  }

  public function test_a_re_encoded_envelope_counts_once_in_the_same_request()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);
    $reencoded = $this->reencode($token);

    $this->assertNotSame($token, $reencoded);
    $this->assertTrue($this->plugin->verify($token));
    $this->assertTrue($this->plugin->verify($reencoded));
    $this->assertSame('1', $this->counter_value($token));
  }

  public function test_a_re_encoded_envelope_is_refused_in_the_next_request()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);

    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertFalse($this->verify_in_new_request($this->reencode($token)));
  }

  public function test_a_malformed_token_is_memoised_as_a_failure()
  {
    $this->assertFalse($this->plugin->verify('not-base64!!'));
    $this->assertFalse($this->plugin->verify('not-base64!!'));

    $this->assertCount(2, OpenPorte_Test_Env::actions('openporte_verify_result'));
    $this->assertSame(array(), OpenPorte_Test_Env::replay_rows());
  }

  public function test_the_memo_does_not_confuse_two_different_tokens()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;

    $this->assertTrue($this->plugin->verify($this->token(time() + 600)));
    $this->assertTrue($this->plugin->verify($this->token(time() + 600)));
  }
}
