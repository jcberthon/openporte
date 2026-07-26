# `public/altcha.min.js` — upstream tracking

Vendored from [`altcha-org/altcha`](https://github.com/altcha-org/altcha).
MIT-licensed at time of last upgrade (verify on each upgrade).
Current version tracked by `OPENPORTE_WIDGET_VERSION` in `openporte.php`.

## Upgrade procedure

1. Confirm upstream release is OSI-approved OSS; update "Upstream provenance ledger"
   section (`docs/agents/altcha-upstream.md#Upstream-provenance-ledger`).
2. Replace `public/altcha.min.js` from the upstream release, use
   `npm run altcha:update -- X.Y.Z`.
3. Bump the `OPENPORTE_WIDGET_VERSION` define in `openporte.php` — the
   `ALTCHA_WIDGET_VERSION` alias in the same file derives from it (no separate
   edit). This moves independently of `OPENPORTE_VERSION`
   (`docs/release-preparation.md#Phase 0-—-Pre-flight`).
4. Add a changelog entry in `readme.txt` under `== Changelog ==` (WP.org
   `= X.Y.Z =` format, newest first; prior convention e.g. the `= 1.26.0 =`
   entry's `* ALTCHA Widget 2.2.2` bullet).
5. Update the vendored-version reference in the "A06 Vulnerable & Outdated
   Components" row of the OWASP table in `docs/security-audit.md`.
6. Run the integration test checklist (widget renders; self-hosted challenge
   fetch + solve; PHP `verify()` accepts a valid token; tampered token rejected;
   `script.js` de-dup still works; attribute-API compatibility).
   Also grep the new bundle for the allowed-algorithms array
   (`grep -o '\["SHA-256[^]]*\]' public/altcha.min.js` — currently
   `["SHA-256","SHA-384","SHA-512"]`, behind a minifier-renamed identifier)
   and sync `OpenPortePlugin::get_allowed_algorithms()` if it ever changes.
7. **Record provenance** — capture the SHA-256 of the re-vendored
   `public/altcha.min.js` plus the upstream source ref (tag/commit) in
   `docs/agents/altcha-upstream.md`, so the shipped build is independently verifiable
   on every future upgrade.

## The version string inside the bundle is not authoritative

Upstream commits `dist/` to the repo and injects the version at build time
(`vite.config.ts` → `ALTCHA_VERSION: process.env.npm_package_version`), with no
`prepublishOnly`/`prepack` step. A release that does not rebuild `dist/`
therefore ships artifacts stamped with an older version.

That is the case for **2.3.0**, which vendors a bundle reporting **2.2.4**:
`git diff v2.2.4 v2.3.0 -- dist/` is empty (last commit touching `dist/` is
`5c5aa3f "2.2.4"`), and the only source change in the range is a new *empty*
`src/plugins/index.ts`. Nothing is missing and no changed code is stale.

Use `package.json` / the lockfile — and `OPENPORTE_WIDGET_VERSION` — as the
source of truth for the vendored version. Do not treat a mismatched embedded
string as a failed re-vendor; verify with the SHA-256 in the provenance ledger
instead.

## Licensing-risk contingency

If `altcha-org` relicenses to a non-OSS license (as happened with the original
WP plugin):

1. **Continue using the last MIT-licensed release indefinitely.** MIT grants
   are irrevocable for already-released code. The vendored
   `public/altcha.min.js` is our offline safety net — committed to this repo,
   recoverable even if the upstream repo is deleted.
2. **Emergency fork only if** a security issue surfaces *and* upstream refuses
   to fix it under an OSS license. Do not fork preemptively — this project
   does not have the maintainer bandwidth to own the JS library too.
3. **Track but don't depend.** Record the last MIT-licensed git SHA below.

## Upstream provenance ledger

Last verified MIT upstream (including altcha.umd.cjs SHA-256 sum):

- `v2.3.0` (8b87cf5) on 2025-12-18 - 0f557a9f535a15acbe0fc7abb4fe896502a6d4c27de1c4145cfa001d0d2be099
  - Integration verified 2026-07-13 on the floor (PHP 8.0 / WP 5.6) and ceiling
    (PHP 8.5 / WP 7.0) benches (#44, #45): all ten emitted attributes are still
    in the widget's `customElements.define` map, the light-DOM contract used by
    `public/script.js` (`.altcha[data-state]`, `input[type=checkbox]`) is
    unchanged, and `refetchonexpire` re-fetches at expiry. The upstream
    `spamfilter`/`blockspam` deprecation is documentation-only — no runtime
    warning, attributes still accepted (keep-vs-drop decision: #6).
    *(Record kept as verified. #6 later resolved to remove them, so 1.28.0
    emits **eight** attributes — `spamfilter` and `blockspam` are gone from
    `get_widget_attrs()` and the `wp_kses` whitelist. The next re-vendor should
    check eight, not ten.)*
  - 2.3.0 is pure repackaging for CVE-2025-65849 (disputed, but flagged by
    `npm audit`): the obfuscation/analytics/upload plugins moved out of `altcha`
    into a new `@altcha/plugins` package. OpenPorte uses none of them, so the
    only breaking change (`altcha/obfuscation` → `@altcha/plugins/obfuscation`)
    does not apply — the upgrade is functionally a no-op. The shipped bundle
    still reports `2.2.4`; see "The version string inside the bundle is not
    authoritative" above.
- `v2.2.2` (81e92af) on 2025-09-09 - dca232f0f5ae3d5e32c63aaf66a6aa9ae33543993d8397c011ea6ccc4650c8c6
