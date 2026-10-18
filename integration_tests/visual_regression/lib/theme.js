/**
 * Calendar theme handling for the specs: which themes a run covers, and activating one.
 *
 *   for (const theme of themes()) {
 *       test.describe(theme, () => {
 *           test.beforeAll(() => activateTheme(theme));
 *           ...
 *       });
 *   }
 *
 * Only one calendar theme can be active on a site, so the specs run per theme in blocks (workers: 1, files and
 * tests in order); each block activates its theme first. A failed shot does not stop the other blocks.
 */
const {request} = require('@playwright/test');
const {wp, site} = require('./wp');

/**
 * Themes of the run: OSEC_THEMES, default plana and gamma (decision 6).
 *
 * @returns {string[]}
 */
function themes() {
    return (process.env.OSEC_THEMES || 'plana,gamma').split(',').map((t) => t.trim()).filter(Boolean);
}

/**
 * Activates a calendar theme unless it is active, then makes one request: a theme switch leaves the CSS compile to
 * the next request.
 *
 * @param {string} theme
 */
async function activateTheme(theme) {
    const rows = JSON.parse(wp(['osec', 'theme', 'list', '--fields=name,status', '--format=json']));
    if (rows.some((row) => row.name === theme && 'active' === row.status)) {
        return;
    }
    wp(['osec', 'theme', 'activate', theme]);
    const context = await request.newContext({baseURL: site().baseURL, ignoreHTTPSErrors: true});
    try {
        await context.get('/');
    } finally {
        await context.dispose();
    }
}

/**
 * Name of the active calendar theme.
 *
 * @returns {string|undefined}
 */
function activeTheme() {
    return JSON.parse(wp(['osec', 'theme', 'list', '--fields=name,status', '--format=json']))
        .find((row) => 'active' === row.status)?.name;
}

module.exports = {themes, activateTheme, activeTheme};
