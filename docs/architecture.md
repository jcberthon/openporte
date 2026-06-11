# Architecture

> Maintainer / contributor reference. User-facing essentials (how to choose a
> mode, the privacy stance) live in `readme.txt`; coding conventions live in
> `AGENTS.md`. This document reflects the codebase after the paid-SaaS removal
> and the OpenPorte rebrand (1.27.0).
>
> Naming note: the plugin class (`OpenPortePlugin`), the DB option keys, the
> public hooks, the REST namespace and the text domain were moved to the
> `openporte` namespace in 1.27.0, with the old `altcha_*` / `AltchaPlugin`
> names kept as deprecated aliases. Some internal function and settings-field
> identifiers still carry the `altcha_` prefix pending a follow-up cleanup.
> References are by function name rather than line number on purpose — line
> numbers in this codebase have already shifted several times.

## Overview and modes

The plugin operates in two modes, both fully self-contained with no external
service dependency. A third mode — a paid SaaS classifier hosted on
`altcha.org` — was removed.

- **`selfhosted`** (default): proof-of-work. Challenges are served by a
  WordPress REST endpoint at `wp-json/openporte/v1/challenge`. No API key, no
  external service, no account.
- **`custom`**: the challenge URL points to a backend the site operator runs
  themselves. Responses are verified by server signature using the site's own
  HMAC secret. This is the legitimate self-hostable backend path — it is *not*
  a paid or remote service.

The mode is selected via the `altcha_api` option. In `get_challengeurl()`,
`custom` returns the operator-supplied URL stored in `altcha_api_custom_url`;
any other value — including legacy `"eu"` / `"us"` values left in the database
by old installs — falls back to the local REST endpoint.

## Code map

Files are listed in roughly the order `openporte.php` requires them. Each
integration file self-registers its hooks at require time and no-ops unless its
`get_integration_*()` option is set.

```text
openporte.php              Entry point + plugin header. Defines constants, requires
                           every file in load order, registers the [openporte]/[altcha]
                           shortcodes, the activation + legacy-option migration hooks,
                           and the REST route.
uninstall.php              Deletes all openporte_* options on plugin deletion.
includes/
  core.php                 OpenPortePlugin singleton — shared logic: option accessors,
                           challenge generation, HMAC verification, widget HTML
                           rendering, the wp_kses attribute whitelist.
  helpers.php              Enqueue helpers + openporte_plugin_active() detection.
  admin.php                Admin menu (Settings → OpenPorte Anti-spam).
  settings.php             Settings API: option registration + sanitize callbacks,
                           sections and fields.
  index.php                Silence-is-golden guard.
admin/
  options.php              Settings-page HTML + the field/select render callbacks.
  index.php                Silence-is-golden guard.
integrations/              14 integration files (each self-registers its hooks):
  wordpress.php            WP login / register / comments / reset-password.
  woocommerce.php          WooCommerce login / register / reset-password.
  contact-form-7.php       Contact Form 7 (wpcf7_spam).
  gravityforms.php + gravityforms/{addon.php, field.php}    GF add-on + custom field.
  elementor.php  + elementor/field.php                      Elementor Pro custom field.
  formidable.php + formidable/{OpenPorteFieldType.php, builder-field.php, builder-settings.php}
                           Formidable custom field (autoloaded class).
  forminator.php           Forminator.
  wpforms.php              WPForms.
  coblocks.php             CoBlocks (intentional reCAPTCHA spoof — see Invariants).
  html-forms.php           HTML Forms.
  enfold-theme.php         Enfold theme contact / Mailchimp forms.
  wpdiscuz.php             wpDiscuz (renders into the WP comment flow).
  wpmembers.php            WP-Members (reuses the WP-registration option).
  custom.php               Shortcode / manual mode: enqueues the widget + its options.
  index.php                Silence-is-golden guard.
public/
  altcha.min.js            Vendored upstream ALTCHA web component (DO NOT EDIT; version =
                           OPENPORTE_WIDGET_VERSION). See "The vendored widget".
  altcha.js                Comment-only companion (no executable code).
  altcha.css               Widget wrapper styles.
  script.js                Front-end: fixes the checkbox name attr; removes duplicate
                           widgets via a MutationObserver.
  custom.js                Configures the widget from window.OPENPORTE_WIDGET_ATTRS.
  admin.js                 Settings-page toggle (enables Challenge URL in custom mode).
  admin.css                Settings-page styles.
  widget.php               Adds `async defer type="module"` to the widget <script> tag.
  index.php                Silence-is-golden guard.
languages/                 29 locales (.po/.mo) + openporte.pot.
docs/                      Maintainer docs: architecture.md, security-audit.md,
                           maintenance-testing.md, release-preparation.md, agents/.
tests/                     Manual wp-env bench (README + bin/wp-init.sh); no automated suite.
.github/workflows/         publish.yml (tag → WordPress.org SVN) + phpmd.yml (PHPMD → code scanning).
.wordpress-org/            WordPress.org banner / icon / screenshot assets.
```

