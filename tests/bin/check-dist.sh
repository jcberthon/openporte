#!/usr/bin/env bash
#
# check-dist.sh — verify the dist archive contains only git-tracked files.
#
# wp dist-archive builds the release zip from the working tree filtered by
# .distignore, NOT from git (see docs/release-preparation.md, Phase 0). A
# stray local directory (a locally-installed tool's state dir, an editor
# artifact, anything not in .distignore) can leak into the published zip
# without ever showing up in `git status` or `git diff` — especially when
# it's only ignored by a *global* gitignore, which .distignore can't see.
#
# This builds a throwaway archive and flags any file inside it that git
# doesn't track. On a clean tree it prints nothing else and exits 0.
#
# Caveat: this assumes everything shippable is git-tracked (true today —
# vendor/ is generated but already excluded via .distignore). If the repo
# ever stops tracking generated-but-shipped files (e.g. languages/*.mo, see
# https://github.com/jcberthon/openporte/issues/11), this script will need
# an allowlist for those paths.
#
# Usage: ./tests/bin/check-dist.sh   (run from the repo root)

set -euo pipefail

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT
zip="$tmpdir/dist-check.zip"

wp dist-archive . "$zip" >/dev/null

prefix="$(unzip -Z1 "$zip" | head -1 | cut -d/ -f1)"
extra="$(comm -23 \
  <(unzip -Z1 "$zip" | sed "s#^$prefix/##" | grep -v '/$' | sort) \
  <(git ls-files | sort))"

if [ -n "$extra" ]; then
  echo "Files in the dist archive that git doesn't track:" >&2
  while IFS= read -r path; do
    printf '  %s\n' "$path" >&2
  done <<<"$extra"
  exit 1
fi

echo "OK: dist archive contains only git-tracked files ($(unzip -Z1 "$zip" | grep -vc '/$') files)."
