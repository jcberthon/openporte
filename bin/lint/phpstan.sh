#!/usr/bin/env bash
# Runs PHPStan over the plugin source with the project config (phpstan.neon.dist).
#
# This script exists because the invocation needs a flag the config file cannot
# carry: PHPStan has no `memory_limit` parameter, and scanning
# php-stubs/wordpress-stubs (5.8 MB of declarations) blows PHP's default 128 MB.
# Without the raise it dies inside a parallel worker with a misleading
# "Child process error (exit code 255)" rather than a findings report. Keeping
# the invocation here makes it the single source of truth, shared by developers
# (`npm run lint:phpstan`) and by bin/release/check.sh.
#
# Prefers the version pinned in composer.lock over any globally installed
# phpstan, so a finding on one machine is a finding on another. Either binary
# works: the WordPress stubs are wired in through phpstan.neon.dist rather than
# through a Composer plugin, so a global phpstan resolves them too as long as
# `composer install` has run.
#
# Usage: npm run lint:phpstan                        # table report on stdout
#        bash bin/lint/phpstan.sh --error-format=json
#        PHPSTAN_MEMORY_LIMIT=2G npm run lint:phpstan
#
# Extra arguments are passed through to `phpstan analyse`. Exit status is
# PHPStan's own: 0 clean, 1 findings (or a config error). Findings are advisory
# — see docs/agents/static-analysis.md and "Code quality checks" in
# CONTRIBUTING.md.

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

MEMORY_LIMIT="${PHPSTAN_MEMORY_LIMIT:-1G}"

if [[ -x vendor/bin/phpstan ]]; then
  PHPSTAN=vendor/bin/phpstan
elif command -v phpstan >/dev/null 2>&1; then
  PHPSTAN=phpstan
else
  echo "phpstan not found. Run 'composer install' first." >&2
  exit 1
fi

exec "$PHPSTAN" analyse --memory-limit="$MEMORY_LIMIT" "$@"
