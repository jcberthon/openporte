# `public/altcha.min.js` — upstream tracking

Vendored from [`altcha-org/altcha`](https://github.com/altcha-org/altcha).
MIT-licensed at time of last upgrade (verify on each upgrade).
Current version tracked by `OPENPORTE_WIDGET_VERSION` in `openporte.php`.

## Upgrade procedure

1. Confirm upstream release is still under an OSI-approved OSS license.
2. Replace the file from the upstream release.
3. Update `OPENPORTE_WIDGET_VERSION`.
4. Add a changelog entry in `readme.txt`.
5. Update the "Last verified MIT upstream" line below.

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

Last verified MIT upstream: `<sha or version>` on `<date>`.