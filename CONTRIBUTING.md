# Contributing to OpenPorte

OpenPorte is a community reconstruction of the retired official ALTCHA WordPress
plugin. There is no live upstream — **we are the canonical source**. License:
GPLv2 or later.

This document covers **how releases flow** (branching, versioning, lifecycle) and
the **commit/PR conventions**. For the mechanical step-by-step of cutting a
release, see [`docs/release-preparation.md`](docs/release-preparation.md); for
coding rules and the manual verification protocol, see [`AGENTS.md`](AGENTS.md).

## Branching model — single trunk, linear

Development is **single-track**. There is exactly one long-lived branch, `main`.
We do **not** keep parallel version branches (`v1.27`, `v1.28`, …) — those names
refer to *version phases*, not branches. This keeps history linear
(`v1.27.0 → … → v1.27.x → v1.28.0 → …`) and avoids parallel maintenance we don't
have the resources to carry.

- All work happens on **short-lived branches off `main`**, merged back via PR.
- Use descriptive prefixes: `feature/…`, `fix/…`, `docs/…`, and `release/x.y.z`
  for release-prep branches (see the runbook).
- `main` is protected — never commit a release directly to it.

## Release lifecycle — two phases

A version line moves through two phases. The boundary is **WordPress.org
acceptance** of the current line.

**Phase A — patch line (the current `x.y` while it is in / awaiting review).**
`main` accepts **patch-level work only**: bug fixes, security fixes, and
WordPress.org review-requested changes. Each ships as `x.y.Z`.

- Keep each release **small** while a submission is under review, so the reviewer
  has little to re-check. **Review-requested fixes take priority** and ship ahead
  of other queued patches.
- Larger queued patch work (e.g. a security-hardening branch) **waits** until the
  review queue is settled, then merges as a later `x.y.Z`.

**Phase B — next minor line (after acceptance).** The first feature merge bumps
`main` to `x.(y+1).0`. From that point, **no further work happens on the previous
line** — that is the freeze. Feature branches that were waiting now land here.

### Hotfix escape hatch (rare)

If a fix is ever needed for an already-frozen line *after* the next minor has
shipped, cut a one-off `hotfix/x.y.z` branch **from the `vx.y.z` tag**, release
it, and delete the branch. This is the only sanctioned departure from linear
history — there is no standing parallel branch.

## Versioning — semantic versioning

| Bump | When |
|---|---|
| **patch** `x.y.Z` | bug/security fix, **no** user-visible behaviour change |
| **minor** `x.Y.0` | new integration or opt-in feature (backward compatible); also additive translations and a backward-compatible widget re-vendor |
| **major** `X.0.0` | breaking change: dropping a PHP/WP floor, or **removing a public hook/integration past its deprecation window** |

Removing an integration is breaking for its users: **deprecate it in a minor**
(use the `Deprecate` commit verb) and **remove it in the next major**.

The bundled ALTCHA widget has its own version (`OPENPORTE_WIDGET_VERSION` in
[`openporte.php`](openporte.php)) that moves **independently** of the plugin
version — bump it only when `public/altcha.min.js` is re-vendored (see
[`docs/agents/altcha-upstream.md`](docs/agents/altcha-upstream.md)).

The five version-string locations must change atomically — see the version-bump
checklist in [`AGENTS.md`](AGENTS.md) and Phase 1 of the release runbook.

### Tag naming and the pre-push hook

Release tags are `vMAJOR.MINOR.PATCH` (e.g. `v1.27.2`). The publish workflow
derives the WordPress.org version by stripping the leading `v`, and checks it
against the readme `Stable tag`, so a tag that doesn't follow the convention
would break (or silently skip) the deploy.

The repository ships a `pre-push` hook ([`.githooks/pre-push`](.githooks/pre-push))
that rejects non-conforming tag pushes locally. Git hooks are **not** installed
automatically — enable them once per clone:

```bash
git config core.hooksPath .githooks
```

This is a client-side guard and can be bypassed with `git push --no-verify`;
the publish workflow re-validates the tag shape server-side regardless.

## Tracking the plan

Open work is slotted onto **GitHub milestones** — one per planned release
(e.g. `v1.28.0`), plus a `future` bucket for accepted-but-unscheduled ideas.
These serve as the living release plan. An issue gets a release milestone once its
scope and target version are decided; issues still under discussion stay on
`future` (or unmilestoned).

## Commits and pull requests

Commit messages follow [`docs/agents/commit-conventions.md`](docs/agents/commit-conventions.md):
an imperative, capitalized verb prefix (`Add`, `Fix`, `Update`, `Remove`,
`Refactor`, `Docs`, `Bump`, `Deprecate`, `Revert`), ≤72 chars, no trailing
period. Reference issues in the body footer — `Fixes #123` to auto-close on merge,
`Refs #123` for context.

Before opening a PR, run the verification protocol in [`AGENTS.md`](AGENTS.md):
there is **no automated test suite**, so changes are validated by hand on the
`wp-env` bench (`php -l` on changed PHP, a clean `wp-env logs`, and the relevant
acceptance steps under [`docs/acceptance/`](docs/acceptance/)).
