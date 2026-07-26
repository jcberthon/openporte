## Syntax verification and static analysis

After modifying any file, before considering a task complete:

**Verify (BLOCKING):** Run the appropriate syntax checker on each modified file.
Syntax errors must be corrected before proceeding.

- PHP: `php -l <file>`
- Shell scripts: `bash -n <file>`

If syntax is invalid, fix it. Do not proceed with a broken file.

**Validate (INFORMATIVE):** If the following tools are installed, run them on
modified files and report findings in a condensed, organised summary. Do not
block on these results — report only, let the maintainer decide.

- PHP: `npm run lint:phpcs`, `npm run lint:phpmd` and `npm run lint:phpstan`
- Shell: `shellcheck <file>`

The three PHP analysers — what they cover, which one gates a PR, why PHPMD exits
`2`, and the standing backlog of pre-existing findings — are documented once,
for humans and agents alike, under "Code quality checks" in
[`CONTRIBUTING.md`](../../CONTRIBUTING.md). Read it before reporting results;
do not restate it here.

Agent-specific additions to that:

- **Report the delta, not the backlog.** Separate findings your change
  introduced from the pre-existing ones. Reporting the standing backlog as if
  it were new is noise.
- **Never "fix" a pre-existing finding in passing** — that violates the
  touch-scoped rule in [`coding-style.md`](coding-style.md). Raise it instead.
- **Run PHPStan through `npm run lint:phpstan`**, never `phpstan analyse` bare:
  the analysis needs a raised memory limit to parse the WordPress stubs, and
  without it PHPStan dies with a misleading "Child process error (exit code
  255)" instead of a report. See [`bin/lint/phpstan.sh`](../../bin/lint/phpstan.sh).
- PHPStan runs at **level 5** and **gates the PR** — it is the one analyser here
  that should report zero findings. `phpstan-baseline.neon` absorbs the four that
  predate its introduction (issue #77), so anything PHPStan reports is a finding
  *your change* introduced. Fix it.
- **Never regenerate the baseline** (`--generate-baseline`) and never add a
  `@phpstan-ignore` comment to make the check pass. Both bury a real finding. If
  you believe a finding is a false positive, say so and raise it — an
  `ignoreErrors` entry in [`phpstan.neon.dist`](../../phpstan.neon.dist) needs
  the maintainer's agreement and a written justification, exactly like a
  documented `phpcs:ignore`. The entries already there cover symbols from
  optional third-party plugins that are deliberately not dependencies.

**WordPress Plugin Check (MANUAL — tester only):** The WordPress
[Plugin Check](https://wordpress.org/plugins/plugin-check/) tool is run by a
human tester against the built plugin zip (not the source tree) on a
WordPress 6.3+ instance. It is **not** an automated step and is **not** run by
agents. The tester exports results as a JSON file and reports findings to the
maintainer; the maintainer then decides which items to fix and which are
justified false positives (documented with `phpcs:ignore` and an explanatory
comment). Plugin Check requires WordPress 6.3+ — see
[Older WordPress versions](../maintenance-testing.md#older-wordpress-versions)
for caveats when testing on older cores.
