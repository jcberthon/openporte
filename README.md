# OpenPorte

**OpenPorte is a community-maintained fork of the retired ALTCHA Spam
Protection plugin for WordPress (v1)** — a free, open source, self-hostable,
privacy-friendly CAPTCHA alternative.

Website: https://altcha.org

WordPress Plugin Directory: none yet

Having troubles? Please report in [Issues](https://github.com/jcberthon/openporte/issues).

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

Backward-compatible with ALTCHA v1: settings migrate automatically, the
`[altcha]` shortcode and all existing hooks keep working. Integrations for
paid-only plugins (e.g. Enfold) are **deprecated** and will be removed.

## Supported Integrations

* CoBlocks
* Contact Form 7
* Elementor Pro Forms
* Enfold Theme
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
* Custom HTML (with a short code `[altcha]` (deprecated) or `[openporte])

## Floating UI

The plugin supports the [Floating UI](https://altcha.org/docs/v2/floating-ui/) but with known limitations:

Currently the Floating UI does not work with:

- Forminator with multi-step forms

## Installation

You cannot install version 1 currently by searching the plugin directory (at work). Alternatively, install the plugin manually:

1. Download the `.zip` from the [Releases](https://github.com/jcberthon/openporte/releases).
2. Upload `altcha` folder to the `/wp-content/plugins/` directory  
3. Activate the plugin through the 'Plugins' menu in WordPress  
4. Review the settings and enable your integrations

### Mode of Operation (to be updated)

_Note: TODO add custom mode, as only the paid altcha.org SaaS classifier is removed; keep self-hosted PoW and custom self-hostable backend. Description below requires an update._

There is only a self-hosted mode, which is enabled after activation. No additional setup is required, except enabling the integrations you need in the plugin settings.

### REST API

This plugin requires the WordPress REST API. If you are using any "Disable REST API" plugins, ensure that the endpoint `/altcha/v1/challenge` (now deprecated) and `/openporte/v1/challenge` is allowed.

### Hooks

The plugin provides several hooks to customize or extend its functionality.

#### Filters

* `apply_filters('altcha_challenge_url', $challenge_url)`  
  Override the challenge URL.  
  **Returns:** `string`

* `apply_filters('altcha_integrations', $integrations)`  
  Modify the list of available integrations. Supported values: `captcha`, `captcha_spamfilter`, `shortcode`.  
  **Returns:** `array<string>`

* `apply_filters('altcha_plugin_active', false, $name)`  
  Check if an integration by `$name` is active.  
  **Returns:** `bool`

* `apply_filters('altcha_widget_attrs', $attrs, $mode, $language, $name)`  
  Override widget attributes.  
  **Returns:** `array<string, mixed>`

* `apply_filters('altcha_widget_html', $html, $mode, $language, $name)`  
  Override the entire widget HTML.  
  **Returns:** `string`

* `apply_filters('altcha_translations', $translations, $language)`  
  Override translation strings.  
  **Returns:** `array<string, string>`

#### Actions

* `do_action('altcha_verify_result', $result)`  
  Triggered after payload verification.

  * `$result`: `bool` verification result.  
  * Full server verification payload is available via:

    ```php
    AltchaPlugin::$instance->spamfilter_result

## License

GPLv2