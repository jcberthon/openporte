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

echo "Synced ${DEST}/{icon-256x256.png,icon-128x128.png,icon.svg} from ${SRC}."
