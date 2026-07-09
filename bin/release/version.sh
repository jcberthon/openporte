#!/usr/bin/env bash
# Bump the plugin version in every mechanical location (Phase 1 of
# docs/release-preparation.md). Does NOT touch the changelog / upgrade notice
# (Phase 3) or "Tested up to" — those require human judgment.
#
# Usage: npm run release:version -- X.Y.Z
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
source bin/release/lib.sh

VERSION="${1:-}"
if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Usage: npm run release:version -- X.Y.Z (no leading 'v')" >&2
  exit 1
fi

sed_inplace -E "s/\* Version:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+/* Version: ${VERSION}/" openporte.php
sed_inplace -E "s/\* Stable tag:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+/* Stable tag: ${VERSION}/" openporte.php
sed_inplace -E "s/define\('OPENPORTE_VERSION', '[0-9]+\.[0-9]+\.[0-9]+'\)/define('OPENPORTE_VERSION', '${VERSION}')/" openporte.php
sed_inplace -E "s/^Stable tag:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+/Stable tag: ${VERSION}/" readme.txt

echo "Bumped version to ${VERSION} in openporte.php and readme.txt."
echo "Remaining manual: 'Tested up to' (if the WP ceiling changed) and the"
echo "Changelog / Upgrade Notice entries (Phase 3)."
