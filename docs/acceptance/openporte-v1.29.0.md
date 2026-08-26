# Acceptance criteria

Acceptance record for **v1.29.0**. Run the automated checks first, then the
manual wp-env steps. Tick the boxes as evidence while performing the release
validation (Phase 5 of `docs/release-preparation.md`).

## Automated checks

1. [ ] `npm run release:check` — `php -l`/`bash -n` pass (blocking); `phpcs`/`phpstan`
       output reviewed, no unexplained new findings (informative)
2. [ ] PHPUnit unit suite (`npm run test:unit`) **green**, no skipped or risky tests
3. [ ] E2E suite (`cd tests/e2e && npm test`, see `tests/e2e/README.md`) **all
       drivers green**: the settings matrix (integrations × auto-verification
       mode × Floating UI) with its negative controls (missing token, forged
       token, expired-token replay), plus the replay-limit suite (budget spent
       then refused, strict single use, unlimited at 0, AJAX resubmission, login
       still succeeds at strict)

## wp-env manual test steps

Tests **(a)**, **(b)** and **(c)** are our regression tests.

**(a) Self-hosted form submits and verifies**

1. [ ] wp-env start
2. [ ] Activate the plugin; confirm Settings → OpenPorte Anti-spam shows "API Mode:
       Self-hosted" by default
3. [ ] Enable an integration (e.g. WordPress → Comments)
4. [ ] Submit a comment with the OpenPorte widget — confirm the entry is created; no PHP
       errors in wp-env logs
5. [ ] Also verify there are no outgoing network requests to altcha.org in the browser's
       Network tab (the whole point: no external dependencies). This is the test that
       proves the SaaS is truly gone.

**(b) Switching to "custom" mode shows the Challenge URL field**

1. [ ] In Settings → OpenPorte Anti-spam, change "API Mode" to "Custom"
2. [ ] Confirm the "Challenge URL" text input becomes enabled immediately (JS toggle)
3. [ ] Enter any URL; confirm it saves; confirm the widget's challengeurl attribute
       reflects it in the rendered HTML
