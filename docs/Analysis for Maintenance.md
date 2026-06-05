# Entry Point

openporte.php is the sole entry point. It contains the WordPress plugin header (Plugin Name, Version, etc.), defines three constants (OPENPORTE_VERSION, OPENPORTE_WIDGET_VERSION, ALTCHA_WEBSITE), then sequentially requires all other files and registers the top-level hooks.

# How It Hooks Into WordPress

The plugin uses no custom framework — everything is pure WordPress hook API:


Registration;	Hook;	Purpose
register_activation_hook;	—;	Seeds 8 default wp_options entries
add_action('init');	—;	Loads i18n textdomain
add_action('rest_api_init');	—;	Registers GET /wp-json/openporte/v1/challenge
add_action('admin_menu');	—;	Adds Settings > ALTCHA menu page
add_action('admin_init');	—;	Registers all 30 settings fields via the Settings API
add_filter('script_loader_tag');	public/widget.php;	Adds type="module" to the widget <script> tag
add_shortcode('altcha');	—;	[altcha] shortcode for manual widget placement
~40 hooks across 13 integration files;	various;	Render widget + validate on each supported form plugin

The OpenPortePlugin class in includes/core.php is a singleton (accessed via OpenPortePlugin::$instance everywhere). It owns all shared logic: challenge generation, HMAC verification, spam-filter API calls, widget HTML rendering, and all wp_options key names.

# Directory Layout

openporte.php                  ← Entry point + plugin header
includes/
  core.php                  ← OpenPortePlugin singleton (all business logic)
  helpers.php               ← Script enqueue helpers, plugin-detection
  admin.php                 ← Admin menu registration
  settings.php              ← Settings API (30 options, 5 sections)
  index.php                 ← Directory listing guard
admin/
  options.php               ← Admin settings page HTML template
integrations/
  wordpress.php             ← WP login/register/comments/reset-pw (9 hooks)
  woocommerce.php           ← WC login/register/reset-pw (6 hooks)
  contact-form-7.php        ← CF7 (3 hooks)
  gravityforms.php + /      ← GF add-on + custom field class
  elementor.php + /         ← Elementor custom field type
  wpforms.php               ← WPForms (2 hooks)
  formidable.php + /        ← Formidable Forms (PSR-autoloaded class)
  forminator.php            ← Forminator (3 hooks)
  coblocks.php              ← CoBlocks (6 hooks, reCAPTCHA spoof)
  html-forms.php            ← HTML Forms (5 hooks)
  enfold-theme.php          ← Enfold theme (4 hooks)
  wpdiscuz.php              ← wpDiscuz (1 hook)
  wpmembers.php             ← WP-Members (1 hook)
  custom.php                ← Shortcode/manual mode (enqueue scripts)
public/
  altcha.min.js             ← Upstream ALTCHA Svelte web component v2.2.2 (DO NOT EDIT)
  altcha.js                 ← Comment-only companion file (no executable code)
  altcha.css                ← Original plugin CSS (widget wrapper fixes)
  script.js                 ← Original: fixes `name` attr + removes duplicates via MutationObserver
  admin.js                  ← Original: admin settings UI toggle logic
  custom.js                 ← Original: configures widget via window.ALTCHA_WIDGET_ATTRS
languages/                  ← 27 pre-compiled .po/.mo translation files
.github/workflows/
  publish.yml               ← CI: on tag push → deploy to WordPress.org SVN
.wordpress-org/             ← WordPress.org banner/icon/screenshot assets

# Testing / Linting / Build Tooling

**There is none**. No composer.json, no package.json, no PHPUnit, no Jest, no ESLint, no PHPCS, no PHPStan, no Webpack, no Vite. The JS assets in public/ are pre-compiled and committed directly. The only CI is .github/workflows/publish.yml, which fires on a tag push and does nothing except deploy straight to WordPress.org SVN via the 10up deploy action — no test or lint steps.

# Forked vs. Original

File; 	Origin
public/altcha.min.js;	Upstream — ALTCHA Svelte web component v2.2.2 from altcha-org/altcha. Do not edit.
public/altcha.js;	Upstream — comment-only header explaining the source repo
Everything else in public/, includes/, integrations/, admin/, openporte.php;	Original plugin code
README.md;	Fork-modified — rewritten to say "community reconstruction of the retired official plugin"; issue tracker changed to this fork
readme.txt;	Verbatim from upstream release ZIP, but the Installation section still references https://github.com/altcha-org/wordpress-plugin/releases (the retired upstream)

The plugin is a reconstruction of the retired official altcha-org/wordpress-plugin v1. The upstream v2/v3 is no longer open source.

# 5 Gotchas Before Touching This Code

1. Version numbers live in 5 separate places — all must be updated together.

openporte.php (plugin header Version:, Stable tag:, and OPENPORTE_VERSION constant), plus readme.txt (header + new changelog entry). Miss any one and WordPress.org auto-update or the browser cache-buster (OPENPORTE_VERSION is used as the cache-busting query string for all enqueued assets) will be inconsistent.

2. coblocks.php works by spoofing reCAPTCHA — any CoBlocks update can silently break it.

When a CoBlocks form is submitted, the plugin injects a dummy reCAPTCHA token into $_POST, tricks WordPress into thinking Google reCAPTCHA is configured, then intercepts the outbound HTTP call via pre_http_request to run ALTCHA verification instead and return a synthetic {"success":true/false} body. If CoBlocks_Form::GCAPTCHA_VERIFY_URL changes, the intercept misses and the fake token goes to Google (which rejects it), silently breaking all CoBlocks forms.

3. Both wordpress.php and woocommerce.php hook into authenticate at priority 20 — mutual exclusivity depends on WooCommerce's nonce field name staying stable.

They avoid double-firing by checking isset($_POST['woocommerce-login-nonce']). If that nonce field name ever changes in a WooCommerce update, both handlers could fire for the same login request, causing double ALTCHA verification (the second attempt to decode an already-consumed payload would fail).

4. The Enfold Theme integration is invisible to has_active_integrations().

get_integrations() in core.php (which drives the "only enqueue scripts on pages with active integrations" logic) does not include the Enfold Theme option. The Enfold hooks load unconditionally regardless of the toggle. Similarly, WP-Members has no dedicated option key — it reuses the core WordPress registration option.

5. spam_filter_call() has no is_wp_error() guard, and the HMAC verification does not use hash_equals().

core.php:594 accesses $resp['response']['code'] directly. On a network failure, $resp is a WP_Error object, producing PHP notices (though the fail-closed behavior is benign). More critically, verify_server_signature() (line 415) and verify_solution() (line 445) both use === to compare HMAC signatures instead of hash_equals(), leaving them theoretically vulnerable to timing attacks. If you fix the HMAC comparison, be careful not to change the true (raw binary output) flag on hash('sha256', ..., true) at line 413 — that raw byte string is the HMAC input, matching the upstream server's convention; switching to hex would invalidate all signatures.