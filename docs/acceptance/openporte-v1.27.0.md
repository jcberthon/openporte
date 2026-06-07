# Acceptance criteria

## wp-env manual test steps

Tests **(a)**, **(b)** and **(c)** are our regression tests.

**(a) Self-hosted form submits and verifies**
1. [X] wp-env start
2. [X] Activate the plugin; confirm Settings → ALTCHA shows "API Mode: Self-hosted" by default
3. [X] Enable an integration (e.g. WordPress → Comments)
4. [X] Submit a comment with the ALTCHA widget — confirm the entry is created; no PHP errors in wp-env logs
5. [X] Also verify there are no outgoing network requests to altcha.org in the browser's Network tab (the whole point: no external dependencies). This is the test that proves the SaaS is truly gone.

**(b) Switching to "custom" mode shows the Challenge URL field**
1. [X] In Settings → ALTCHA, change "API Mode" to "Custom"
2. [X] Confirm the "Challenge URL" text input becomes enabled immediately (JS toggle)
3. [X] Enter any URL; confirm it saves; confirm the widget's challengeurl attribute reflects it in the rendered HTML
4. [X] Negative test: in Self-hosted mode, confirm that the Challenge URL field is indeed disabled (this is the counterpart of the [data-custom-api] toggle; tests that you didn't break the JS)

**(c) No PHP notices in wp-env logs, no error or warning in browser dev console**
1. [X] After each action above, run wp-env logs and confirm no PHP Warning, PHP Notice, or Undefined errors related to altcha_api_key, get_api_key, or the removed functions
2. [X] After each action above, check in the browser development console that there are no warning or error.

**(d) Upgrade successful**
1. [X] Test a legacy install: before wp-env start, manually set altcha_api to "eu" in the database (wp option update altcha_api eu via wp-env run cli), then load a page with widget and confirm that the challenge hits the local REST endpoint and not eu.altcha.org. This is the graceful degradation scenario, the easiest to break. Before testing, force an old value in the database and verify graceful degradation: `wp-env run cli wp option update altcha_api eu` then reload a page with widget → the challenge must hit the local REST endpoint, not `eu.altcha.org`.

**(e) PHP and Wordpress compatibility verification**
1. [-] Test with PHP 7.3 and Wordpress 5.0 - oldest combo PHP/Wordpress versions, but deprecated, support will be removed in versions after 1.27.*
2. [x] Test with PHP 8.0 and Wordpress 5.6 - minimum PHP version supported, newest WP version available
3. [x] Test with PHP 8.3 and Wordpress 6.8 - oldest maintained PHP version, the 2nd oldest version after the current WP stable version (7.0)
4. [x] Test with PHP 8.5 and Wordpress 7.0 - newest PHP version, newest WP version available

The test 1. failed. The function core.php:generate_challenge() is calling str_ends_with() standard function which was introduced in PHP 8.0 ([PHP 8.0 New Features - Official](https://www.php.net/manual/en/migration80.new-features.php#migration80.new-features.standard)). This is existing (aka legacy) code, so v1.26.3 was already requiring PHP 8.0 but the documentation (readme.txt) wasn't updated. The first Wordpress version to support PHP 8.0 is Wordpress 5.6. As part of this issue, the documentation (all where necessary) requires an update of the minimum requirements.
