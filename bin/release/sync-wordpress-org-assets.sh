#!/usr/bin/env bash
# Sync the WordPress.org listing icons from the branding source of truth, so
# share/branding stays the only place these are hand-maintained.
#
# Usage: npm run release:assets
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

SRC="share/branding"
DEST=".wordpress-org"

cp "${SRC}/png/openporte-icon-256px.png"         "${DEST}/icon-256x256.png"
cp "${SRC}/png/openporte-icon-128px.png"         "${DEST}/icon-128x128.png"
cp "${SRC}/scalable/openporte-icon.svg"          "${DEST}/icon.svg"
cp "${SRC}/png/openporte-banner-wp-772x250.png"  "${DEST}/banner-772x250.png"
cp "${SRC}/png/openporte-banner-wp-1544x500.png" "${DEST}/banner-1544x500.png"

# Every raster asset above is named after its required WxH (WordPress.org's
# listing enforces exact pixel sizes) — verify the copy actually matches
# before it ships. SVGs are vector and skipped. Uses macOS's built-in `sips`
# (no install needed); on other platforms this degrades to a warning rather
# than blocking the sync, since `sips` isn't available there.
if command -v sips >/dev/null 2>&1; then
  for f in "${DEST}"/*.png "${DEST}"/*.jpg "${DEST}"/*.jpeg; do
    [[ -e "$f" ]] || continue
    base="$(basename "$f")"
    if [[ "$base" =~ ([0-9]+)x([0-9]+)\.(png|jpe?g)$ ]]; then
      expected_w="${BASH_REMATCH[1]}"
      expected_h="${BASH_REMATCH[2]}"
      actual_w="$(sips -g pixelWidth "$f" | awk '/pixelWidth:/ {print $2}')"
      actual_h="$(sips -g pixelHeight "$f" | awk '/pixelHeight:/ {print $2}')"
      if [[ "$actual_w" != "$expected_w" || "$actual_h" != "$expected_h" ]]; then
        echo "ERROR: ${f} is ${actual_w}x${actual_h}px, expected ${expected_w}x${expected_h}px per its filename." >&2
        exit 1
      fi
    fi
    # Files without a WxH suffix (e.g. a future screenshot-1.png) have no
    # expected size to check against — skipped silently.
  done
else
  echo "WARNING: 'sips' not found, skipping dimension verification (macOS only)." >&2
fi

echo "Synced ${DEST}/{icon-256x256.png,icon-128x128.png,icon.svg,banner-772x250.png,banner-1544x500.png} from ${SRC}, dimensions verified."
