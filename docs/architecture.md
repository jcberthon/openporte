# ALTCHA Spam Protection — Architecture

This document describes the architecture of the ALTCHA Spam Protection WordPress plugin (project intended name: OpenPorte). It is a reference for maintainers and contributors.

## Overview and modes

The plugin operates in two modes, both fully self-contained with no external service dependencies:

- **selfhosted** (default): Proof-of-work. Challenges are generated and served by a WordPress REST endpoint at `wp-json/altcha/v1/challenge`. No API key or external service is required.
- **custom**: The challenge URL points to a backend that the site owner runs themselves. Responses from this backend are verified using the site's own HMAC secret.

The mode is selected via the `altcha_api` option. When set to `custom`, the plugin uses the URL stored in `altcha_api_custom_url`; for any other value (including legacy database values such as `eu` or `us`), it falls back to the local REST endpoint (`get_challengeurl()`: `includes/core.php:273-279`).

## Verification dispatch

Verification is dispatched based on the **shape of the decoded payload**, not the configured mode. In `verify()` (`includes/core.php:353-371`), the plugin checks the base64-decoded JSON payload: if it contains a `verificationData` field, it invokes `verify_server_signature()`; otherwise, it invokes `verify_solution()` for proof-of-work verification.

`verify_server_signature()` (`includes/core.php:375-392`) verifies the HMAC signature against the site's secret (retrieved via `get_secret()`), then parses `verificationData` into `$spamfilter_result` and returns `true` if the `classification` field is not `BAD`.

`verify_solution()` performs proof-of-work verification: it validates the challenge hash, signature, and expiration, returning `true` only if all checks pass.

## What was removed and why

The paid altcha.org regional SaaS classifier was removed to keep the plugin free and self-hosted, with no dependency on external services. The following were removed:

- Regional SaaS modes (`eu`/`us`) and their API key requirement
- `$option_api_key` option and `get_api_key()` method
- The regional branch of `get_challengeurl()` that constructed URLs to `https://{region}.altcha.org`
- `spam_filter_check()` and `spam_filter_call()` methods that POSTed to `https://{region}.altcha.org/api/v1/classify`
- `$option_send_ip` option, `$hostname` property, and `get_ip_address()` method

None of these symbols exist in the current codebase.

## Spam filter — status and limits

**The plugin provides no spam classifier.** The classifier engine was a hosted ALTCHA service (commercial Sentinel) and was never open-source.

What remains is consumer-side plumbing that *can* act on classification data if a custom backend provides it:

- `verify_server_signature()` reads a classification from the signed `verificationData` payload (`includes/core.php:387-389`)
- `get_blockspam()` option and the widget attribute `blockspam='1'` (`includes/core.php:163-165` and `includes/core.php:503-504`) enable client-side spam filtering behavior
- The Gravity Forms integration checks `$spamfilter_result['classification']` and uses the `score` and `reasons` fields if present (`integrations/gravityforms.php:28,34-36`)

This plumbing only has an effect if a **custom** backend returns classification data in its signed response. It has **no effect** in self-hosted proof-of-work mode, and the plugin does not ship with any classifier functionality.

## Privacy stance

In both supported modes, the plugin:

- Requires no API key
- Makes no calls to external paid services
- Collects no visitor IP addresses (`get_ip_address()` was removed)
- Sets no cookies
- Performs no tracking

## The vendored widget

`public/altcha.min.js` is the upstream ALTCHA widget, vendored as-is under the MIT license. Its behavior is documented separately from this plugin's PHP code.

Per an external audit of the widget source: the widget enforces its own attribution (ignoring `hidefooter`/`hidelogo` settings) **only** when it detects "free SaaS" usage — specifically, when the challenge URL is on `*.altcha.org` and carries `apiKey=ckey_`. Because this plugin never uses such URLs or API keys, the `hidefooter` and `hidelogo` widget attributes always take effect in this plugin's context.

## Invariants for future maintainers and AI agents

- **"custom" mode is not the paid SaaS.** It is the legitimate self-hostable backend path. Do not remove it; it is load-bearing for real users.
- **Verification dispatch keys on payload shape, not on the API mode.** Changes to mode handling must not alter how a valid or invalid challenge is verified.
- Do not reintroduce any external-service dependency (API keys, regional endpoints, etc.) or IP collection.
- Do not edit or rename `public/altcha.min.js`. It is vendored code under MIT license; modifications would break the license terms and are unnecessary.
