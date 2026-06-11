# Security Audit

> Maintainer / contributor reference. This document records a security review of
> the OpenPorte Spam Protection plugin and the remediation taken for each
> finding. It complements `docs/architecture.md` (how verification works) and
> `AGENTS.md` (coding conventions). It is not user-facing.
>
> Scope reviewed: all PHP under `openporte.php`, `includes/`, `admin/`,
> `public/`, `integrations/` (including the `formidable/`, `gravityforms/` and
> `elementor/` subdirectories), `uninstall.php`, and the first-party JavaScript
> in `public/` (`script.js`, `custom.js`, `admin.js`). The vendored
> `public/altcha.min.js` is treated as a third-party dependency and was not
> audited line by line.
>
> Audited version: 1.27.1. A prior review of the pre-fork v1.26.3 lives in
> `local/Security_Analysis.md`; several of its findings are now obsolete
> (`get_ip_address()` and the paid-SaaS challenge URL it flagged were removed in
> the 1.27.0 paid-SaaS removal, and the widget-attribute escaping it flagged is
> now handled by `esc_attr()` in `render_widget()`).

## Summary

Baseline WordPress hygiene is strong:

- **Input** is read as `sanitize_text_field( wp_unslash( $_POST[...] ) )`
  everywhere a superglobal is touched.
- **Output** is escaped for context — `esc_html()` / `esc_attr()` on the admin
  page, and `wp_kses()` with the `OpenPortePlugin::$html_espace_allowed_tags`
  whitelist for every rendered widget.
- **Admin settings** go through the WordPress Settings API, which enforces the
  capability (`manage_options`) and the `options.php` nonce on save; every
  registered option has a `sanitize_callback`.
- **Direct access** is blocked by an `ABSPATH` (or `WP_UNINSTALL_PLUGIN`) guard
  in every PHP file.
- **Crypto** uses `random_bytes()` / `random_int()` for secrets and the PoW
  secret number, and `hash_equals()` for constant-time HMAC comparison.
- The single direct DB query (uninstall cleanup) uses `$wpdb->prepare()` with
  `$wpdb->esc_like()`.

No SQL injection, stored/reflected XSS, missing-capability, or broken-nonce
issues were found. The substantive weaknesses are in the **challenge-verification
logic**; the rest are low-severity hardening items.

| # | Title | Type | Location | Risk | Status |
|---|-------|------|----------|------|--------|
| 1 | Solved tokens are not single-use (replay) | Replay | `verify()` / `verify_solution()` | Medium | Accepted (documented) |
| 2 | Server-signature path skips `expire` / `verified` | Replay / weak verification | `verify_server_signature()` | Medium | **Fixed** |
| 3 | Decoded payload not validated before use | Input validation / robustness | `verify()` + sub-methods | Low | **Fixed** |
| 4 | Broken `autocomplete` attribute on settings inputs | Info exposure / best practice | `admin/options.php` | Low | **Fixed** |
| 5 | No rate limiting on the public challenge endpoint | DoS / abuse | REST route | Low | Accepted (documented) |
| 6 | No HTTPS enforcement on the custom challenge URL | MITM (operator-controlled) | `get_challengeurl()` / settings | Info | Accepted (documented) |
| 7 | Signing secret stored in plaintext in `wp_options` | Hardening | options | Info | Accepted (documented) |
| 8 | Form handlers add no nonce of their own | By design | `integrations/*` | Info | Accepted (documented) |

---

## Findings

### 1. Solved tokens are not single-use (replay) — Medium — Accepted

**Type:** Replay attack / anti-automation bypass.
**Location:** `includes/core.php`, `verify()` → `verify_solution()` (and the
`verify_server_signature()` path).

`verify_solution()` validates the algorithm, the challenge hash, the HMAC
signature, and — when the salt carries an `?expires=` parameter — the expiry. It
keeps **no record of which solutions have already been accepted**. The same
base64 `altcha` payload therefore verifies successfully on every submission
until it expires. With the default expiry of 1 hour (and "None" = never), a bot
can solve one proof-of-work and then replay that single token across unlimited
submissions, defeating the anti-spam purpose.

> Note: the 1.26.3 changelog entry "Fixed possible replay attacks via salt
> splicing" refers to a narrower salt-parsing bug, not to one-time-use.

**Reference fix (not applied):** store a hash of the accepted payload's
`signature` in a transient keyed to the remaining validity window, and reject a
payload whose signature is already present — with per-request memoisation so a
token legitimately verified twice within one request (e.g. the dual
`authenticate` registration in `wordpress.php` / `woocommerce.php`) still
passes.

**Decision — Accepted, not fixed.** A stateful one-time-use store has a
real false-rejection risk: when a submission fails for an unrelated reason (e.g.
"username already taken") and the visitor resubmits, the still-valid token would
be rejected as a replay unless the widget re-solves on re-render (it usually
does, but not in every configuration). The maintainer chose to accept this as a
known limitation of stateless proof-of-work rather than risk breaking legitimate
resubmissions. Mitigating factors: the default 1-hour expiry bounds the replay
window, and proof-of-work raises the per-token cost. Operators wanting stricter
behaviour should keep the expiry short (avoid "None").

