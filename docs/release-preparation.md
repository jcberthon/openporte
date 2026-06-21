# Release Preparation

A step-by-step runbook for cutting an OpenPorte release. Work through the phases
in order; each one gates the next.

> Conventions referenced here: the branching model, versioning policy, and
> patch/minor lifecycle live in `../CONTRIBUTING.md` (this runbook covers the
> *mechanics*, not the policy); the version-bump locations and the
> static-analysis / verification protocol live in `AGENTS.md`; the manual
> acceptance steps live in `docs/acceptance/`; the i18n discipline is in
> `AGENTS.md` → "i18n discipline". This document ties them into one release flow.

## Phase 0 — Pre-flight

1. **Branch.** `main` is protected — never commit the release prep directly to
   it. Create a release branch off an up-to-date `main`
   (e.g. `git switch -c release/1.27.2`).
2. **Decide the version number** using semantic versioning:
   - **patch** (`x.y.Z`) — bug/security fixes, no behaviour change for users;
   - **minor** (`x.Y.0`) — new integration or opt-in feature, backward compatible;
   - **major** (`X.0.0`) — breaking change (e.g. dropping a PHP/WP version, or
     removing a public hook/symbol past its deprecation window).
   - The bundled ALTCHA **widget** has its own version (`OPENPORTE_WIDGET_VERSION`)
     that moves independently — bump it only when `public/altcha.min.js` is
     re-vendored (see `docs/agents/altcha-upstream.md`).
3. **Clean working tree.** Ensure no stray build artifacts are present
   (a leftover `openporte.zip` at the repo root, editor temp files, etc.).
   `git status` should show only the changes you intend to ship. Note: the
   release archive is built from the working tree filtered by `.distignore`,
   **not** from git — anything on disk that `.distignore` does not exclude will
   end up in the published zip.

## Phase 1 — Version bump

Bump the version in **every** location below in the same commit, or the plugin
ships inconsistently (the WordPress.org "Stable tag" must match the plugin
header, and `OPENPORTE_VERSION` busts the asset cache):

| File | Field |
|---|---|
| `openporte.php` | `* Version:` (plugin header) |
| `openporte.php` | `* Stable tag:` (plugin header) |
| `openporte.php` | `define('OPENPORTE_VERSION', '…')` |
| `readme.txt` | `Stable tag:` |
| `readme.txt` | new `= X.Y.Z =` changelog section (Phase 3) |

If the WordPress "Tested up to" ceiling changed since the last release, update
`Tested up to:` in both `readme.txt` and the plugin header too.

## Phase 2 — Translations (only if user-facing strings changed)

Skip this phase if the release touched no translatable strings. Otherwise the
`.pot` template and the per-locale `.po`/`.mo` files must be regenerated
(29 locales are currently shipped under `languages/`). For the do-not-translate
glossary, the per-locale fill-in procedure, and the LLM-assisted translation
prompt, see `docs/agents/i18n.md`.

Please note that we exclude public/altcha.min.js (vendored, not your strings) and
other directories on purpose. 

```bash
# 1. Regenerate the POT template
wp i18n make-pot . languages/openporte.pot \
  --domain=openporte \
  --exclude=vendor,local,tests,public/altcha.min.js \
  --slug=openporte

# 2. Merge the new POT into each locale (fuzzy-matching), then stamp the version.
#    A reworded/typo-fixed msgid keeps its old translation, now flagged "#, fuzzy".
for po in languages/openporte-*.po; do
  msgmerge --update --backup=none --previous "$po" languages/openporte.pot
done
sed -i '' \
  's/Project-Id-Version: OpenPorte Spam Protection [0-9.]*/Project-Id-Version: OpenPorte Spam Protection X.Y.Z/' \
  languages/openporte-*.po

# 3. Compile the MO binaries (msgfmt excludes "#, fuzzy" entries → English fallback
#    until a translator reviews them; --check also gates syntax + placeholder errors).
for po in languages/openporte-*.po; do
  msgfmt --check "$po" -o "${po%.po}.mo"
done
```

> Replace `X.Y.Z` with the release version. On Linux, drop the `''` argument to
> `sed -i` (that empty backup suffix is a BSD/macOS requirement). The merge/compile
> steps use GNU gettext (`msgmerge`/`msgfmt`) rather than `wp i18n update-po`/`make-mo`:
> only `msgmerge` preserves a translation when its `msgid` changes (marking it `#, fuzzy`),
> and only `msgfmt` keeps fuzzy entries out of the `.mo`. See `docs/agents/i18n.md` for why.

## Phase 3 — Changelog and upgrade notice

