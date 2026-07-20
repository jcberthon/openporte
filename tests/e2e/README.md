# OpenPorte settings-matrix E2E suite

Browser-driven tests that loop **integrations × auto-verification mode ×
Floating UI** against a running wp-env bench, so combinations that are too
numerous to click through by hand get exercised automatically. Born from the
wpDiscuz `auto="onsubmit"` race (submission AJAX winning against the
proof-of-work solve).

Per integration driver it runs:

- **8 acceptance combos** — auto ∈ {disabled, onload, onfocus, onsubmit} ×
  floating ∈ {off, on}: fill the form, solve (pre-solve where the mode allows,
  submit-triggered for onsubmit/floating — the race paths), submit, assert the
  submission was accepted.
- **2 negative controls** — the server must reject both:
  - *missing token*: strip the widget from the DOM and submit. Without this,
    a combo could "pass" because verification isn't wired up at all.
  - *forged token*: solve for real, then corrupt the payload's HMAC signature
    before submitting. Client-side everything looks verified; only the
    server's signature check can catch it.

One suite-wide extra negative (on the core-comments driver only, because the
server-side check is integration-agnostic): **expired token replay** — solve
with `openporte_expires=2`, capture the token, detach the widget (a live one
auto-refetches expired challenges), submit the stale token after the validity
window, expect rejection.

Drivers: WordPress core comments, Contact Form 7, wpDiscuz, WPForms Lite,
WooCommerce login, WooCommerce registration. The third WooCommerce surface
(reset password) is not driven: it shares the registration code path (render
hook + a `*_post` filter) but its happy path ends in an email the mailer-less
bench can't observe.

Real browser, real PoW solving (no mocks): the widget genuinely computes the
challenge, so this also catches timing regressions like the complexity-range
change that exposed the wpDiscuz race.

## Prerequisites

1. A running bench: `./wp-env.sh start` (from the repo root; provisions
   fixtures via `tests/bin/wp-init.sh` — the CF7 driver relies on its
   "Contact Us" page, the WPForms driver on its "WPForms Test" page).
   **OpenPorte must be activated** on the bench
   (`./wp-env.sh run cli -- wp plugin activate openporte`) — the init script
   never activates it (a fresh bench starts with it deactivated, for the
   migration test).
2. Reachability: the suite talks to `WP_BASE_URL`
   (default `http://localhost:8888`). For the remote Docker host, either use
   `WP_BASE_URL=http://<remote-host>:8888` or an SSH tunnel
   (`ssh -f -N -L 8888:localhost:8888 user@remote`).
3. wp-cli bridge: settings are flipped between tests via
   `./wp-env.sh run cli -- wp option …` (default). Override with
   `WP_CLI_CMD="wp-env run cli -- wp"` for a local wp-env.
4. Node ≥ 18. Then:

```sh
cd tests/e2e
npm install
npx playwright install chromium
```

## Running

```sh
cd tests/e2e
npm test                                   # full matrix, all drivers
MATRIX_DRIVERS=wpdiscuz npm test           # one integration
npx playwright test --grep "onsubmit"      # one mode across drivers
npm run test:headed                        # watch it
npm run report                             # open the HTML report
```

Tests run **serially with one worker** — the settings under test are global
WordPress options.

## Adding an integration

Create `drivers/<name>.js` exporting
`{ key, option, ensure, open, fill, submit, expectAccepted, expectRejected }`
(see `drivers/contact-form-7.js` for the smallest example) and register it in
`drivers/index.js`. `ensure()` runs once per driver: activate the plugin,
create fixtures idempotently, return the context (page path) the other hooks
receive.

## Known caveats

- **wp-cli argument quoting**: `wp-env.sh` forwards commands through
  `ssh "… wp-env $*"`, so the remote shell strips one layer of quoting.
  Values passed to the `wp()` helper must contain no spaces or quotes
  (fixture values are deliberately space-free, e.g. `E2E-Comments`).
- **Comment flood throttle**: core rejects a comment posted within 15 s of
  the previous one from the same IP ("You are posting comments too
  quickly") — every submission here comes from one browser IP, so the
  comment drivers purge the fixture post's comments before each test
  (`driver.reset()`).
- **CF7 acceptance** treats `data-status="failed"` as captcha-accepted: on a
  bench with no mailer the mail send fails *after* the spam check. `spam` is
  the captcha-rejected status. All four visible fields must be filled — the
  default form's Subject is required, and CF7 6.x may block an invalid form
  client-side with the status silently stuck on `init`.
- **Floating UI combos** intentionally use the submit-triggered flow; the
  widget's floating popup positioning is not asserted (only that the
  submission ultimately succeeds). This is the path that caught the
  submit-swallowed-while-verifying bug fixed in `public/script.js`.
- **WooCommerce drivers** share one `[woocommerce_my_account]` fixture page:
  logged out it holds the login form and (once registration is enabled) the
  register form. Each driver zeroes the sibling WooCommerce integration
  options in `ensure()` so exactly one widget renders — the shared helpers
  target `.first()`. The register driver's `reset()` deletes the customers
  its tests created, sparing the login driver's fixture user.
