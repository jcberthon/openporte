const { execSync } = require('node:child_process');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');

// How to reach wp-cli. Default goes through the remote wp-env wrapper
// (./wp-env.sh run cli -- wp ...); rsync only happens on `start`, so these
// calls don't race local edits. Override for a local wp-env with:
//   WP_CLI_CMD="wp-env run cli -- wp"
const WP_CLI = process.env.WP_CLI_CMD || './wp-env.sh run cli -- wp';

/**
 * Run a wp-cli command and return trimmed stdout. `args` is a plain string —
 * keep values shell-safe (option names/values used here are alphanumeric).
 */
function wp(args) {
  return execSync(`${WP_CLI} ${args}`, {
    cwd: REPO_ROOT,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  }).trim();
}

/** Set an option, using delete for empty values (safer than passing '' through ssh+docker layers). */
function wpSetOption(name, value) {
  if (value === '' || value === null || value === undefined) {
    try {
      wp(`option delete ${name}`);
    } catch (e) {
      // already absent — fine
    }
  } else {
    wp(`option update ${name} ${value}`);
  }
}

/** Baseline OpenPorte configuration every combo starts from. */
function applyBaseline() {
  wpSetOption('openporte_api', 'selfhosted');
  wpSetOption('openporte_complexity', 'low');
  wpSetOption('openporte_expires', '300');
  wpSetOption('openporte_delay', '');
}

/** Per-combo widget settings. auto: ''|onload|onfocus|onsubmit; floating: 0|1. */
function applyCombo({ auto, floating }) {
  wpSetOption('openporte_auto', auto);
  wpSetOption('openporte_floating', floating ? '1' : '');
}

/** Wait until any widget on the page reports the solved state. */
async function waitForVerified(page, timeout = 90000) {
  await page
    .locator('.altcha[data-state="verified"]')
    .first()
    .waitFor({ state: 'attached', timeout });
}

/** Start solving manually (the auto=disabled flow): tick the widget checkbox. */
async function clickWidgetCheckbox(page) {
  await page.locator('.altcha input[type="checkbox"]').first().click();
}

/**
 * Negative-control helper: strip every widget from the DOM so the form posts
 * with no altcha field, which the server must reject. (Bypasses client-side
 * `required` validation the same way a bot skipping the widget would.)
 */
async function removeWidgets(page) {
  await page.evaluate(() => {
    document.querySelectorAll('altcha-widget').forEach((el) => el.remove());
  });
}

module.exports = {
  wp,
  wpSetOption,
  applyBaseline,
  applyCombo,
  waitForVerified,
  clickWidgetCheckbox,
  removeWidgets,
};
