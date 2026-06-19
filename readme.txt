=== OpenPorte Spam Protection ===
Tags: captcha, spam, anti-spam, anti-bot, gdpr
Stable tag: 1.27.1
Requires at least: 5.6
Requires PHP: 8.0
Tested up to: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Contributors: huygens-25

OpenPorte offers a free, open-source Captcha alternative, ensuring robust spam protection while respecting user privacy and GDPR compliance.

== Description ==

OpenPorte is a community-maintained fork of the ALTCHA Spam Protection
plugin for WordPress (version 1), which provides a free, open source,
self-hostable, privacy-friendly CAPTCHA alternative based on a proof-
of-work mechanism — no cookies, no tracking, GDPR-friendly by design.

For the list of contributors, refer to our GitHub project: [Contributors](https://github.com/jcberthon/openporte/graphs/contributors?from=1.6.2024).

= Background =

The original ALTCHA WordPress plugin (v1) was open source (GPLv2). Its
authors have since released a version 2/3 which is no longer open source,
and some features that were free in v1 are now paid. They no longer
maintain v1 and recommend that users migrate to v2/v3. See the official
project at https://altcha.org for their offering.

OpenPorte continues the v1 line as free software (GPLv2 or later) for users
who want to stay on a fully open-source, self-hosted solution. It is a
faithful fork: existing v1 installations can switch to OpenPorte and keep
their settings (see Upgrading).

= Compatibility =

OpenPorte is backward-compatible with ALTCHA v1:

* Your existing settings are migrated automatically on activation.
* The `[altcha]` shortcode keeps working (alongside the new `[openporte]`).
* The `altcha_*` filters and actions keep firing as deprecated aliases.

See the Deprecations section for the full list of compatibility aliases and
what they map to.

== Upgrade Notice ==

= 1.27.1 =
Changes requested by the wordpress.org plugin review: renamed an internal
Elementor integration class, removed the directory asset files from the plugin
package, and dropped the no-longer-needed load_plugin_textdomain() call. No
functional change.

= 1.27.0 =
First release of the OpenPorte community fork of ALTCHA Spam Protection v1. The
paid altcha.org SaaS classifier is removed; self-hosted and custom backends are
unchanged. ALTCHA v1 settings migrate automatically on activation. Deactivate
the old ALTCHA plugin first; don't run both at once.

== Upgrading ==

= From the original ALTCHA v1 plugin =

Deactivate the old ALTCHA plugin, then install and activate OpenPorte. Your
existing configuration is detected and copied into the OpenPorte settings on
first activation; the original ALTCHA settings are left untouched, so you can
roll back to ALTCHA v1 without losing anything. Do not run both plugins at the
same time.

== Deprecations ==

The following ALTCHA-era identifiers are kept as aliases for backward
compatibility and are scheduled for removal in a future release:

* The `[altcha]` shortcode — use `[openporte]`.
* The `altcha/v1` REST namespace — use `openporte/v1`.
* The `altcha_*` filters and actions — now firing through WordPress' deprecated
  hook mechanism; use the `openporte_*` equivalents.
* The `AltchaPlugin` class and the `ALTCHA_VERSION` / `ALTCHA_WIDGET_VERSION`
  constants — use `OpenPortePlugin` and the `OPENPORTE_*` constants.
* Integrations targeting paid-only third-party plugins; affected users should
  migrate to the official ALTCHA v2/v3 plugin.

== Privacy ==

= No cookies, no tracking =

OpenPorte prioritizes user privacy by avoiding the use of cookies and fingerprinting techniques.

= No external service =

This plugin remains fully contained within your WordPress installation, eliminating any reliance on external services.

== Modes of Operation ==

OpenPorte verifies submissions in one of two modes, selected in the settings
(API Mode):

* Self-hosted (default) — a proof-of-work challenge is issued and verified by
  your own WordPress site through the REST API. Fully self-contained, with no
  external service and no additional setup beyond enabling the integrations you
  need.
* Custom — point the Challenge URL at your own ALTCHA-compatible backend (for
  example a self-hosted ALTCHA Sentinel). Submissions are verified with your
  site's signing secret.

The paid altcha.org regional SaaS classifier offered by earlier versions has
been removed; both remaining modes are free and self-hostable.
 
== Installation ==

Download, install and activate `OpenPorte Spam Protection`.
 
Alternatively, install the plugin manually:

1. Download the `.zip` from the [Releases](https://github.com/jcberthon/openporte/releases).
2. Upload `openporte` folder to the `/wp-content/plugins/` directory  
3. Activate the plugin through the 'Plugins' menu in WordPress  
4. Review the settings and enable your integrations

== REST API ==

This plugin requires the WordPress REST API. If you are using any "Disable REST API" plugins, ensure that the endpoint `/altcha/v1/challenge` (marked for deprecation) and `/openporte/v1/challenge` are allowed.

== Supported Integrations ==

* CoBlocks
* Contact Form 7
* Elementor Pro Forms (deprecated — paid plugin, see Deprecations)
* Formidable Forms
* Forminator
* GravityForms
* HTML Forms
* WPDiscuz
* WPForms
* WP-Members
* WordPress Login, Register, Password reset
* WordPress Comments
* WooCommerce
* Custom HTML (via the `[openporte]` shortcode, or the deprecated `[altcha]` alias)

== Source Code ==

All source code for the plugin, and the ALTCHA widget is available on GitHub. In the repository, you'll also find versions of non-minified JavaScript and CSS assets:

* Plugin: https://github.com/jcberthon/openporte
* ALTCHA Widget: https://github.com/altcha-org/altcha


== Screenshots ==

1. Friction-less Captcha without puzzles
2. Configuration
3. Protection on the login page
4. Protection with WPForms
5. Floating UI Captcha

== Changelog ==

= 1.27.2 =
This is a security-hardening release. None of these are exploitable vulnerabilities; they are defence-in-depth improvements with no change to normal behaviour.
* Hardening: submitted verification tokens are now strictly validated before use, so malformed or junk submissions fail closed without emitting PHP warnings.
* Hardening: signed server (spam-filter) responses are now only accepted while unexpired and explicitly verified, mirroring the proof-of-work path. Minimal custom backends that omit those fields keep working.
* Hardening: new installs now generate a 256-bit HMAC signing key. Existing keys — and the challenges already signed with them — are left untouched.
* Hardening: the inline widget-configuration script now hex-escapes its JSON so attribute values cannot break out of the `<script>` context.
* Hardening: tightened the Formidable Forms autoloader class-name guard.
* Fixed a typo in the settings field markup (`autcomplete="none"` became `autocomplete="off"`).
* Removed dead code left over from the paid-SaaS removal.

= 1.27.1 =
* Renamed the Elementor form-field integration class to use the `OpenPorte_` prefix, as requested by the wordpress.org plugin review (avoids the reserved `Elementor` prefix). No behaviour change.
* Removed the wordpress.org directory icon files from the plugin package; they are deployed separately as directory assets.
* Removed the `load_plugin_textdomain()` call: since WordPress 4.6 (we require 5.6+) translations are loaded automatically by core. No behaviour change.

= 1.27.0 =
* Forked ALTCHA Spam Protection v1 as OpenPorte, a community-maintained, fully open-source (GPLv2 or later) continuation.
* Rebranded the plugin to OpenPorte: new `[openporte]` shortcode and `openporte/v1` REST namespace, with the `[altcha]` shortcode, `altcha/v1` endpoint, `altcha_*` hooks and the `ALTCHA_*` / `AltchaPlugin` symbols kept as deprecated aliases (see Deprecations).
* Existing ALTCHA v1 settings are copied into the OpenPorte namespace on activation; the original `altcha_*` options are left in place so you can roll back.
* Removed the paid altcha.org regional SaaS classifier; self-hosted proof-of-work and custom self-hostable backends are unchanged.
* Security: HMAC signatures are now compared with `hash_equals()` (timing-safe).
* Wrapped the "This form requires JavaScript!" message so it can be translated.
* Corrected the documented minimum requirements to match the plugin's existing PHP 8.0 / WordPress 5.6 floor.
Contributors (GitHub) for this release: jcberthon, ded-furby.
Co-contributors: Mistral (AI), Claude (AI), GPT-OSS (AI).

= 1.26.3 =
* Fixed possible replay attacks via salt splicing.

= 1.26.2 =
* Updated readme for the new version 2.

= 1.26.1 =
* Fix Elementor Pro Forms widget rendering

= 1.26.0 =
* Added Formidable Forms integration
* Fixed PHP warning in the verify function
* ALTCHA Widget 2.2.2

= 1.25.0 =
* Added hooks for improved customization and integration flexibility. [#45]

= 1.24.0 =
* Fix issue with duplicate widget rendering in Elementor popups and WPDiscuz replies

= 1.23.0 =
* Support for CoBlocks

= 1.22.1 =
* Fix Gravity Forms validation with custom server 

= 1.22.0 =
* Fix Forminator multi-page forms
* Fix Gravity Forms with Sentinel and fields classification

= 1.21.0 =
* ALTCHA Widget 2.0.2
* Widget scripts are now injected only on pages, which include the widget
* Support for custom Challenge URL and ALTCHA Sentinel

= 1.20.0 =
* Enfold Theme (contact and newsletter forms) integration

= 1.19.0 =
* Fix submit issues with Contact Form 7 + Conditional fields

= 1.18.0 =
* Fix language with Contact Form 7

= 1.17.0 =
* Update widget to 1.2.0
* Widget removes support for Expires header fixing potential auto-revalidation issues
* Widget script provided as a UMD module allowing for JS minification

= 1.16.0 =
* Fix reply to comments from the admin page [#36]

= 1.15.0 =
* Translations with gettext and automatic language detection [#33]

= 1.14.1 =
* Fix the "Settings" link [#32]

= 1.14.0 =
* Automatic language detection [#31]
* Change placement of the "Settings" link in the plugin list [#32]

= 1.13.1 =
* Ignore WooCommerce form submissions in WordPress integration [#30]

= 1.13.0 =
* WooCommerce integration [#26]
* Improved validation message [#27]
* Password lost error message [#28]

= 1.12.0 =
* HTML Forms - skip verification if the shortcode is not in the form markup [#23]

= 1.11.1 =
* Fix Forminator compatibility issue

= 1.11.0 =
* Added support for WP-Members

= 1.10.0 =
* Added support for WPDiscuz

= 1.9.3 =
* Fix REST API Cache-Control header

= 1.9.2 =
* Enable Custom HTML (shortcode) integration by default when activated

= 1.9.1 =
* PHP 7 support (replace str_contains by strpos) [#19]

= 1.9.0 =
* Widget updated to version 1.0.0
* CF7 - fix widget placement
* Fix page caching

= 1.8.0 =
* Shortcode (custom integration) - fix mode (SpamFilter) 

= 1.7.0 =
* HTML Forms - add Shortcode option

= 1.6.1 =
* Fix WordPress login integration

= 1.6.0 =
* Fix Elementor Pro Forms widget rendering
* Fix Contact Form 7 widget position and shortcode support

= 1.5.0 =
* Fix REST base URL (+ REST prefix removed from settings) [#13]

= 1.4.0 =
* Support for Elementor Pro Forms
* Widget updated to 0.6.7

= 1.3.1 =
* Fix site_url parsing issue [#11]

= 1.3.0 =
* Added support for custom REST API prefixes

= 1.2.0 =
* Forminator - fix widget rendering with file input
* Widget updated to 0.6.4

= 1.1.0 =
* Shortcode - support for `language` attribute

= 1.0.0 =
* Widget updated to 0.6.3

= 0.3.0 =
* Added nonce sanitization
* Removed server-side spam filter (required for Plugin Directory)

= 0.2.1 =
* Fixes requested by Plugin Directory review
* Fixed various Spam Filter issues

= 0.2.0 =
* Widget updated to 0.6.0
* Added support for Floating UI

= 0.1.7 =
* Fix Forminator multi-step forms

= 0.1.6 =
* Widget updated to 0.5.1

= 0.1.5 =
* Fixes requested by Plugin Directory review

= 0.1.4 =
* GravityForms - added label and description options
* Altcha widget updated to 0.4.3

= 0.1.3 =
* Fixed "lost password" verification bug
* Altcha widget updated to 0.4.1

= 0.1.2 =
* Fixed widgets footer link and log warnings

= 0.1.1 =
* Widget v0.4.0
* Challenge expiration

= 0.1.0 =
* First version
