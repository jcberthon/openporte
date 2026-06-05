# OpenPorte test bench (wp-env)

Manual, browser-driven test environment for the plugin. There is no automated
test suite (see `AGENTS.md`); this harness just provisions a reproducible
WordPress instance with fixtures so changes can be exercised by hand.

## What it sets up

Driven by [`.wp-env.json`](../.wp-env.json) (`afterStart` →
[`bin/wp-init.sh`](bin/wp-init.sh)):

- **WordPress** (version pinned in `.wp-env.json`) on `http://localhost:8888`.
- **Contact Form 7** — installed and **activated** (used by the integration page).
- **ALTCHA Spam Protection v1.26.3** (the upstream plugin OpenPorte forks) and
  **OpenPorte** (this repo, mapped into `wp-content/plugins/openporte`) — both
  **installed but deactivated**.
- Two pages:
  - **Contact Us** — a Contact Form 7 form; the form id is discovered at
    provisioning time (not hard-coded, since Contact Form 7 assigns it on install).
  - **Test Page** — the `[altcha]` and `[openporte]` shortcodes, to check the
    primary shortcode and its deprecated alias render.

`wp-init.sh` is idempotent: re-running leaves existing fixture pages untouched.

## Running it

The repo is exercised on a remote Docker host via [`wp-env.sh`](../wp-env.sh)
(host/path in `.wp-env.conf`). Typical loop:

```sh
./wp-env.sh start        # boots wp-env and runs the afterStart hook
./wp-env.sh -v start     # same, with environment details printed
./wp-env.sh stop
```

To target other versions: `./wp-env.sh -p 8.0 -w 6.5 start`.

## The ALTCHA → OpenPorte migration test

> **Activate ALTCHA and OpenPorte one at a time, never together** — both
> register the `[altcha]` shortcode and the `altcha/v1` REST route and will clash.

The migration runs on **OpenPorte activation** (`register_activation_hook`), not
as a silent in-place update. Because the entry file was renamed
`altcha.php → openporte.php`, WordPress treats OpenPorte as a distinct plugin.

1. Activate **ALTCHA Spam Protection** (v1.26.3). Set some **non-default** config
   (e.g. API mode `custom` + a Challenge URL, change the complexity, toggle the
   Contact Form 7 integration). Note the signing secret
   (`wp option get altcha_secret`).
2. Deactivate ALTCHA.
3. Activate **OpenPorte**. On activation it **copies** every `altcha_*` option
   into its `openporte_*` counterpart (guarded: it never overwrites an existing
   `openporte_*` value).
4. Verify:
   - `wp option list | grep -E 'altcha_|openporte_'` — the `openporte_*` options
     mirror the `altcha_*` ones, **and the `altcha_*` options are still present**
     (so the user can roll back to ALTCHA v1 without data loss).
   - The signing secret is **unchanged** (`wp option get openporte_secret` equals
     the value noted in step 1) — otherwise previously issued challenges break.
   - On **Test Page**, both `[altcha]` and `[openporte]` render the widget.
   - `/wp-json/altcha/v1/challenge` and `/wp-json/openporte/v1/challenge` both
     return a challenge.
   - `./wp-env.sh logs` (or `wp-env logs`) is clean — no PHP warnings/notices.
5. Roll back (optional): deactivate OpenPorte, reactivate ALTCHA — its config is
   intact.

## Open point: the legacy ALTCHA zip

`bin/wp-init.sh` installs ALTCHA v1.26.3 from `local/altcha-spam-protection.1.26.3.zip`
when present, otherwise from wordpress.org
(`https://downloads.wordpress.org/plugin/altcha-spam-protection.1.26.3.zip`).

As of this writing wordpress.org still serves the **byte-identical** 1.26.3 build,
so committing the zip to git is unnecessary — the URL fallback keeps the harness
reproducible for other contributors. **Decision pending (JC):** delete the local
zip and rely on the URL, or keep a committed copy as an offline/archival pin in
case the upstream listing is ever removed. The `local/` zip is git-ignored today.
