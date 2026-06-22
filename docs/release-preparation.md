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
   end up in the published zip. Crucially, `wp dist-archive` does **not** read
   `.gitignore` at all — only `.distignore` — so any file git ignores (via the
   repo `.gitignore` *or* a global one) still ships, and ignored files never
   appear in `git status`/`git diff` to warn you. (The v1.27.2 archival zip
   shipped a stray `.crush/` tool-state directory exactly this way.) Run
   [`tests/bin/check-dist.sh`](../tests/bin/check-dist.sh) to verify; see
   Phase 7 for when this matters most.

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

Unlike the CI deploy above (which builds from a clean checkout), this command
reads your local working tree, so anything sitting on disk that `.distignore`
doesn't exclude — including files git ignores, which never appear in
`git status` — ships in the zip. **Before uploading a locally-built zip
anywhere, verify it:**

```bash
./tests/bin/check-dist.sh
```

This flags any file in the archive that git doesn't track. It should print
nothing but an `OK` line; if it lists files, clean them up (or extend
`.distignore`) before uploading.

## Post-release

- Confirm the new version appears on the
  [WordPress.org plugin page](https://wordpress.org/plugins/openporte/) and that
  the deploy workflow run succeeded.
- Verify a clean install/upgrade from the published package on a fresh bench.
- If a widget upgrade shipped, re-check the "Last verified MIT upstream" note is
  recorded.

## Recovering from a bad GitHub Release asset

GitHub Releases in this repo are published as **immutable**: once published you
**cannot edit the tag or replace the attached assets**. So if a release zip
ships with a defect — e.g. a stray local directory that slipped past
`.distignore` (the exact failure `tests/bin/check-dist.sh` now guards against in
Phase 0/7) — you cannot fix the existing release in place.

Two facts make recovery low-risk:

- **The WordPress.org package is built by CI from a clean checkout**, not from
  the asset attached to the GitHub Release. A bad GitHub asset therefore does
  **not** imply a bad WordPress.org package — verify it, but they are
  independent.
- **The deploy only fires on 3-part tags.** `.github/workflows/publish.yml`
  triggers on `v[0-9]+.[0-9]+.[0-9]+` only, so a **4-part `vMAJOR.MINOR.PATCH.N`
  tag does not trigger a WordPress.org deploy** (and the job re-validates the
  shape and would exit anyway). This is the convention we use for a GitHub-only
  re-release.

To ship a corrected asset on GitHub only (without redeploying to WordPress.org):

1. **Fix the root cause** on a normal branch/PR and merge it, so the *next* real
   release is clean. (This does not retroactively fix the published release.)
2. **Rebuild and verify** the corrected zip from a clean tree:
   ```bash
   wp dist-archive .
   ./tests/bin/check-dist.sh   # must print only the OK line
   ```
3. **Tag a GitHub-only patch.** `N` starts at 1 (e.g. `v1.27.2.1` corrects
   `v1.27.2`). The `pre-push` hook blocks 4-part tags by design, so this
   deliberate action needs `--no-verify`:
   ```bash
   git tag -a v1.27.2.1 -m "GitHub-only re-release of v1.27.2 (corrected asset)"
   git push --no-verify origin v1.27.2.1
   ```
4. **Create the GitHub Release** for that tag, attach the corrected zip, and
   state in the notes that it is a **GitHub-only patch** caused by a
   release-preparation issue, and that the **WordPress.org package is
   unaffected**.
5. **Update the original release's notes to point at the patch.** On an
   immutable release the tag and the attached assets are frozen, but the
   **release notes remain editable**. Add a short note for GitHub users
   pointing to the `vX.Y.Z.N` re-release (and clarifying the WordPress.org
   package was never affected).

Do **not** bump the plugin version or `Stable tag` for a GitHub-only patch — the
plugin code is unchanged, only the packaging was wrong. The `vX.Y.Z.N` tag lives
entirely on GitHub and never reaches WordPress.org.
