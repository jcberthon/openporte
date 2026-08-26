<?php
/**
 * The reuse counter, end to end through verify(): the limit setting, its
 * filter, the per-request counting, and the result hook on refusals.
 *
 * @package OpenPorte\Tests
 */
class ReplayLimitTest extends OpenPorteTestCase
{
  public function test_limit_of_three_accepts_three_requests_then_refuses()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 3;
    $token = $this->token(time() + 600);

    $results = array();
    for ($i = 0; $i < 4; $i++) {
      $results[] = $this->verify_in_new_request($token);
    }

    $this->assertSame(array(true, true, true, false), $results);
    $this->assertSame('3', $this->counter_value($token));
  }

  public function test_limit_of_one_refuses_the_second_request()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);

    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertFalse($this->verify_in_new_request($token));
  }

  public function test_limit_of_zero_accepts_every_replay()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 0;
    $token = $this->token(time() + 600);

    for ($i = 0; $i < 5; $i++) {
      $this->assertTrue($this->verify_in_new_request($token), 'request ' . ($i + 1));
    }
  }

  public function test_limit_of_zero_writes_no_state()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 0;
    $token = $this->token(time() + 600);

    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertSame(array(), OpenPorte_Test_Env::replay_rows());
  }

  public function test_missing_option_uses_the_default_of_five()
  {
    $token = $this->token(time() + 600);

    for ($i = 0; $i < 5; $i++) {
      $this->assertTrue($this->verify_in_new_request($token), 'request ' . ($i + 1));
    }
    $this->assertFalse($this->verify_in_new_request($token));
  }

  public function test_non_numeric_option_uses_the_default_of_five()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 'abc';

    $this->assertSame(5, $this->plugin->get_replaylimit());
  }

  public function test_a_negative_stored_value_means_unlimited()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = -5;

    $this->assertSame(0, $this->plugin->get_replaylimit());
  }

  public function test_a_filter_can_lower_the_limit()
  {
    OpenPorte_Test_Env::$filters['openporte_replay_limit'] = function ($limit) {
      return 2;
    };
    $token = $this->token(time() + 600);

    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertFalse($this->verify_in_new_request($token));
  }

  public function test_a_filter_receives_the_current_hook_as_context()
  {
    OpenPorte_Test_Env::$current_filter = 'authenticate';
    $captured = null;
    OpenPorte_Test_Env::$filters['openporte_replay_limit'] = function ($limit, $context) use (&$captured) {
      $captured = $context;
      return $limit;
    };

    $this->plugin->get_replaylimit();

    $this->assertSame('authenticate', $captured);
  }

  public function test_a_filter_returning_a_non_numeric_value_falls_back_to_the_stored_limit()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 7;
    OpenPorte_Test_Env::$filters['openporte_replay_limit'] = function ($limit) {
      return 'abc';
    };

    $this->assertSame(7, $this->plugin->get_replaylimit());
  }

  public function test_a_filter_returning_a_negative_value_falls_back_to_the_stored_limit()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 7;
    OpenPorte_Test_Env::$filters['openporte_replay_limit'] = function ($limit) {
      return -3;
    };

    $this->assertSame(7, $this->plugin->get_replaylimit());
  }

  public function test_a_refused_replay_still_fires_the_result_hook_with_false()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->token(time() + 600);

    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertFalse($this->verify_in_new_request($token));

    $actions = OpenPorte_Test_Env::actions('openporte_verify_result');
    $this->assertSame(2, count($actions));
    $this->assertFalse(end($actions)[1]);
  }

  public function test_a_server_signature_token_is_counted_too()
  {
    OpenPorte_Test_Env::$options['openporte_replaylimit'] = 1;
    $token = $this->server_token(time() + 600);

    $this->assertTrue($this->verify_in_new_request($token));
    $this->assertFalse($this->verify_in_new_request($token));
  }
}
