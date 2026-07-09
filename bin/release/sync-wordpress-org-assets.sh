#!/usr/bin/env bash
# Sync the WordPress.org listing icons from the branding source of truth, so
# share/branding stays the only place these are hand-maintained.
#
# NOT included: banner-772x250.png / banner-1544x500.png. WordPress.org's
# banner aspect ratio doesn't match share/branding's current banner exports
# (256/384/600/768/1024px), so there is nothing correct to copy yet.
#
# Usage: npm run release:assets
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

SRC="share/branding"
DEST=".wordpress-org"

cp "${SRC}/png/openporte-icon-256px.png" "${DEST}/icon-256x256.png"
cp "${SRC}/png/openporte-icon-128px.png" "${DEST}/icon-128x128.png"
cp "${SRC}/scalable/openporte-icon.svg" "${DEST}/icon.svg"

echo "Synced ${DEST}/{icon-256x256.png,icon-128x128.png,icon.svg} from ${SRC}."
