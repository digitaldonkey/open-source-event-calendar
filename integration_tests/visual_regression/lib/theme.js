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
 *
 * Locally through WP-CLI; in remote mode (lib/wp.js) through the Activate link of wp-admin's Calendar Themes page,
 * logged in with the state content.setup.js stored.
 */
const { request } = require('@playwright/test');
const { wp, site, REMOTE, STORAGE_STATE } = require('./wp');

const THEMES_PAGE = '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes';

/**
 * Themes of the run: OSEC_THEMES, default plana (the plugin's default theme) and vortex (the base theme every
 * other theme builds on).
 *
 * @returns {string[]}
 */
function themes() {
    return (process.env.OSEC_THEMES || 'plana,vortex').split(',').map((t) => t.trim()).filter(Boolean);
}

/**
 * Activates a calendar theme unless it is active, then makes one request: a theme switch leaves the CSS compile to
 * the next request.
 *
 * @param {string} theme
 * @param {import('@playwright/test').Browser} browser Needed in remote mode (wp-admin instead of WP-CLI).
 */
async function activateTheme(theme, browser) {
    if (REMOTE) {
        await withAdminPage(browser, async (page) => {
            await page.goto(THEMES_PAGE);
            const link = page.locator(`.activatelink-${theme}`);
            // The active theme has no Activate link.
            if (await link.count()) {
                await link.first().click();
                await page.waitForLoadState('load');
            }
            await page.goto('/');
        });
        return;
    }
    const rows = JSON.parse(wp([
        'osec',
        'theme',
        'list',
        '--fields=name,status',
        '--format=json',
    ]));
    if (rows.some((row) => row.name === theme && 'active' === row.status)) {
        return;
    }
    wp([
        'osec',
        'theme',
        'activate',
        theme,
    ]);
    const context = await request.newContext({
        baseURL: site().baseURL,
        ignoreHTTPSErrors: true,
    });
    try {
        await context.get('/');
    } finally {
        await context.dispose();
    }
}

/**
 * Whether a calendar theme is the active one.
 *
 * @param {string} theme
 * @param {import('@playwright/test').Browser} browser Needed in remote mode.
 * @returns {Promise<boolean>}
 */
async function isThemeActive(theme, browser) {
    if (REMOTE) {
        return withAdminPage(browser, async (page) => {
            await page.goto(THEMES_PAGE);
            // Listed themes carry an Activate link, the active one does not.
            return 0 === await page.locator(`.activatelink-${theme}`).count()
                && await page.locator('.activatelink').count() > 0;
        });
    }
    return JSON.parse(wp([
        'osec',
        'theme',
        'list',
        '--fields=name,status',
        '--format=json',
    ]))
        .some((row) => row.name === theme && 'active' === row.status);
}

/**
 * Runs a callback with a logged-in wp-admin page of the site under test.
 *
 * @param {import('@playwright/test').Browser} browser
 * @param {function(import('@playwright/test').Page): Promise<*>} callback
 * @returns {Promise<*>} The callback's result.
 */
async function withAdminPage(browser, callback) {
    const context = await browser.newContext({
        baseURL: site().baseURL,
        ignoreHTTPSErrors: true,
        storageState: STORAGE_STATE,
    });
    try {
        return await callback(await context.newPage());
    } finally {
        await context.close();
    }
}

module.exports = {
    themes,
    activateTheme,
    isThemeActive,
};
