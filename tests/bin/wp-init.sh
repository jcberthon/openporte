#!/usr/bin/env bash
#
# wp-init.sh — wp-env "afterStart" hook: provision test fixtures for OpenPorte.
#
# Sets up a side-by-side bench so the rebrand/migration can be exercised by hand:
#   * Contact Form 7 (installed via .wp-env.json) — activated.
#   * ALTCHA Spam Protection v1.26.3 (the upstream plugin OpenPorte forks) and
#     OpenPorte (this repo, mapped) — both INSTALLED but DEACTIVATED, so a tester
#     can activate them one at a time and walk the ALTCHA -> OpenPorte upgrade.
#   * Two pages:
#       - "Contact Us": a Contact Form 7 form (its id is discovered, not guessed).
#       - "Test Page": both the [altcha] and [openporte] shortcodes.
#
# Idempotent: safe to re-run; existing fixture pages are left untouched.
#
# IMPORTANT: activate ALTCHA and OpenPorte one at a time, never together — both
# register the [altcha] shortcode and the altcha/v1 REST route and would clash.

set -euo pipefail

# Run a wp-cli command inside the wp-env "cli" container. --silent keeps npm's
# banner out of the output so command substitution below stays clean.
wpcli() { npm run --silent env run cli -- wp "$@"; }

ALTCHA_SLUG="altcha-spam-protection"
ALTCHA_ZIP="local/${ALTCHA_SLUG}.1.26.3.zip"
ALTCHA_URL="https://downloads.wordpress.org/plugin/${ALTCHA_SLUG}.1.26.3.zip"

echo "wp-init: installing legacy ALTCHA v1.26.3 (source plugin for the migration test)…"
if [ -f "$ALTCHA_ZIP" ]; then
  wpcli plugin install "$ALTCHA_ZIP" --force
else
  # The zip is intentionally not committed to git; wordpress.org still serves the
  # byte-identical 1.26.3 build, so fall back to downloading it.
  wpcli plugin install "$ALTCHA_URL" --force
fi

echo "wp-init: ALTCHA + OpenPorte left deactivated; activating Contact Form 7…"
# Deactivation is a no-op (and harmless) when the plugin is already inactive.
wpcli plugin deactivate "$ALTCHA_SLUG" || true
wpcli plugin deactivate openporte || true
wpcli plugin activate contact-form-7

echo "wp-init: creating fixture pages…"
existing_slugs="$(wpcli post list --post_type=page --post_status=publish --field=post_name)"

if ! grep -qxF "contact-us" <<<"$existing_slugs"; then
  # Contact Form 7 creates a default form on activation; discover its id instead
  # of hard-coding it (the id is assigned at install time and is not stable).
  cf7_id="$(wpcli post list --post_type=wpcf7_contact_form --format=ids | awk '{print $1}')"
  if [ -z "$cf7_id" ]; then
    echo "wp-init: WARNING — no Contact Form 7 form found; the 'Contact Us' page will have no form." >&2
    wpcli post create --post_type=page --post_title='Contact Us' --post_status=publish \
      --post_content='No Contact Form 7 form was found when this page was provisioned.'
  else
    echo "wp-init: using Contact Form 7 form id ${cf7_id}."
    wpcli post create --post_type=page --post_title='Contact Us' --post_status=publish \
      --post_content="[contact-form-7 id=\"${cf7_id}\"]"
  fi
fi

if ! grep -qxF "test-page" <<<"$existing_slugs"; then
  # Real newline between the two shortcodes (a literal \n would not render).
  wpcli post create --post_type=page --post_title='Test Page' --post_status=publish \
    --post_content=$'[altcha]\n[openporte]'
fi

echo "wp-init: done."
