#!/usr/bin/env bash
# Runs PHPMD over the plugin source with the project ruleset (phpmd.xml.dist).
#
# This script exists because PHPMD accepts path exclusions only as a CLI flag —
# unlike phpcs.xml.dist, a PHPMD ruleset file cannot carry them. Keeping the
# invocation here makes it the single source of truth for what gets scanned,
# shared by developers and by .github/workflows/phpmd.yml. Running `phpmd .`
# by hand instead would also walk vendor/, which CI never sees.
#
# Usage: npm run lint:phpmd                       # text report on stdout
#        bash bin/lint/phpmd.sh sarif results.sarif
#
# Exit status is PHPMD's own: 0 clean, 1 error, 2 violations found. Violations
# are advisory (docs/agents/static-analysis.md) — CI reports them, it does not
# gate on them.

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

FORMAT="${1:-text}"
REPORTFILE="${2:-}"

# Everything that is not plugin source. Mirrors the exclusions in
# phpcs.xml.dist, plus .claude/worktrees — agent worktrees hold full checkouts
# of the repo and would otherwise be reported once per worktree.
EXCLUDE='*vendor/*,*node_modules/*,*tests/*,*local/*,*.claude/*'

# PHPMD 2.15 is not deprecation-clean on PHP 8.4+; its own notices would
# otherwise bury the report. Scoped to this invocation, not the project.
PHP_ARGS=(-d error_reporting='E_ALL & ~E_DEPRECATED')

CMD=(php "${PHP_ARGS[@]}" vendor/bin/phpmd . "$FORMAT" phpmd.xml.dist --exclude "$EXCLUDE")
if [[ -n "$REPORTFILE" ]]; then
  CMD+=(--reportfile "$REPORTFILE")
fi

"${CMD[@]}"