4. [ ] Negative test: in Self-hosted mode, confirm that the Challenge URL field is
       indeed disabled (this is the counterpart of the [data-custom-api] toggle; tests
       that you didn't break the JS)

**(c) No PHP notices in wp-env logs, no error or warning in browser dev console**

1. [ ] After each action above, run wp-env logs and confirm no PHP Warning, PHP Notice,
       or Undefined errors — in particular none referencing the spam-filter symbols
       removed in this release (blockspam, server-signature classification)
2. [ ] After each action above, check in the browser development console that there are
       no warning or error.

**(d) Upgrade successful**

1. [ ] Test a legacy install: before wp-env start, manually set altcha_api to "eu" in
       the database (`wp-env run cli wp option update altcha_api eu`), then load a page
       with widget and confirm that the challenge hits the local REST endpoint and not
       `eu.altcha.org`. This is the graceful degradation scenario, the easiest to break.
2. [ ] ALTCHA → OpenPorte migration on a bench with ALTCHA v1 previously configured (see
       `tests/README.md`): settings carried over, forms keep verifying
3. [ ] Algorithm default on upgrade — all four routes, since only a fresh install may
       end up on SHA-512 (`wp option get openporte_algorithm`, and confirm the served
       challenge's `algorithm` field agrees):
       - [ ] Fresh activation on a clean bench → seeded **SHA-512**
       - [ ] Plugin update from pre-1.28 OpenPorte (activation hook does not run, so no
             option is stored) → `get_algorithm()` falls back to **SHA-256**
       - [ ] ALTCHA v1 → OpenPorte install + activate (the activation hook *does* run,
             right after the legacy migration) → **SHA-256**
       - [ ] Deactivate + reactivate a pre-1.28 OpenPorte bench → still **SHA-256**,
             i.e. a plugin toggle must not change the algorithm
       The last two regressed before release: the activation hook seeded SHA-512
       unconditionally, which in Custom API mode would break verification against a
       backend still serving SHA-256.
4. [ ] A bench upgraded from a version with spam-filter options set shows no notice and
       silently ignores the leftover options
5. [ ] Replay limit is seeded on **both** routes and never overwrites a choice
       (`wp option get openporte_replaylimit`):
       - [ ] Fresh activation on a clean bench → **5**
       - [ ] Plugin update from 1.28.x (the activation hook does not run, so
             `openporte_upgrade()` seeds it on `plugins_loaded`) → **5**
       - [ ] Set it to 1, then deactivate + reactivate → still **1**, i.e. a plugin
             toggle must not reset an admin's choice
6. [ ] No expiry migration happened: a bench with `openporte_expires` set to `0` or `30`
       still holds that value after the upgrade (1.29.0 warns, it does not migrate)

**(e) PHP and WordPress compatibility verification**

1. [ ] Test with PHP 8.0 and WordPress 5.6 — minimum supported combo (PHP floor, first
       WP version with PHP 8.0 support)
2. [ ] Test with PHP 8.3 and WordPress 6.8 — oldest maintained PHP version, the WP major
       before current stable
3. [ ] Test with PHP 8.5 and WordPress 7.0 — newest PHP version, newest WP version available

(The deprecated PHP 7.3 / WP 5.0 combo from the v1.27.0 run is retired: the
supported floor has been PHP 8.0 / WP 5.6 since the v1.27.0 acceptance run
documented the `str_ends_with()` requirement.)

**(f) v1.29.0 replay protection**

The E2E replay-limit suite covers 1–3 automatically; tick them from that run, or
verify by hand. Steps 4–8 are **not** covered by any harness.

1. [ ] Default limit (5): solve a challenge, capture the token, and replay it —
       accepted five times in total, refused on the sixth
2. [ ] Strict (1): a replay is refused immediately, and **logging in still works** on
       both `wp-login.php` and the WooCommerce login form (the dual `authenticate`
       registration — the highest-harm failure mode)
3. [ ] Unlimited (0): repeated replays are all accepted, and a query for
       option names matching `%openporte_replay_%` returns **nothing** — the
       counter must write no state at all when it is off
4. [ ] Counter storage and lifetime, database path (no object cache): after one
       verified submission there is a `_transient_openporte_replay_*` row **and** its
       paired `_transient_timeout_*` row, and the timeout is the token's own expiry
       (roughly now + Expiration), not a fixed window
5. [ ] Object-cache path: with a persistent object cache installed, the same replays are
       bounded and **no** `openporte_replay_*` option rows appear; the settings page
       reports the counter as backed by the object cache
6. [ ] **Concurrency (manual — the one thing no harness can prove).** Neither the unit
       suite (single process, fake `$wpdb`) nor Playwright (one worker, by design) can
       demonstrate atomicity; it comes from InnoDB row locking. Set the limit to 3,
       capture one solved token, and replay it from **at least 10 parallel**
       requests at once — e.g. `seq 10 | xargs -P10 -I{} curl …` posting the
       same token to the form endpoint. Exactly **3** must be accepted, no more.
       Repeat a few times: a lost update shows up as an occasional 4th
       acceptance, not as a consistent one.
       (A standalone script for this is tracked in #102.)
7. [ ] Fail-open is observable: stop the object cache (or otherwise break the store),
       replay a token — the submission is **accepted** (documented degradation), the
       settings page reports the fail-open episode, and a listener on
       `openporte_replay_store_unavailable` fires
8. [ ] Deprecation notice: with `WP_DEBUG` on, a direct call to
       `OpenPortePlugin::$instance->verify_solution($token)` logs a deprecation naming
       `verify()`; a normal form submission logs **nothing**
9. [ ] Uninstall cleanup: after generating counters, deactivate and delete the plugin,
       then confirm **zero** rows match `openporte_%`, `_transient_openporte_%` or
       `_transient_timeout_openporte_%`

**(g) v1.29.0 settings UI**

1. [ ] Replay limit field: presets save and round-trip; choosing "Custom" reveals the
       numeric field (0–100) and the value persists; `wp option update
       openporte_replaylimit 7` renders as **Custom / 7** rather than silently snapping
       to a preset
2. [ ] Sanitiser: entering `-5` stores **0** (not 5), and `250` stores **100**
3. [ ] Expiration advisories (Self-hosted): `0` shows a red error-style notice and
       **still saves**; a value under 60 s shows a warning and still saves; a normal
       value shows neither
4. [ ] Custom mode: the Expiration control is greyed out with the explanatory line, and
       — the important part — **saving in Custom mode does not change
       `openporte_expires`** (`wp option get openporte_expires` before and after). A
       disabled field submits nothing; without the null guard this would silently store
       `0`, the worst possible replay configuration
5. [ ] Switching API Mode back and forth enables/disables Expiration and Challenge URL
       live, without a reload (JS toggle)
6. [ ] Custom-mode health check: a backend whose challenges carry no `expires` in the
       salt yields a warning naming that; one that sets a sane expiry yields a success
       notice mentioning it
7. [ ] Replay-protection status notice appears on the settings screen and matches
       reality (limit in force, object cache vs database)

**(h) v1.28.0 feature checks (regression)**

1. [ ] Algorithm setting: Settings → OpenPorte Anti-spam offers SHA-256 / SHA-384 / SHA-512;
       select a non-default value, confirm the served challenge uses it and a submission
       still verifies end-to-end
2. [ ] Expiration setting: the preset select saves; choosing "Custom" reveals the
       numeric field (0–14400 s) and the value persists; with a short expiry, a stale
       token is rejected server-side (covered automatically by the E2E expired-token
       replay — tick from that run or verify by hand)
3. [ ] Challenge URL health check (Custom mode): on the settings screen, a reachable
       ALTCHA-compatible backend with matching secret/algorithm yields a success notice;
       an unreachable URL or mismatched secret yields a warning/error notice; saving a
       changed URL/secret/algorithm re-runs the check (transient busted)
4. [ ] Custom HTML integration: its toggle is styled/labelled **Deprecated** (removal
       next major, pointing at the `[openporte]` shortcode) but remains functional when enabled
5. [ ] Widget Customisation controls: aligned and consistently labelled; each option
       (hide footer/logo, auto-verification, floating, delay) still round-trips through save
6. [ ] Activation-conflict screen: with ALTCHA v1 active, activating OpenPorte via
       Plugins → Add New → Upload shows the conflict screen and its link back to the
       Plugins page works (#65)

**(i) WordPress Plugin Check**

1. [ ] Run the Plugin Check plugin against the build on a WP 6.3+ bench: every finding
       resolved or justified (known readme-only gotchas: `Tested up to` must be
       major.minor; upgrade notice ≤ 300 chars)
