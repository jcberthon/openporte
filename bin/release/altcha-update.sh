#!/usr/bin/env bash
# Fetch the ALTCHA widget from upstream and replace the bundled file.
#
# Usage: npm run altcha:update -- X.Y.Z
#   X.Y.Z  = version tag to fetch (e.g. 2.3.0) - mandatory
#
# After running, verify the header comments and OPENPORTE_WIDGET_VERSION constant.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
source bin/release/lib.sh

# --- arguments ----------------------------------------------------------
TARGET_VERSION="${1:-}"
GH_TAGGED_URL="$GH_ALTCHA_URL/$GH_TAG_REF/v${TARGET_VERSION}"
TMPDIR=""

cleanup() {
  [[ -n "$TMPDIR" && -d "$TMPDIR" ]] && rm -rf "$TMPDIR"
}
trap cleanup EXIT

if [[ -z "$TARGET_VERSION" ]]; then
  echo 'Error: the version argument is mandatory. (e.g "npm run altcha:update -- 2.3.0")' >&2
  exit 1
fi

echo "Fetching ALTCHA widget v${TARGET_VERSION} …"

# --- download the .cjs file ---------------------------------------------
TMPDIR=$(mktemp -d)
curl -fsSL -o "$TMPDIR/altcha.min.js" "$GH_TAGGED_URL/$GH_DIST_DIR/$GH_ALTCHA_WIDGET_FILENAME"
SRC="$TMPDIR/altcha.min.js"

# --- check the file exists ----------------------------------------------
if [[ ! -f "$SRC" ]]; then
  echo "Error: $GH_DIST_DIR/$GH_ALTCHA_WIDGET_FILENAME not found for version ${TARGET_VERSION}" >&2
  exit 1
fi

# --- write the new file -------------------------------------------------
HEADER="/**
 * Version: ${TARGET_VERSION}
 * Source: $GH_ALTCHA_URL/tree/v${TARGET_VERSION}
 */"

{
  echo "$HEADER"
  cat "$SRC"
} > public/altcha.min.js

# --- write the body-only SHA-256 file -----------------------------------
sha256sum "$SRC" | awk '{print $1}' > public/altcha.min.js.sha256

echo "Replaced public/altcha.min.js with v${TARGET_VERSION}."
echo "Vendor SHA-256: $(cat public/altcha.min.js.sha256)"
echo "Remember to update OPENPORTE_WIDGET_VERSION in openporte.php and"
echo "readme.txt before committing."
