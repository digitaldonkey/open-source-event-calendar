/**
 * The site under test and WP-CLI access to it, for the config and the setup projects.
 *
 *   const {wp, site} = require('./lib/wp');
 *   wp(['osec', 'theme', 'activate', 'plana']);
 *   site().baseURL  // e.g. https://try-pw.ddev-wordpress.ddev.site
 *
 * Env:
 *   WP_SANDBOX_DB  Sandbox database (see the plugin's CLAUDE.md, "Database Safety"); unset: the DDEV dev site.
 *                  Passed on to every WP-CLI call, so the tests and WP-CLI always work on the same site.
 *   DDEV_HOSTNAME  Set by DDEV in the web container; gives the Chromium host-resolver rules.
 *
 * Runs inside the DDEV web container only: WP-CLI is /usr/local/bin/wp, the browser the DDEV `playwright` service.
 */
const {execFileSync} = require('child_process');
const path = require('path');

/** The plugin directory: WP-CLI runs there, so relative dataset paths work. */
const PLUGIN_DIR = path.resolve(__dirname, '../../..');

/** Logged-in browser state, written by content.setup.js, used by every other project. */
const STORAGE_STATE = path.resolve(__dirname, '../.auth/admin.json');

/** Manifest of the generated content (anchor, pages, events by key), written by content.setup.js. */
const MANIFEST = path.resolve(__dirname, '../.auth/manifest.json');

/**
 * Runs WP-CLI against the site under test.
 *
 * @param {string[]} args WP-CLI arguments.
 * @returns {string} Trimmed standard output.
 */
function wp(args) {
    return execFileSync('/usr/local/bin/wp', args, {cwd: PLUGIN_DIR, encoding: 'utf8', env: process.env}).trim();
}

/**
 * Site facts, asked from WordPress once and kept in the environment, so every Playwright worker process (which
 * loads the config again) sees the same values without calling WP-CLI.
 *
 * @returns {{baseURL: string, uploadsDir: string}}
 */
function site() {
    if (!process.env.OSEC_PW_SITE) {
        const facts = JSON.parse(wp(['eval', 'echo wp_json_encode(["baseURL" => home_url(), "uploadsDir" => wp_upload_dir()["basedir"]]);']));
        process.env.OSEC_PW_SITE = JSON.stringify(facts);
    }
    return JSON.parse(process.env.OSEC_PW_SITE);
}

/**
 * Chromium --host-resolver-rules sending every DDEV hostname to ddev-router, as a real browser goes (TLS,
 * X-Forwarded-Proto). Without it, sandbox hostnames resolve inside the playwright container.
 *
 * @returns {string}
 */
function hostRules() {
    return (process.env.DDEV_HOSTNAME || '')
        .split(',')
        .filter(Boolean)
        .map((host) => `MAP ${host} ddev-router`)
        .join(', ');
}

module.exports = {PLUGIN_DIR, STORAGE_STATE, MANIFEST, wp, site, hostRules};