---

### 2. Server-signature verification skips `expire` / `verified` — Medium — Fixed

**Type:** Replay / weakened verification (custom-backend / spam-filter mode).
**Location:** `includes/core.php`, `verify_server_signature()`.

When a `custom` backend returns a server-signed payload, the original code
verified only the HMAC signature and then returned `true` whenever
`classification !== 'BAD'`. It did **not** check the `expire` timestamp or the
`verified` flag carried in `verificationData`. Two consequences:

1. **No expiry** — a captured server-signed payload is accepted forever (replay),
   even though the proof-of-work path (`verify_solution()`) already enforces
   expiry. The ALTCHA reference implementation requires
   `verified === true && expire > now`.
2. **`verified` ignored** — a payload the backend explicitly marked *not* verified
   would still be accepted as long as `classification` was not the literal `BAD`.

Additionally, reading `$this->spamfilter_result['classification']` without an
`isset()` guard emits a PHP warning and fails open (treats a missing
classification as "not BAD") when the key is absent.

**Fix applied.** After the HMAC check passes, parse `verificationData` and:

- reject when `expire` is present, numeric, and in the past;
- reject when `verified` is present and falsy (`''`, `0`, `false`, `no`);
- guard the `classification` read with `isset()`.

The `expire` / `verified` checks are **defensive (only-when-present)** so a
minimal custom backend that omits those fields is not broken, while the
reference backend (which sends both) gets the stricter behaviour. The
load-bearing raw-binary `true` flag on `hash('sha256', …, true)` is preserved
(removing it breaks all verification — see `AGENTS.md`).

```php
public function verify_server_signature($payload, $hmac_key = null)
{
  if ($hmac_key === null) {
    $hmac_key = $this->get_secret();
  }
  $data = $this->decode_payload($payload);
  // Guard the payload shape before touching properties (see finding #3).
  if ($data === null || !isset($data->algorithm, $data->verificationData, $data->signature)) {
    return false;
  }
  $alg_ok = ($data->algorithm === 'SHA-256');
  // The raw-binary (true) flag is load-bearing; removing it breaks verification.
  $calculated_hash = hash('sha256', $data->verificationData, true);
  $calculated_signature = hash_hmac('sha256', $calculated_hash, $hmac_key);
  // hash_equals: constant-time comparison so the HMAC can't be recovered via timing.
  if (!($alg_ok && hash_equals($calculated_signature, $data->signature))) {
    return false;
  }
  $this->spamfilter_result = array();
  parse_str($data->verificationData, $this->spamfilter_result);
  // Mirror verify_solution() and the ALTCHA reference (verified === true &&
  // expire > now). Checked only when the backend supplies the field, so minimal
  // custom backends that omit them keep working.
  if (isset($this->spamfilter_result['expire'])) {
    $expire = intval($this->spamfilter_result['expire'], 10);
    if ($expire > 0 && $expire < time()) {
      return false;
    }
  }
  if (isset($this->spamfilter_result['verified'])) {
    $verified_flag = strtolower((string) $this->spamfilter_result['verified']);
    if (in_array($verified_flag, array('', '0', 'false', 'no'), true)) {
      return false;
    }
  }
  return !isset($this->spamfilter_result['classification'])
    || $this->spamfilter_result['classification'] !== 'BAD';
}
```

---

### 3. Decoded payload not validated before use — Low — Fixed

**Type:** Input validation / robustness.
**Location:** `includes/core.php`, `verify()`, `verify_solution()`,
`verify_server_signature()`.

