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

- PHP: `phpstan analyse <file>` (requires `phpstan.neon` at repo root)
- Shell: `shellcheck <file>`

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