#!/usr/bin/env bash
# Regenerate translations (Phase 2 of docs/release-preparation.md). Only run
# when the release touched translatable strings. See docs/agents/i18n.md for
# the glossary and per-locale fill-in procedure.
#
# Usage: npm run release:i18n -- X.Y.Z
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
source bin/release/lib.sh

VERSION="${1:-}"
if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Usage: npm run release:i18n -- X.Y.Z (no leading 'v')" >&2
  exit 1
fi

wp i18n make-pot . languages/openporte.pot \
  --domain=openporte \
  --exclude=vendor,local,tests,public/altcha.min.js \
  --slug=openporte

# msgmerge (not `wp i18n update-po`) so a reworded/typo-fixed msgid keeps its
# old translation, flagged "#, fuzzy", instead of being dropped.
for po in languages/openporte-*.po; do
  msgmerge --update --backup=none --previous "$po" languages/openporte.pot
done

sed_inplace -E "s/Project-Id-Version: OpenPorte Spam Protection [0-9.]+/Project-Id-Version: OpenPorte Spam Protection ${VERSION}/" languages/openporte-*.po

# msgfmt (not `wp i18n make-mo`) so "#, fuzzy" entries are excluded from the
# .mo (falling back to English) until a translator reviews them.
for po in languages/openporte-*.po; do
  msgfmt --check "$po" -o "${po%.po}.mo"
done

echo "Translations regenerated for ${VERSION}."
echo "Review any newly-fuzzy entries before shipping (docs/agents/i18n.md)."
