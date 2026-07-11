#!/usr/bin/env bash
# Shared helpers for bin/release/*.sh. Sourced, not executed directly.

# BSD sed (macOS) requires an argument to -i; GNU sed (Linux) errors if given one.
# See docs/release-preparation.md, Phase 2.
sed_inplace() {
  if [[ "$(uname)" == "Darwin" ]]; then
    sed -i '' "$@"
  else
    sed -i "$@"
  fi
}

GH_ALTCHA_URL="https://github.com/altcha-org/altcha"
GH_RAW_ALTCHA_URL="https://raw.githubusercontent.com/altcha-org/altcha"
GH_TAG_REF="refs/tags"
GH_ALTCHA_WIDGET_FILENAME="altcha.umd.cjs"
GH_DIST_DIR="dist"
# Note the 2 values above are true for v2, but v3 they are different:
#   - filename: altcha.umd.min.cjs
#.  - dist dir: dist/main/
# In a future version when migrating to an altcha v3 release, the logic should be added.
export GH_ALTCHA_URL GH_RAW_ALTCHA_URL GH_TAG_REF GH_ALTCHA_WIDGET_FILENAME GH_DIST_DIR
