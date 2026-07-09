#!/usr/bin/env bash
# Static analysis (Phase 4 of docs/release-preparation.md).
# php -l / bash -n are blocking; phpcs / phpstan are informative only.
#
# Usage: npm run release:check
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

echo "== php -l (blocking) =="
git ls-files '*.php' | xargs -n1 php -l

echo "== bash -n (blocking) =="
git ls-files '*.sh' | xargs -n1 bash -n

if [[ -x vendor/bin/phpcs ]]; then
  PHPCS=vendor/bin/phpcs
elif command -v phpcs >/dev/null 2>&1; then
  PHPCS=phpcs
else
  PHPCS=""
fi
if [[ -n "$PHPCS" ]]; then
  echo "== phpcs (informative) =="
  "$PHPCS" || true
else
  echo "phpcs not installed (composer install), skipping — informative only."
fi

if [[ -x vendor/bin/phpstan ]]; then
  PHPSTAN=vendor/bin/phpstan
elif command -v phpstan >/dev/null 2>&1; then
  PHPSTAN=phpstan
else
  PHPSTAN=""
fi
if [[ -n "$PHPSTAN" && -f phpstan.neon ]]; then
  echo "== phpstan (informative) =="
  "$PHPSTAN" analyse || true
else
  echo "phpstan/phpstan.neon not found, skipping — informative only."
fi

echo "Static analysis complete."
