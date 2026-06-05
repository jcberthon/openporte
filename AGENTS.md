# AGENTS.md

> **v0 — starting point.** Refine this file as you learn the codebase. Delete anything that turns out to be wrong.

## How to use this file

References like `@docs/agents/*.md` are **lazy-loaded**: read the linked file with
your Read tool only when the current task makes it relevant (e.g., load
commit-conventions.md when committing, not on every turn).

## What this repo is

Community reconstruction of the retired official ALTCHA WordPress plugin. The upstream GPL project has been removed from GitHub by its original author — there is no live upstream to reference or merge from. **We are the canonical source.** Style and structure decisions are ours alone. License: GPLv2 or later.

Pure PHP WordPress plugin. No Composer, no npm, no build step, no test suite, no linter.

Compat floor: PHP/WP minimums in `readme.txt`. Don't use syntax/APIs newer than the floor.

## Scope: paid-plugin integrations being removed

   Integrations targeting paid-only plugins (Enfold; check others in
   `integrations/` against their authors' licensing) are scheduled for removal
   from this fork. Users should migrate to the official ALTCHA plugin (v2/v3).

   Until removed, commits modifying these files should use the `Deprecate` verb.

## Verification protocol

There are zero automated tests. Before and after changes:

1. Use [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (requires Docker — OrbStack or Docker Desktop on macOS) to spin up a local WordPress instance and test manually.
2. Tail `wp-env`'s PHP error log (`wp-env logs`) after any change — PHP warnings and notices surface here and are otherwise silent.
3. For integration changes, activate the relevant third-party plugin in `wp-env` and exercise the affected form.

## Entry point and load order

`openporte.php` is the sole entry point. It `require`s files in this order, which matters:

1. `includes/helpers.php`
2. `includes/core.php` — instantiates `OpenPortePlugin` singleton immediately on load
3. `public/widget.php`
4. All 13 files under `integrations/` — each self-registers its hooks at `require` time

Each integration file registers hooks unconditionally at load; the callbacks themselves check `OpenPortePlugin::$instance->get_integration_*()` to decide whether to act.

## Coding conventions

- **Singleton access:** always `OpenPortePlugin::$instance`. Never call `new OpenPortePlugin()`.
- **WP options keys:** all defined as `static` properties on `OpenPortePlugin` (e.g., `OpenPortePlugin::$option_api`). Never hardcode the raw option string `"openporte_*"` anywhere — always reference the property. (The legacy `altcha_*` keys live only in the activation-time migration map.)
- **i18n:** most user-facing strings use `__()` / `esc_html__()`. Exceptions exist (see fix-mes below) — follow the existing pattern when adding new strings.
- **Static analysis**: only necessary for code changes, then read `@docs/agents/static-analysis.md`.

### i18n discipline (apply on every change)

- All user-facing strings must be wrapped in a translation function
  (`__()`, `esc_html__()`, `esc_attr__()`, …) with the text domain.
- Any string containing a placeholder (`%s`, `%d`, `%1$s`, …) MUST be preceded
  by a `/* translators: … */` comment describing each placeholder. See the
  `get_translations()` footer string for the existing pattern. Plain strings
  with no placeholder do NOT need such a comment.
- Adding or changing a user-facing string invalidates its existing translation
  and requires the `.pot` template to be regenerated. Note this in the commit
  so translations are refreshed.

### Comment what you touch

When you modify a branch, fallback, or workaround whose intent is not
self-evident, add a short inline comment explaining the *why*, not the *what*.
Example: in `get_challengeurl()`, the final `else` is annotated as the
self-hosted default and graceful fallback for legacy `eu`/`us` DB values.
Prefer a one-line intent comment over leaving future readers (human or agent)
to re-derive the logic.

## Version bump checklist

Five locations must change atomically or the plugin breaks:

| File | Field |
|---|---|
| `openporte.php` | `* Version:` in header |
| `openporte.php` | `* Stable tag:` in header |
| `openporte.php` | `define('OPENPORTE_VERSION', ...)` |
| `readme.txt` | `Version:` in header |
| `readme.txt` | `Stable tag:` + new `= X.Y.Z =` changelog entry |

`OPENPORTE_VERSION` is also used as the cache-busting query string for all enqueued assets.

## `public/altcha.min.js` — vendored, do not edit

Vendored from [`altcha-org/altcha`](https://github.com/altcha-org/altcha) (MIT
at last upgrade). Version tracked by `OPENPORTE_WIDGET_VERSION` in `openporte.php`.

For upgrades and licensing-risk contingency (only load on a need-basis):
@docs/agents/altcha-upstream.md

## Known gotchas

**`authenticate` hook — dual registration at priority 20.**
`integrations/wordpress.php` and `integrations/woocommerce.php` both hook `authenticate` at priority 20. Mutual exclusion relies on `isset($_POST['woocommerce-login-nonce'])`. The same pattern applies to `lostpassword_post`. If WooCommerce renames that nonce field, both handlers fire on the same request. Keep both files in sync when changing auth logic.

**`integrations/coblocks.php` — intentional reCAPTCHA spoof.**
CoBlocks has no extension API, so the integration fakes a reCAPTCHA token and intercepts the outbound HTTP verification call via `pre_http_request`. This is deliberate. The intercept matches on `CoBlocks_Form::GCAPTCHA_VERIFY_URL` — if that constant changes in a CoBlocks update, all CoBlocks forms silently break.

**`has_active_integrations()` blind spots.**
`get_integrations()` in `core.php` omits Enfold Theme and WP-Members. Their hooks load and fire unconditionally regardless of the "only enqueue scripts on pages with active integrations" logic.

**Fix on sight when touching adjacent code (call out in commit message):**

- The core.php defects previously listed here — the untranslated "requires
  JavaScript" string, the missing `is_wp_error()` guard, and the `===` HMAC
  comparison — were fixed in 1.27.0. When touching the HMAC path, preserve the
  `true` (raw binary) flag on `hash('sha256', …, true)`; removing it breaks all
  challenge verification.
- Read the file `local/Security_Analysis.md` for security issues that need
  correction (not published online).

## Release

Push a git tag. The `.github/workflows/publish.yml` workflow deploys straight to WordPress.org SVN. No manual steps.

## Commit conventions

Imperative verb prefix (`Add`, `Fix`, `Update`, `Remove`, `Refactor`, `Docs`,
`Bump`, `Deprecate`, `Revert`), ≤72 chars, no trailing period. Issue refs in
body footer: `Fixes #123` to auto-close, `Refs #123` otherwise.

Full conventions and examples (load on a as-needed-basis): @docs/agents/commit-conventions.md
