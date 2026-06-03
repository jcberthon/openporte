# Commit conventions

**Subject line:**
- Imperative verb, capitalized: `Add`, `Fix`, `Update`, `Remove`, `Refactor`,
  `Docs`, `Bump`, `Deprecate`, `Revert`
- One short sentence in English, no trailing period
- ≤50 chars preferred, 72 hard limit

**Body** (optional, for non-trivial changes):
- Two newlines after the subject, then prose wrapped at ~72 chars
- Explain *why*, not *what* — the diff shows what

**Issue references** (in the body footer when applicable):
- `Fixes #123` / `Closes #123` auto-closes the issue on merge to default branch
- `Refs #123` for context without closing

Example:

    Fix HMAC comparison to use hash_equals()

    Replace `===` with `hash_equals()` in core.php:415,445 to remove
    timing-attack surface. The `true` (raw binary) flag on the hash()
    call at line 413 must be preserved — removing it would invalidate
    every previously issued challenge.

    Fixes #42