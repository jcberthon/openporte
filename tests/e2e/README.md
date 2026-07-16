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
- **1 negative control** — strip the widget from the DOM and submit; the
  server must reject. Without this, a combo could "pass" because verification
  isn't wired up at all.

Real browser, real PoW solving (no mocks): the widget genuinely computes the
challenge, so this also catches timing regressions like the complexity-range
change that exposed the wpDiscuz race.

## Prerequisites

1. A running bench: `./wp-env.sh start` (from the repo root; provisions
   fixtures via `tests/bin/wp-init.sh` — the CF7 driver relies on its
   "Contact Us" page). **OpenPorte must be activated** on the bench
   (`./wp-env.sh run cli -- wp plugin activate openporte`) — the init script
   leaves it deactivated for the migration test.
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
