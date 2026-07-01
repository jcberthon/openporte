# OpenPorte

**OpenPorte is a community-maintained fork of the retired ALTCHA Spam
Protection plugin for WordPress (v1)** — a free, open source, self-hostable,
privacy-friendly CAPTCHA alternative.

Website: https://github.com/jcberthon/openporte

WordPress Plugin Directory: https://wordpress.org/plugins/openporte/

Having troubles?  
Please report in [Issues](https://github.com/jcberthon/openporte/issues) or
use the [WordPress.org Support Forum](https://wordpress.org/support/plugin/openporte/).

> **AI-assisted project.** Architecture, security decisions, and final review
> are mine; AI tools (Claude, Mistral, and others) help with drafting code,
> tests, translations, and documentation — without them, one person couldn't
> keep this fork alive.

## Why this fork?

The original ALTCHA WordPress plugin v1 was open source (GPLv2). Its authors
released a v2/3 that is no longer open source, moved some previously-free
features behind a paywall, and no longer maintain v1 (they recommend
migrating to [v2/3](https://altcha.org)).

OpenPorte continues the v1 line as free software (GPLv2 or later), for people
who want to stay on a fully open-source, self-hosted solution. It is based on
the last GPL release of ALTCHA v1 (1.26.1).

OpenPorte uses the upstream [ALTCHA widget](https://github.com/altcha-org/altcha)
(MIT-licensed) as a bundled dependency.

## Compatibility

Backward-compatible with ALTCHA v1: settings migrate automatically (the
original `altcha_*` options are left in place, so you can roll back), and the
`[altcha]` shortcode, the `altcha/v1` REST namespace and the `altcha_*` hooks
keep working as **deprecated aliases** of their `openporte` equivalents.
Integrations for paid-only plugins (e.g. Enfold) are also **deprecated** and
will be removed.

## Supported Integrations

* CoBlocks
* Contact Form 7
* Elementor Pro Forms (deprecated — paid plugin)
* Enfold Theme (deprecated — paid plugin)
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

## Floating UI

The plugin supports the [Floating UI](https://altcha.org/docs/v2/floating-ui/) but with known limitations:

Currently the Floating UI does not work with:

- Forminator with multi-step forms

## Installation

### WordPress.org Plugin Directory

OpenPorte is listed in the WordPress.org plugin directory. So you can install
it directly from the admin UI on your site.

1. Open the WordPress admin UI
2. Under Plugins → Add Plugin, search for openporte
3. Install it and activate it.
4. Review the settings and enable your integrations

### GitHub release

You can also download the GitHub release and install it via the WordPress
admin UI.

1. Download the `.zip` from the [Releases](https://github.com/jcberthon/openporte/releases).
2. Under Plugins → Add Plugin, click **Upload Plugin** and select the downloaded `.zip` file.
3. Activate the plugin through the 'Plugins' menu in WordPress  
4. Review the settings and enable your integrations

### Modes of Operation

OpenPorte verifies submissions in one of two modes, selected in the settings
(API Mode):

- **Self-hosted** (default) — a proof-of-work challenge is issued and verified by
  your own WordPress site via the REST API. Fully self-contained, no external
  service, no account.
- **Custom** — point the Challenge URL at your own ALTCHA-compatible backend
  (e.g. a self-hosted ALTCHA Sentinel); submissions are verified with your site's
  signing secret.

The paid altcha.org regional SaaS classifier offered by earlier versions has been
removed; both remaining modes are free and self-hostable.

### REST API

This plugin requires the WordPress REST API. If you are using any "Disable REST API" plugins, ensure that the endpoint `/altcha/v1/challenge` (now deprecated) and `/openporte/v1/challenge` is allowed.

### Hooks

The plugin provides several hooks to customize or extend its functionality.
Each hook below is also fired under its old `altcha_*` name as a **deprecated
alias** (via WordPress' deprecated-hook mechanism); use the `openporte_*` names.

#### Filters

* `apply_filters('openporte_challenge_url', $challenge_url)`  
  Override the challenge URL.  
  **Returns:** `string`

* `apply_filters('openporte_integrations', $integrations)`  
  Modify the list of available integrations. Supported values: `captcha`, `captcha_spamfilter`, `shortcode`.  
  **Returns:** `array<string>`

* `apply_filters('openporte_plugin_active', false, $name)`  
  Check if an integration by `$name` is active.  
  **Returns:** `bool`

* `apply_filters('openporte_widget_attrs', $attrs, $mode, $language, $name)`  
  Override widget attributes.  
  **Returns:** `array<string, mixed>`

* `apply_filters('openporte_widget_html', $html, $mode, $language, $name)`  
  Override the entire widget HTML.  
  **Returns:** `string`

* `apply_filters('openporte_translations', $translations, $language)`  
  Override translation strings.  
  **Returns:** `array<string, string>`

#### Actions

* `do_action('openporte_verify_result', $result)`  
  Triggered after payload verification.

  * `$result`: `bool` verification result.  
  * Full server verification payload is available via:

    ```php
    OpenPortePlugin::$instance->spamfilter_result

## License

GPLv2 - see [LICENSE](LICENSE)
