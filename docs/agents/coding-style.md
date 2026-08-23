# Coding style — WordPress Coding Standards

This project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
(WPCS) — [PHP](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/),
[CSS](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/),
[JavaScript](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
— plus its [Inline Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/)
for docblocks. Applies to PHP, JS (`public/*.js`), and CSS (`public/*.css`)
alike. Excluded: vendored third-party files — currently `public/altcha.min.js`
(see "`public/altcha.min.js` — vendored, do not edit" in AGENTS.md). Check a
file's header before restyling anything under `public/altcha.*` — several of
those files carry upstream attribution rather than being ours to reformat.

No linter enforces this for JS/CSS today (only PHP has `phpcs.xml.dist` +
composer WPCS tooling). Follow it by hand for JS/CSS until that changes.

## Touch-scoped, not a mass reformat

**Do not refactor code you're not otherwise touching purely to bring it into
line with WPCS.** This codebase predates this policy and has plenty of
pre-existing non-conformance. A drive-by style pass creates diff noise, review
burden, and merge-conflict risk unrelated to the actual change. A dedicated
cleanup, if it ever happens, is a deliberate maintainer-scoped effort — not
something to slip into an unrelated fix.

**When you *do* touch a function, method, class, or hook** — because the task
requires implementing a change or a fix there — bring that unit into line with
WPCS as part of the change: naming, spacing, brace/control-structure style,
discouraged-function replacement, security/sanitization/escaping conventions,
i18n (see "i18n discipline" in AGENTS.md).

- **The refactor must be behavior-preserving.** Same inputs, same outputs,
  same side effects — verify with `php -l` (`docs/agents/static-analysis.md`)
  and, for anything touching widget rendering, verification, or integrations,
  the manual protocol in AGENTS.md's "Verification protocol". Style cleanup is
  not license to also change behavior "while you're in there" — if a behavior
  change is separately warranted, make it a deliberate, callable-out part of
  the commit, not an accidental side effect of reformatting.
- **Exception — indentation stays 2-space.** `phpcs.xml.dist` deliberately
  excludes whitespace/indentation sniffs because this codebase inherits
  ALTCHA's 2-space indentation; that decision stands here too. Do not convert
  a touched function's indentation to WPCS's tab convention — match the
  surrounding file's existing style. Everything else in WPCS (naming,
  docblocks, brace placement, etc.) still applies.
- **Scope stops at the unit boundary**, not the whole file. The whole touched
  function including its header, yes — the other nine methods of the class,
  no. If a fix touches one method of a ten-method class, restyle that method
  and its docblock, and leave the other nine alone.

**The unit is the whole function, header comment included** — not the lines
your diff happens to land on. Change three lines inside a forty-line function
and the other thirty-seven, *plus* the docblock above it, are in scope: that
whole function should read as conformant when you are done, with no "the rest
was already like that" left behind. Same for a method you edit, and for a hook
callback together with its `add_action`/`add_filter` registration. Before
calling a modified function done, check across its **entire** body and header:

- the docblock: present, accurate for what the code *now* does, with correct
  `@param`/`@return`/`@since` (see the two sections below) — a stale header on
  a function you just edited is a defect, not a leftover;
- inline comments still describing the current logic — a comment that has
  quietly become wrong is worse than no comment;
- naming, brace and control-structure style, spacing, quoting;
- discouraged functions, escaping/sanitization, and i18n.

## Inline documentation (docblocks)

- **Creating a new function, method, class, or hook:** add a docblock. No
  exceptions — this is the one case with no "did I already touch it" test,
  since the code didn't exist before your change.
- **Modifying an existing function/method/class/hook** (even a small fix): add
  a docblock if one is missing, or update the existing one if your change
  makes it stale (new/changed parameter, changed return, changed behavior,
  new deprecation, etc). Bring the docblock current with what the code now
  does.
- **The header comment travels with the function.** A docblock is reviewed as
  part of any change to the function it documents, even when no line of it
  appears in your diff — see "The unit is the whole function" above.
- **Do not** add or edit docblocks for functions/classes/etc you don't
  otherwise need to touch — same touch-scoped principle as above.
- Structure: one-line summary, blank line, optional longer description, then
  tags (`@since`, `@param $name Type Description.`, `@return Type
  Description.`, `@throws`, ...) as applicable to the symbol, per WPCS.

## The `@since` tag

- **New function/method/class/hook created during this release cycle:** tag
  it with the *in-progress* version, not the last stable release. Find the
  in-progress version from `openporte.php`'s `Version:` header /
  `OPENPORTE_VERSION` constant (carries a `-dev` suffix while unreleased), or
  the topmost `= X.Y.Z (unreleased) =` entry in `readme.txt`'s changelog.
  Don't hardcode a version number from memory or from an example — it goes
  stale the moment a release ships.
- **Existing function/method/class you're modifying that has no docblock, or
  a docblock missing `@since`:** add the tag, using the symbol's actual
  introduction version — found via `git log --follow -p -- <file>` /
  `git blame` back to the commit that first introduced that signature.
  - **Don't search further back than 1.26.3** — the last GPL v1 ALTCHA
    release before the fork (see "Auditing upstream ALTCHA v1" in AGENTS.md).
    If the git trail runs cold before an attributable introduction commit —
    history gets thin, or the function predates reliable attribution — tag it
    `@since 1.26.3` as the earliest baseline rather than guessing further
    back.
- **A later, meaningful behavior change to an existing, already-tagged
  symbol** may get a second line — `@since x.y.z Description of what
  changed.` — per WPCS convention. Add this only for a change you're the one
  making, not retroactively for history you didn't touch.
