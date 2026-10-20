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
 *   OSEC_REMOTE_URL, OSEC_REMOTE_DIR
 *                  Remote mode: shoot a site WP-CLI cannot reach (e.g. another DDEV project running an older release
 *                  with an imported content database). OSEC_REMOTE_DIR holds its manifest.json and
 *                  admin-password.txt (user `admin`). No content generation; login and theme switching go through
 *                  wp-admin. The local site (WP_SANDBOX_DB) only gives the uploads folder for the output.
 *   DDEV_HOSTNAME  Set by DDEV in the web container; the hostnames sent to ddev-router (connectOptions()).
 *
 * Runs inside the DDEV web container only: WP-CLI is /usr/local/bin/wp, the browser the DDEV `playwright` service.
 */
const { execFileSync } = require('child_process');
const path = require('path');

/** The plugin directory: WP-CLI runs there, so relative dataset paths work. */
const PLUGIN_DIR = path.resolve(__dirname, '../../..');

/** Logged-in browser state, written by specs/content.setup.js, used by every other project. */
const STORAGE_STATE = path.resolve(__dirname, '../.auth/admin.json');

/** Where Firefox's silent print writes its PDF, inside the playwright container (connectOptions(), lib/pages.js). */
const FIREFOX_PDF = '/tmp/osec-firefox-print.pdf';

/** Remote mode (see the header), or null. */
const REMOTE = process.env.OSEC_REMOTE_URL
    ? {
        url: process.env.OSEC_REMOTE_URL.replace(/\/$/, ''),
        dir: path.resolve(process.env.OSEC_REMOTE_DIR || '.'),
    }
    : null;

/**
 * Manifest of the content (anchor, pages, events by key): written by specs/content.setup.js, or shipped with the
 * imported database in remote mode.
 */
const MANIFEST = REMOTE ? path.join(REMOTE.dir, 'manifest.json') : path.resolve(__dirname, '../.auth/manifest.json');

/**
 * Runs WP-CLI against the site under test.
 *
 * @param {string[]} args WP-CLI arguments.
 * @returns {string} Trimmed standard output.
 * @throws {Error} With WP-CLI's error output (e.g. "Error: ...") when the command fails.
 */
function wp(args) {
    try {
        return execFileSync('/usr/local/bin/wp', args, {
            cwd: PLUGIN_DIR,
            encoding: 'utf8',
            env: process.env,
            stdio: [
                'ignore',
                'pipe',
                'pipe',
            ],
        }).trim();
    } catch (e) {
        throw new Error((e.stderr || '').trim() || e.message);
    }
}

/**
 * Site facts, asked from WordPress once and kept in the environment, so every Playwright worker process (which
 * loads the config again) sees the same values without calling WP-CLI.
 *
 * @returns {{baseURL: string, uploadsDir: string}}
 */
function site() {
    if (!process.env.OSEC_PW_SITE) {
        const facts = JSON.parse(wp([
            'eval',
            'echo wp_json_encode(["baseURL" => home_url(), "uploadsDir" => wp_upload_dir()["basedir"]]);',
        ]));
        if (REMOTE) {
            facts.baseURL = REMOTE.url;
        }
        process.env.OSEC_PW_SITE = JSON.stringify(facts);
    }
    return JSON.parse(process.env.OSEC_PW_SITE);
}

/**
 * Playwright connectOptions sending the browser to the DDEV sites through ddev-router, as a real browser goes (TLS,
 * X-Forwarded-Proto; WordPress loops on wp-admin without it). Inside the playwright container the DDEV hostnames
 * resolve to 127.0.0.1. Needs the service's `run-server --unsafe`, which passes these launch options on.
 *
 * Chromium: --host-resolver-rules for the DDEV hostnames (DDEV_HOSTNAME). Firefox has no such option:
 * network.dns.forceResolve sends every hostname to the router, external ones too - fine for pages that load
 * nothing from the internet (print views), not for maps. Firefox also prints silently to FIREFOX_PDF.
 *
 * @param {'chromium'|'firefox'} browser
 * @returns {{wsEndpoint: string, headers: Object<string, string>}}
 */
function connectOptions(browser) {
    const launch = 'firefox' === browser
        ? {
            firefoxUserPrefs: {
                'network.dns.forceResolve': routerIp(),
                // window.print() saves a PDF without a dialog, inside the playwright container (lib/pages.js).
                'print.always_print_silent': true,
                'print.show_print_progress': false,
                'print_printer': 'Mozilla Save to PDF',
                'print.printer_Mozilla_Save_to_PDF.print_to_file': true,
                'print.printer_Mozilla_Save_to_PDF.print_to_filename': FIREFOX_PDF,
            },
        }
        : {
            args: [
                `--host-resolver-rules=${[
                    ...(process.env.DDEV_HOSTNAME || '').split(','),
                    REMOTE ? new URL(REMOTE.url).hostname : '',
                ]
                    .filter(Boolean)
                    .map((host) => `MAP ${host} ddev-router`)
                    .join(', ')}`,
            ],
        };
    return {
        wsEndpoint: process.env.PLAYWRIGHT_WS || 'ws://playwright:3000/',
        headers: { 'x-playwright-launch-options': JSON.stringify(launch) },
    };
}

/**
 * IP of ddev-router on the DDEV network (the playwright service is on the same network), kept in the environment
 * for the worker processes.
 *
 * @returns {string}
 */
function routerIp() {
    if (!process.env.OSEC_PW_ROUTER_IP) {
        process.env.OSEC_PW_ROUTER_IP = execFileSync('getent', [
            'hosts',
            'ddev-router',
        ], { encoding: 'utf8' }).split(/\s+/)[0];
    }
    return process.env.OSEC_PW_ROUTER_IP;
}

/**
 * Password of the user `admin` of the remote site (admin-password.txt in OSEC_REMOTE_DIR).
 *
 * @returns {string}
 */
function remotePassword() {
    return require('fs').readFileSync(path.join(REMOTE.dir, 'admin-password.txt'), 'utf8').trim();
}

module.exports = {
    PLUGIN_DIR,
    STORAGE_STATE,
    MANIFEST,
    REMOTE,
    FIREFOX_PDF,
    wp,
    site,
    connectOptions,
    remotePassword,
};
