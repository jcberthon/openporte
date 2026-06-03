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