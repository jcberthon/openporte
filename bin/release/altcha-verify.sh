#!/usr/bin/env bash
# Verify the integrity of public/altcha.min.js against the committed
# public/altcha.min.js.sha256 (body only, header excluded).
#
# Usage: npm run altcha:verify [[--] [online|offline]]
#   online  = fetch the latest version from GitHub and verify against it
#   offline = use the local file (default)
#   Note: the version tag is detected from the local file header.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
source bin/release/lib.sh

# --- arguments ----------------------------------------------------------
VERIF_MODE="${1:-}"
if [[ -z "$VERIF_MODE" ]]; then
  VERIF_MODE="offline"
elif [[ "$VERIF_MODE" != "offline" && "$VERIF_MODE" != "online" ]]; then
  echo "Error: invalid argument: $VERIF_MODE" >&2
  exit 1
fi

# --- check the .sha256 file exists (v2.2.2 predates this mechanism) -----
if [[ "$VERIF_MODE" == "offline" && ! -f public/altcha.min.js.sha256 ]]; then
  echo "Error: public/altcha.min.js.sha256 not found. Try online mode instead." >&2
  exit 1
fi

# --- determine version (used in messages only) --------------------------
VERSION=$(sed -n 's/^ \* Version: \([0-9.]*\)/\1/p' public/altcha.min.js)
if [[ -z "$VERSION" && "$VERIF_MODE" == "online" ]]; then
  echo "Error: could not determine widget version from public/altcha.min.js. Try offline mode instead." >&2
  exit 1
fi

echo "Verifying ALTCHA widget v${VERSION} …"

# --- strip the header comment block (lines 1-4) and hash the body -------
LOCAL_SHA=$(perl -0777 -pe 's/\A\s*\/\*.*?\*\/\s*//s' public/altcha.min.js | sha256sum | awk '{print $1}')
EXPECTED_SHA=""
if [[ "$VERIF_MODE" == "offline" ]]; then
  EXPECTED_SHA=$(cat public/altcha.min.js.sha256)
else # Online
  EXPECTED_SHA=$(curl -fsSL "$GH_RAW_ALTCHA_URL/$GH_TAG_REF/v${VERSION}/$GH_DIST_DIR/$GH_ALTCHA_WIDGET_FILENAME" | sha256sum | awk '{print $1}')
fi

echo "Local body SHA-256:  $LOCAL_SHA"
echo "Expected SHA-256:    $EXPECTED_SHA ($VERIF_MODE computed)"

if [[ "$LOCAL_SHA" != "$EXPECTED_SHA" ]]; then
  echo ""
  echo "MISMATCH — public/altcha.min.js body does not match the committed checksum."
  echo "Run: npm run altcha:update -- ${VERSION:-X.Y.Z}"
  exit 1
fi

echo ""
echo "OK — public/altcha.min.js body matches the committed checksum."
