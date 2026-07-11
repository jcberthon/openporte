# `public/altcha.min.js` — upstream tracking

Vendored from [`altcha-org/altcha`](https://github.com/altcha-org/altcha).
MIT-licensed at time of last upgrade (verify on each upgrade).
Current version tracked by `OPENPORTE_WIDGET_VERSION` in `openporte.php`.

## Upgrade procedure

1. Confirm upstream release is OSI-approved OSS; update "Upstream provenance ledger"
   section (`docs/agents/altcha-upstream.md#Upstream-provenance-ledger`).
2. Replace `public/altcha.min.js` from the upstream release, use
   `npm run altcha:update -- X.Y.Z`.
3. Bump `OPENPORTE_WIDGET_VERSION` (`openporte.php:57`) — the `ALTCHA_WIDGET_VERSION`
   alias at `openporte.php:69` derives from it (no separate edit). This moves
   independently of `OPENPORTE_VERSION` (`docs/release-preparation.md#Phase 0-—-Pre-flight`).
4. Add a changelog entry in `readme.txt` (WP.org `= X.Y.Z =` format, newest first;
   prior convention e.g. `readme.txt:214` `* ALTCHA Widget 2.2.2`).
5. Update the vendored-version reference in `docs/security-audit.md:475`.
6. Run the integration test checklist (widget renders; self-hosted challenge
   fetch + solve; PHP `verify()` accepts a valid token; tampered token rejected;
   `script.js` de-dup still works; attribute-API compatibility).
7. **Record provenance** — capture the SHA-256 of the re-vendored
   `public/altcha.min.js` plus the upstream source ref (tag/commit) in
   `docs/agents/altcha-upstream.md`, so the shipped build is independently verifiable
   on every future upgrade.


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
- `v2.2.2` (81e92af) on 2025-09-09 - dca232f0f5ae3d5e32c63aaf66a6aa9ae33543993d8397c011ea6ccc4650c8c6