## Verification dispatch

Verification is dispatched on the **shape of the decoded payload**, not the
configured mode. This distinction matters: changing the mode does not change how
a challenge is verified. In `verify()`, the plugin decodes the submitted token
via `decode_payload()` — a strict `base64_decode` + `json_decode` that returns
`null` for anything malformed, so junk submissions fail closed without emitting
PHP warnings. A valid object carrying a `verificationData` field is routed to
`verify_server_signature()`; otherwise to `verify_solution()` for proof-of-work.
Each method re-checks that the fields it needs are present before using them.

`verify_server_signature()` checks the HMAC signature against the site secret
(`get_secret()`), then parses `verificationData` into `$spamfilter_result`. It
returns `true` only when the signature is valid, the payload is unexpired
(`expire`, when present) and explicitly verified (`verified`, when present), and
the `classification` is not `BAD`. The `expire`/`verified` checks mirror the
ALTCHA reference implementation and are applied defensively — only when the
backend actually supplies the field — so minimal custom backends keep working.

`verify_solution()` performs proof-of-work verification: it validates the
challenge hash, its signature, and expiration, returning `true` only if all
checks pass.

The site secret is generated once at activation by `random_secret()` as a
256-bit key (`bin2hex(random_bytes(32))`), stored in `openporte_secret`, and
never regenerated for an existing install (so previously issued challenges keep
verifying). The full security review of this path — including the accepted
stateless-replay limitation — is in [`docs/security-audit.md`](security-audit.md).

## What was removed, and why

The paid `altcha.org` regional SaaS classifier was removed to keep the plugin
free and self-hosted, with no dependency on external services. Removed:

- The regional SaaS modes (`eu` / `us`) and their API-key requirement
- `$option_api_key` and `get_api_key()`
- The regional branch of `get_challengeurl()` that built URLs to
  `https://{region}.altcha.org`
- `spam_filter_check()` and `spam_filter_call()`, which POSTed submissions to
  `https://{region}.altcha.org/api/v1/classify`
- `$option_send_ip`, the `$hostname` property, and `get_ip_address()`
- `flatten_post()`, `sanitize_data()` and `remove_private_keys()` — helpers that
  flattened and sanitised form data for the classifier POST. They had no callers
  after the SaaS removal and were deleted in the security-hardening pass (see
  [`docs/security-audit.md`](security-audit.md), finding #11).

None of these symbols exist in the current codebase. The verification dispatch
(payload-shape, not API mode) was deliberately preserved; the security-hardening
pass only *added* checks (`expire`/`verified`, strict payload decoding) without
changing how a valid or invalid challenge is routed.

## Spam filter — status and limits

**The plugin provides no spam classifier.** The classification engine was a
hosted ALTCHA service (commercial successor: Sentinel) and was never
open-source.

What remains is consumer-side plumbing that acts on classification data only if
a `custom` backend supplies it:

- `verify_server_signature()` reads a classification out of the signed
  `verificationData` payload.
- `get_blockspam()` and the widget attribute `blockspam='1'` enable the
  blocking behavior.
- The Gravity Forms integration acts on `$spamfilter_result`, using its
  `classification`, `score`, and `reasons` fields.

This plumbing has **no effect** in `selfhosted` proof-of-work mode (no
classification is produced there), and the plugin ships with no classifier of
its own. It is not a feature offered out of the box.

## Privacy stance

In both supported modes the plugin requires no API key, makes no calls to
external paid services, collects no visitor IP address (`get_ip_address()` was
removed), sets no cookies, and performs no tracking.

## The vendored widget

`public/altcha.min.js` is the upstream ALTCHA widget, vendored as-is under the
MIT license. Its behavior is documented separately from this plugin's PHP.

The following is **upstream widget behavior**, established from an audit of the
widget source rather than from this repository's PHP: the widget enforces its
own attribution — ignoring `hidefooter` / `hidelogo` — only when it detects
"free SaaS" usage, i.e. a challenge URL on `*.altcha.org` carrying
`apiKey=ckey_`. Because this plugin never produces such a URL, `hidefooter` and
`hidelogo` always take effect in this plugin's context.

## Invariants for future maintainers (and AI agents)

These guard against mistakes that have actually been made while working on this
code:

- **`custom` mode is not the paid SaaS.** Do not remove it. It is the legitimate
  self-hostable backend path and is load-bearing for real users (e.g. operators
  running their own classifying backend).
- **The verification dispatch keys on payload shape, not on the API mode.** Any
  change to mode handling must not alter how a valid or invalid challenge is
  verified — breaking this breaks every protected form.
- **Do not reintroduce any external-service dependency** (API keys, regional
  endpoints) **or visitor-IP collection.** "No external service" is a core
  promise of the fork.
- **Do not edit or rename `public/altcha.min.js`.** It is the vendored upstream
  widget; treat it as a third-party dependency. The MIT license permits
  modification, but edits would be lost when the widget is re-vendored on
  upgrade, and changing it is out of scope.
