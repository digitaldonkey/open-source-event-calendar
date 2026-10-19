/**
 * Setup project `content`: generates the test content and stores a logged-in browser state for wp-admin pages.
 *
 * `wp osec content generate` replaces what an earlier run created and refuses a site with other calendar data
 * (see tests/data/content/README.md). The manifest goes to .auth/manifest.json for the specs. OSEC_ANCHOR pins the
 * anchor (default: the generator's, the week with 15 May of next year).
 *
 * Remote mode (OSEC_REMOTE_URL, lib/wp.js): the content was imported with the site, so nothing is generated, and the
 * login goes through wp-login.php with the shipped password.
 */
const fs = require('fs');
const path = require('path');
const {test: setup} = require('@playwright/test');
const {wp, STORAGE_STATE, MANIFEST, REMOTE, remotePassword} = require('./lib/wp');
const {about} = require('./lib/pages');

setup('Generate the test content', about(
    'Runs `wp osec content generate --site-settings` with tests/data/content/dataset.json: replaces the content of an '
    + 'earlier run, sets the calendar page, theme and settings, and writes the manifest (page paths, event IDs) the '
    + 'other tests read. Fails on a site with calendar data the generator did not create.',
), async () => {
    setup.skip(!!REMOTE, 'Remote mode: the content was imported with the site.');
    fs.mkdirSync(path.dirname(MANIFEST), {recursive: true});
    wp([
        'osec', 'content', 'generate',
        '--dataset=tests/data/content/dataset.json',
        '--site-settings',
        `--manifest=${MANIFEST}`,
        ...(process.env.OSEC_ANCHOR ? [`--anchor=${process.env.OSEC_ANCHOR}`] : []),
    ]);
});

setup('Log in as the first administrator', about(
    'Takes login cookies from WordPress (lib/auth-cookies.php, no password) and stores the browser state for the '
    + 'wp-admin tests. The calendar views are shot logged out.',
), async ({context, baseURL}) => {
    const page = await context.newPage();
    if (REMOTE) {
        // No WP-CLI there: the login form with the password shipped with the imported database.
        await page.goto(`${baseURL}/wp-login.php`);
        await page.locator('#user_login').fill('admin');
        await page.locator('#user_pass').fill(remotePassword());
        await page.locator('#wp-submit').click();
    } else {
        // Login cookies from WordPress itself: no password needed, works on the dev site and every sandbox.
        await context.addCookies(JSON.parse(wp(['eval-file', path.join(__dirname, 'lib', 'auth-cookies.php')])));
        await page.goto(`${baseURL}/wp-admin/`);
    }
    await page.locator('#wpadminbar').waitFor();
    await context.storageState({path: STORAGE_STATE});
});