The verification methods decoded the submitted token as
`json_decode( base64_decode( $payload ) )` and then read properties
(`$data->algorithm`, `$data->salt`, …) without checking that decoding produced a
valid object. A non-base64 or non-JSON `altcha` value yields `false` / `null`,
so every property access emits a PHP warning ("Attempt to read property … on
null"). The result still fails closed, but the warnings pollute the log and, on
strict configurations, can interfere with output.

**Fix applied.** A private `decode_payload()` helper performs strict decoding;
`verify()` dispatches only on a valid object, and each sub-method guards the
properties it needs with `isset()` so it stays self-contained when called
directly.

```php
private function decode_payload($payload)
{
  if (!is_string($payload) || $payload === '') {
    return null;
  }
  $decoded = base64_decode($payload, true); // strict: reject non-base64 input
  if ($decoded === false) {
    return null;
  }
  $data = json_decode($decoded);
  if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
    return null;
  }
  return $data;
}
```

`verify_solution()` gains
`if ($data === null || !isset($data->algorithm, $data->salt, $data->number, $data->challenge, $data->signature)) { return false; }`
at the top; `verify_server_signature()` gains the equivalent guard shown in
finding #2. Behaviour on valid payloads is unchanged; malformed input still
fails closed, now without warnings.

---

### 4. Broken `autocomplete` attribute on settings inputs — Low — Fixed

**Type:** Information exposure / WordPress best practice.
**Location:** `admin/options.php`, `openporte_settings_field_callback()`.

The shared settings-field `<input>` carried `autcomplete="none"` — a misspelling
of `autocomplete`, so the intended autocomplete suppression never took effect.
Because the same callback renders the **Signing secret** field as a plain text
input with its value pre-filled, browsers and password managers could capture
and re-offer the HMAC signing secret.

**Fix applied.** Correct the attribute to `autocomplete="off"`. (The field
remains `type="text"`: the admin needs to read and copy the secret, and the
value is only ever exposed on the `manage_options`-gated settings page. The
plaintext-in-DB aspect is finding #7.)

```php
<input autocomplete="off" class="regular-text" ...>
```

---

### 5. No rate limiting on the public challenge endpoint — Low — Accepted

**Type:** Denial of service / abuse.
**Location:** `includes/core.php`, the `rest_api_init` route with
`permission_callback => '__return_true'`, and `openporte_generate_challenge_endpoint()`.

`GET /wp-json/openporte/v1/challenge` (and the deprecated `altcha/v1` alias) is
unauthenticated and uncapped. Each call runs `random_int()` plus a couple of
hashes and returns a fresh signed challenge.

**Decision — Accepted, not fixed.** The endpoint *must* be publicly reachable
for the CAPTCHA to function. The usual mitigation, per-IP throttling via
transients, would require reading `REMOTE_ADDR` and so conflicts with the
plugin's explicit "collects no visitor IP address" privacy promise (see
`docs/architecture.md` → Privacy stance). The per-request cost is low and
proof-of-work already prevents forging a *valid* solution, so the residual risk
is generic request-flooding, best handled at the web-server / WAF / caching
layer rather than in-plugin. Operators who need it can rate-limit the route
upstream.

---

### 6. No HTTPS enforcement on the custom challenge URL — Info — Accepted

**Type:** Man-in-the-middle (operator-controlled configuration).
**Location:** `includes/core.php`, `get_challengeurl()`; the
`openporte_api_custom_url` option (sanitised by `openporte_sanitize_challenge_url()`
with `esc_url_raw()`, which permits `http://`).

In `custom` mode the operator supplies the challenge backend URL. It is not
forced to HTTPS, so a misconfigured `http://` backend would expose challenges
and solutions in transit.

**Decision — Accepted, not fixed.** The value is set by an administrator, not a
visitor, and forcing HTTPS would break legitimate local/development backends
(`http://localhost`, container hostnames). A future UI hint recommending HTTPS
is preferable to a hard block. `esc_url_raw()` already strips dangerous schemes
(e.g. `javascript:`), so there is no injection vector — only a configuration
recommendation.

---

### 7. Signing secret stored in plaintext in `wp_options` — Info — Accepted

**Type:** Hardening / defence in depth.
**Location:** activation (`openporte_activate()`), `OpenPortePlugin::$option_secret`.

The HMAC signing secret is stored unencrypted in `wp_options`. A database
compromise lets an attacker forge valid challenges and solutions.

**Decision — Accepted.** This is standard for WordPress plugins that need a
server-side secret available on every request; there is no materially better
store without a custom encryption-key-management scheme, and the key itself
would still have to live somewhere readable by PHP. The secret is generated with
`random_bytes()`, never sent to the frontend, and is only displayed on the
`manage_options`-gated settings page. Accepted as residual risk.

---

### 8. Form handlers add no nonce of their own — Info — Accepted

**Type:** CSRF (by design).
**Location:** all `integrations/*` POST handlers (each annotated with
`// phpcs:ignore WordPress.Security.NonceVerification.Missing`).

The integration handlers read `$_POST['altcha']` (or `openporte_register`)
without verifying a nonce of their own.

**Decision — Accepted, by design.** Each handler hooks into the host flow's own
processing (`register_post`, `authenticate`, `preprocess_comment`,
`wpcf7_spam`, Gravity Forms / Elementor / Formidable field validation, …), which
already performs that plugin's CSRF handling. The OpenPorte payload is itself an
unforgeable, HMAC-signed anti-automation token — adding a separate nonce would
be redundant and, for the public login/register/comment flows, is not how
WordPress core gates those endpoints. The dispatch reads are presence/value
checks only and are fully sanitised.

---

## Maintainer decisions on record

- **Finding 1 (replay / one-time-use):** documented as an accepted limitation;
  no behavioural change, to avoid the resubmission false-rejection risk.
- **Finding 5 (REST rate limiting):** documented as accepted risk; per-IP
  throttling rejected because it conflicts with the no-visitor-IP privacy
  promise. Handle upstream if needed.
