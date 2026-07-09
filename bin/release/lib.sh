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
