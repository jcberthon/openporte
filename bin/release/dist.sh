#!/usr/bin/env bash
# Build and verify the local/archival release zip (Phase 7 of
# docs/release-preparation.md). Reads the working tree filtered by
# .distignore — run this on a clean tree (see Phase 0).
#
# Usage: npm run release:dist
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

wp dist-archive .
./tests/bin/check-dist.sh
