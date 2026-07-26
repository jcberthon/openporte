# Acceptance criteria

Acceptance record for **v1.28.0**. Run the automated checks first, then the
manual wp-env steps. Tick the boxes as evidence while performing the release
validation (Phase 5 of `docs/release-preparation.md`).

## Automated checks

1. [ ] `npm run release:check` — `php -l`/`bash -n` pass (blocking); `phpcs`/`phpstan`
       output reviewed, no unexplained new findings (informative)
2. [ ] Settings-matrix E2E suite (`cd tests/e2e && npm test`, see `tests/e2e/README.md`)
       full matrix, **all drivers green**: integrations × auto-verification mode ×
       Floating UI, plus the negative controls (missing token, forged token, expired-token replay)

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

**(e) PHP and WordPress compatibility verification**

1. [ ] Test with PHP 8.0 and WordPress 5.6 — minimum supported combo (PHP floor, first
       WP version with PHP 8.0 support)
2. [ ] Test with PHP 8.3 and WordPress 6.8 — oldest maintained PHP version, the WP major
       before current stable
3. [ ] Test with PHP 8.5 and WordPress 7.0 — newest PHP version, newest WP version available

(The deprecated PHP 7.3 / WP 5.0 combo from the v1.27.0 run is retired: the
supported floor has been PHP 8.0 / WP 5.6 since the v1.27.0 acceptance run
documented the `str_ends_with()` requirement.)

**(f) v1.28.0 feature checks**

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

**(g) WordPress Plugin Check**

1. [ ] Run the Plugin Check plugin against the build on a WP 6.3+ bench: every finding
       resolved or justified (known readme-only gotchas: `Tested up to` must be
       major.minor; upgrade notice ≤ 300 chars)
