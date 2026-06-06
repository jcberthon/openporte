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

# Run a wp-cli command inside the wp-env "cli" container. wp-env is the binary
# used by wp-env.sh (there is no npm "env" script in this repo).
wpcli() { wp-env run cli wp "$@"; }

ALTCHA_SLUG="altcha-spam-protection"
# The plugin dir is mapped to wp-content/plugins/openporte, so the zip in local/
# is reachable from the container at this path (relative to the WordPress root).
ALTCHA_ZIP_HOST="local/${ALTCHA_SLUG}.1.26.3.zip"
ALTCHA_ZIP_CONTAINER="wp-content/plugins/openporte/local/${ALTCHA_SLUG}.1.26.3.zip"
ALTCHA_URL="https://downloads.wordpress.org/plugin/${ALTCHA_SLUG}.1.26.3.zip"

echo "wp-init: installing legacy ALTCHA v1.26.3 (source plugin for the migration test)…"
if [ -f "$ALTCHA_ZIP_HOST" ] && wpcli plugin install "$ALTCHA_ZIP_CONTAINER" --force; then
  echo "wp-init: installed ALTCHA from the local zip."
else
  # The zip is intentionally not committed to git; wordpress.org still serves the
  # byte-identical 1.26.3 build, so fall back to downloading it.
  echo "wp-init: local zip unavailable, downloading ALTCHA from wordpress.org…"
  wpcli plugin install "$ALTCHA_URL" --force
fi

echo "wp-init: ALTCHA + OpenPorte left deactivated; activating Contact Form 7…"
# Deactivation is a no-op (and harmless) when the plugin is already inactive.
wpcli plugin deactivate "$ALTCHA_SLUG" || true
wpcli plugin deactivate openporte || true
wpcli plugin activate contact-form-7

echo "wp-init: creating fixture pages…"
existing_slugs="$(wpcli post list --post_type=page --post_status=publish --field=post_name 2>/dev/null || true)"

if ! grep -qxF "contact-us" <<<"$existing_slugs"; then
  # Contact Form 7 5.x identifies forms by a hash (stored in the _hash postmeta);
  # the numeric post id is deprecated. Discover the default form's post id, read
  # its hash, and emit the shortcode with the hash UNQUOTED — the hash is
  # alphanumeric, so it needs no quotes and survives the host -> ssh -> docker
  # shell layers intact (embedded double quotes get mangled across them).
  cf7_post_id="$(wpcli post list --post_type=wpcf7_contact_form --format=ids 2>/dev/null | grep -oE '[0-9]+' | head -n1 || true)"
  cf7_hash=""
  if [ -n "$cf7_post_id" ]; then
    cf7_hash="$(wpcli post meta get "$cf7_post_id" _hash 2>/dev/null | grep -oE '[a-f0-9]{7,}' | head -n1 || true)"
  fi

  if [ -n "$cf7_hash" ]; then
    # Contact Form 7's editor uses the 7-char short hash. Emit the canonical
    # quoted shortcode, but pass it through a file (read by `wp post create`)
    # rather than a CLI argument — embedded double quotes get mangled crossing
    # the wp-env -> docker shell boundary, whereas file content is preserved.
    cf7_short="${cf7_hash:0:7}"
    cf7_content_host="local/.cf7-contact-us.html"
    cf7_content_container="wp-content/plugins/openporte/${cf7_content_host}"
    echo "wp-init: using Contact Form 7 short hash ${cf7_short}."
    printf '%s' "[contact-form-7 id=\"${cf7_short}\" title=\"Contact form 1\"]" > "$cf7_content_host"
    wpcli post create "$cf7_content_container" --post_type=page --post_title='Contact Us' --post_status=publish
    rm -f "$cf7_content_host"
  elif [ -n "$cf7_post_id" ]; then
    echo "wp-init: hash not found; falling back to Contact Form 7 post id ${cf7_post_id}." >&2
    wpcli post create --post_type=page --post_title='Contact Us' --post_status=publish \
      --post_content="[contact-form-7 id=${cf7_post_id}]"
  else
    echo "wp-init: WARNING — no Contact Form 7 form found; the 'Contact Us' page will have no form." >&2
    wpcli post create --post_type=page --post_title='Contact Us' --post_status=publish \
      --post_content='No Contact Form 7 form was found when this page was provisioned.'
  fi
fi

if ! grep -qxF "test-page" <<<"$existing_slugs"; then
  # Real newline between the two shortcodes (a literal \n would not render).
  wpcli post create --post_type=page --post_title='Test Page' --post_status=publish \
    --post_content=$'[altcha]\n[openporte]'
fi

echo "wp-init: done."
