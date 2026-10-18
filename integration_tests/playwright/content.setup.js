/**
 * Setup project `content`: generates the test content and stores a logged-in browser state for wp-admin pages.
 *
 * `wp osec content generate` replaces what an earlier run created and refuses a site with other calendar data
 * (see tests/data/content/README.md). The manifest goes to .auth/manifest.json for the specs.
 */
const fs = require('fs');
const path = require('path');
const {test: setup} = require('@playwright/test');
const {wp, STORAGE_STATE, MANIFEST} = require('./lib/wp');

setup('generate content', async () => {
    fs.mkdirSync(path.dirname(MANIFEST), {recursive: true});
    wp([
        'osec', 'content', 'generate',
        '--dataset=tests/data/content/dataset.json',
        '--site-settings',
        `--manifest=${MANIFEST}`,
    ]);
});

setup('log in', async ({context, baseURL}) => {
    // Login cookies from WordPress itself: no password needed, works on the dev site and every sandbox.
    const cookies = JSON.parse(wp(['eval-file', path.join(__dirname, 'lib', 'auth-cookies.php')]));
    await context.addCookies(cookies);
    const page = await context.newPage();
    await page.goto(`${baseURL}/wp-admin/`);
    await page.locator('#wpadminbar').waitFor();
    await context.storageState({path: STORAGE_STATE});
});