In `readme.txt`:

1. Add a `= X.Y.Z =` entry under `== Changelog ==`. Don't reconstruct it from
   `git log` — pull the "Changelog entry" bullet straight out of the
   description of each PR merged since the last release tag (every PR's
   description has one; see the PR template and `CONTRIBUTING.md` →
   "Commits and pull requests"). Credit contributors as the existing entries
   do. The same bullets are the GitHub release notes for this version.
2. If users need to *do* or *know* something on upgrade (a behaviour change, a
   required reconfiguration, a deactivate-the-old-plugin step), add a matching
   `= X.Y.Z =` block under `== Upgrade Notice ==`.
3. Call out anything a translator needs to refresh (per the i18n discipline) and
   any removed public symbol (so third-party integrators are warned).

## Phase 4 — Static analysis and syntax

Per `AGENTS.md` → `docs/agents/static-analysis.md`:

- **Blocking:** `php -l` on every changed PHP file; `bash -n` on changed shell
  scripts. Fix any syntax error before continuing.
- **Informative:** if installed, run `phpstan analyse` and `phpcs` (the repo
  carries `wp-coding-standards/wpcs` as a dev dependency) and review the output.
  These do not block the release, but unexplained new findings should be
  understood or suppressed with a documented `phpcs:ignore`.

## Phase 5 — Validation (manual acceptance)

There is no automated test suite — validate by hand on the `wp-env` bench (see
`docs/maintenance-testing.md`).

1. **Regression suite.** Run acceptance tests **(a)**, **(b)** and **(c)** from
   `docs/acceptance/` (self-hosted submit-and-verify; custom-mode Challenge URL
   toggle; clean `wp-env logs` / browser console). Add or update an acceptance
   doc for the new version if the behaviour changed.
2. **Upgrade scenario (d).** Exercise the ALTCHA → OpenPorte migration and the
   legacy-value graceful degradation (see `tests/README.md`).
3. **Compatibility matrix (e).** Spot-check the supported PHP/WordPress floor and
   ceiling (currently **PHP 8.0 / WP 5.6** up to **PHP 8.5 / WP 7.0** — see
   `docs/maintenance-testing.md`).
4. **WordPress Plugin Check.** Run the
   [Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin against the
   build on a **WordPress 6.3+** bench and resolve or justify (with a documented
   `phpcs:ignore`) every flagged item.
5. **Widget integration.** If `public/altcha.min.js` was re-vendored this
   release, run the widget integration checks in `docs/maintenance-testing.md`
   → "The `altcha.min.js` widget dependency".

## Phase 6 — Documentation verification

- `readme.txt`: changelog and (if needed) upgrade notice added; `Stable tag`,
  `Requires`, and `Tested up to` are correct.
- `docs/`: anything the release changed is reflected — `architecture.md` for
  behavioural changes, `docs/security-audit.md` for security-relevant ones,
  `docs/maintenance-testing.md` for supported-version or testing changes.
- `docs/agents/altcha-upstream.md`: "Last verified MIT upstream" updated if the
  widget was upgraded.

## Phase 7 — Build and publish

The release branch is merged into `main` via PR, then a tag triggers the
automated WordPress.org deploy.

```bash
# 1. After the PR is merged on GitHub, fast-forward local main:
git switch main
git fetch origin
git merge --ff-only origin/main

# 2. Tag main (now at the merge commit) and push the tag:
VERSION="vX.Y.Z"
git tag -a "${VERSION}" -m "Release ${VERSION}"
git push origin "${VERSION}"
```

The tag name must be `vX.Y.Z`. If you enabled the `pre-push` hook
(`git config core.hooksPath .githooks`, see
[`CONTRIBUTING.md`](../CONTRIBUTING.md)), a malformed tag is rejected locally
before it leaves your machine. The workflow validates it again server-side and
checks it against the readme `Stable tag` before deploying.

Pushing the tag triggers `.github/workflows/publish.yml`, which builds the
package (honouring `.distignore`) and deploys it straight to the WordPress.org
SVN repository using the `SVN_*` secrets. No manual SVN steps are needed.

**Optional — produce a local/archival zip** (e.g. to attach to a GitHub
release), using the same `.distignore` filtering the deploy uses:

```bash
wp dist-archive .
```

## Post-release

- Confirm the new version appears on the
  [WordPress.org plugin page](https://wordpress.org/plugins/openporte/) and that
  the deploy workflow run succeeded.
- Verify a clean install/upgrade from the published package on a fresh bench.
- If a widget upgrade shipped, re-check the "Last verified MIT upstream" note is
  recorded.
