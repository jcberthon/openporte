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
>
> A second pass maps the code against two external frameworks — the
> [WordPress Plugin Security Guidelines](https://developer.wordpress.org/plugins/security/)
> and the [OWASP Top 10 (2021)](https://owasp.org/Top10/) plus the
> [OWASP PHP Configuration / Input-Validation cheat sheets](https://cheatsheetseries.owasp.org/).
> The per-guideline coverage tables are Appendix A and Appendix B; the framework
> pass surfaced no new Medium/High issues but added the Info/Low observations
> #9–#11 below.

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
| 9 | HMAC signing key is 96-bit entropy | Crypto strength (defence in depth) | `random_secret()` | Low | **Fixed** |
| 10 | Formidable autoloader regex does not block path separators | Path traversal (theoretical) | `integrations/formidable.php` | Low | **Fixed** |
| 11 | Unused dead code from the removed paid-SaaS path | Attack surface / maintainability | `core.php` | Info | **Fixed** |
| 12 | Inline-script JSON not hardened against `</script>` breakout | XSS (defence in depth) | `integrations/custom.php` | Info | **Fixed** |

> Findings 9–11 came from the framework review (Appendix A/B). None was
> exploitable; each was a non-breaking hardening and has now been applied.
> Finding 12 came from a follow-up hardening pass.

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

### 9. HMAC signing key is 96-bit entropy — Low — Fixed

**Type:** Cryptographic strength / defence in depth (OWASP A02).
**Location:** `includes/core.php`, `random_secret()` (used to seed
`OpenPortePlugin::$option_secret` at activation).

`random_secret()` returns `bin2hex(random_bytes(12))` — 12 random bytes, i.e.
**96 bits of entropy**, rendered as a 24-character hex HMAC key. The CSPRNG
(`random_bytes`) is correct, but for HMAC-SHA256 the modern minimum is 128 bits
(256 ideal). 96 bits is not practically brute-forceable, so this is hardening,
not an exploitable weakness.

**Fix applied.** `random_secret()` now returns `bin2hex(random_bytes(32))`
(256-bit). Because the secret is generated only when absent (`add_option` is a
no-op when set), existing installs keep their current key and previously issued
challenges keep verifying — only fresh installs get the stronger key.

---

### 10. Formidable autoloader regex does not block path separators — Low — Fixed

**Type:** Path traversal / file inclusion (theoretical) (OWASP A03).
**Location:** `integrations/formidable.php`, `openporte_forms_autoloader()`.

```php
if ( ! preg_match( '/^OpenPorte.+$/', $class_name ) ) { return; }
$filepath = dirname( __FILE__ ) . '/formidable/' . $class_name . '.php';
if ( file_exists( $filepath ) ) { require( $filepath ); }
```

The `.+` in the guard matches any character, including `/`, `\` and `.`, so the
class name is concatenated into the include path without a path-separator check.
This is **not practically exploitable** — the value is a PHP class name supplied
by the autoload mechanism, not by request input, and PHP class names cannot
contain `/` — but it is looser than necessary.

**Fix applied.** The guard is now `^OpenPorte[A-Za-z0-9_]+$`, which rejects path
separators and dots before the class name is concatenated into the include path.

---

### 11. Unused dead code from the removed paid-SaaS path — Info — Fixed

**Type:** Attack surface / maintainability (OWASP A04 Insecure Design).
**Location:** `includes/core.php` — `flatten_post()`, `sanitize_data()`,
`remove_private_keys()`.

These public methods are defined but never called anywhere in the plugin
(confirmed by grep). They are leftovers from the removed paid-SaaS classifier,
which flattened and POSTed form data to the external API. Dead code is not a
vulnerability, but removing unreachable code shrinks the attack surface and the
maintenance burden.

**Fix applied.** The three methods were deleted. Minor caveat: they were
`public`, so third-party code could in theory have called them — unlikely, but
worth a changelog note when this ships in a release.

### 12. Inline-script JSON not hardened against `</script>` breakout — Info — Fixed

**Type:** Cross-site scripting (defence in depth).
**Location:** `integrations/custom.php`, the `wp_add_inline_script()` that exposes
`window.OPENPORTE_WIDGET_ATTRS`.

The widget attributes were encoded with `wp_json_encode()` (no flags) and printed
verbatim inside a `<script>` block. `wp_json_encode()` does not escape `<`, `>` or
`&`, so a value containing the literal `</script>` would close the script element
early and allow HTML injection.

**Not exploitable in practice:** every attribute is admin- or developer-supplied
(`challengeurl` passes through `esc_url_raw()`, which strips `<`/`>`; `strings`
comes from translations; `name` is a code literal), with no visitor-controlled
path. Recorded as defence in depth.

**Fix applied.** Encode with the script-context flags so the output cannot break
out of the `<script>` element:

```php
$attrs = wp_json_encode(
  $plugin->get_widget_attrs($mode),
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
```

---

## Appendix A — WordPress Plugin Security Guidelines coverage

Mapped against <https://developer.wordpress.org/plugins/security/>.

| Guideline | Status | Evidence |
|---|---|---|
| Sanitize inputs | Pass | `sanitize_text_field( wp_unslash( … ) )` on every `$_POST` read; no `$_GET`/`$_REQUEST`/`$_COOKIE`/`$_SERVER` reads (grep-confirmed) |
| Validate data | Pass (hardened) | Token validation added in finding #3; every `register_setting` has a `sanitize_callback` |
| Escape output | Pass | `esc_html()`/`esc_attr()` on admin pages; `wp_kses()` with the `$html_espace_allowed_tags` whitelist for all widget HTML |
| Nonces / CSRF | Pass / by design | Save path uses the Settings API nonce; public form handlers delegate to the host flow (finding #8) |
| Capability checks | Pass | `add_options_page( …, 'manage_options', … )`; Settings API enforces the cap on save; comment handler skips for `manage_options` |
| Avoid direct file access | Pass | `ABSPATH` (or `WP_UNINSTALL_PLUGIN`) guard at the top of every PHP file |
| Prepared SQL | Pass | Only direct query is `uninstall.php`, using `$wpdb->prepare()` + `$wpdb->esc_like()` |
| Secure REST endpoints | Pass (permissive by design) | `permission_callback` present; intentionally public for challenge generation (finding #5) |
| No dynamic file inclusion from input | Pass | All `require`/`include` are static literals; the one variable include is the guarded Formidable autoloader (finding #10) |
| No `eval`/`system`/`unserialize`/`extract` | Pass | grep-confirmed absent |
| Don't trust proxy/`$_SERVER` headers | Pass | No `$_SERVER` reads; `get_ip_address()` removed in 1.27.0 |

## Appendix B — OWASP coverage

OWASP Top 10 (2021):

| Category | Status | Notes |
|---|---|---|
| A01 Broken Access Control | Pass | Admin gated by `manage_options`; the only public surface (challenge REST) is intentional and exposes no secret |
| A02 Cryptographic Failures | Pass (note) | `random_bytes`/`random_int` CSPRNG, HMAC-SHA256, constant-time `hash_equals`; key length is finding #9 |
| A03 Injection | Pass | SQL prepared; no command/code injection sinks; output escaped; autoloader note in finding #10 |
| A04 Insecure Design | Pass (note) | Stateless-PoW replay accepted (finding #1); dead code in finding #11 |
| A05 Security Misconfiguration | Pass (note) | Secure defaults; no debug output; PoW complexity has no seeded default → falls to the 100–10000 range (consider defaulting to medium/high) |
| A06 Vulnerable & Outdated Components | Monitor | Vendored `public/altcha.min.js` (widget 2.2.2) — track upstream advisories on re-vendor |
| A07 Identification & Auth Failures | Pass | Delegates to WP/WooCommerce auth; adds an anti-automation layer, does not weaken auth |
| A08 Software & Data Integrity Failures | Pass | Uses `json_decode` (not `unserialize`); submitted tokens are HMAC-signed and verified |
| A09 Security Logging & Monitoring | Note | No built-in logging; operators can hook the `openporte_verify_result` action |
| A10 SSRF | Pass | No server-side `wp_remote_*`/`file_get_contents`/cURL of operator- or visitor-supplied URLs (grep-confirmed) |

OWASP PHP cheat-sheet spot checks:

- **Type juggling:** security-critical comparisons use strict `===` (algorithm,
  challenge) and `hash_equals` (signatures); the `verified` check uses
  `in_array( …, true )`. No loose `==` in a security decision.
- **Error handling / info leak:** finding #3 removes the PHP warnings that junk
  tokens used to emit, reducing noise/leak in logs.
- **File uploads / sessions:** none — the plugin handles no uploads and sets no
  cookies/sessions.

---

## Maintainer decisions on record

- **Finding 1 (replay / one-time-use):** documented as an accepted limitation;
  no behavioural change, to avoid the resubmission false-rejection risk.
- **Finding 5 (REST rate limiting):** documented as accepted risk; per-IP
  throttling rejected because it conflicts with the no-visitor-IP privacy
  promise. Handle upstream if needed.
