#!/usr/bin/env bash
# Create a signed, annotated release tag locally (Phase 7 of
# docs/release-preparation.md). Deliberately does NOT push: pushing is what
# triggers the WordPress.org deploy (.github/workflows/publish.yml), so it
# stays a separate, explicit command: git push origin <tag>.
#
# Usage: npm run release:tag -- vX.Y.Z
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

TAG="${1:-}"
if [[ ! "$TAG" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Usage: npm run release:tag -- vMAJOR.MINOR.PATCH" >&2
  exit 1
fi

if [[ "$(git config --get tag.gpgsign)" != "true" ]]; then
  echo "tag.gpgsign is not enabled in this clone — the tag would be created unsigned." >&2
  echo "Enable it with: git config tag.gpgsign true" >&2
  exit 1
fi

branch="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$branch" != "main" ]]; then
  echo "Warning: tagging from '${branch}', not 'main'. Release tags are normally cut from main after the release-prep PR merges." >&2
fi

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Working tree is not clean — commit or stash before tagging." >&2
  exit 1
fi

git tag -a "$TAG" -m "Release ${TAG}"

echo "Created signed tag ${TAG} locally. Push explicitly when ready:"
echo "  git push origin ${TAG}"
