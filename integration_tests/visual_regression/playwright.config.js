/**
 * Playwright config of the visual suite. See README.md.
 *
 *   npm run test      compare with the baseline set
 *   npm run review    the same, with a full-page screenshot of every test in the report
 *   npm run update    shoot the baseline set (accept the current look)
 *
 * Env: WP_SANDBOX_DB (site, see lib/wp.js), OSEC_BASELINE_SET (default "local"), OSEC_THEMES (default
 * "plana,vortex"), OSEC_MAX_DIFF_RATIO (share of pixels that may differ, default 0.001), OSEC_REVIEW (set by
 * `npm run review`), PLAYWRIGHT_WS.
 *
 * Projects (workers: 1, in this order): `content` generates the test content and logs in; `admin`, `chromium` (views
 * and print) and `firefox` (print) depend only on it, so a failed shot in one does not skip the others. The specs
 * switch the calendar theme per block (lib/theme.js).
 */
const path = require('path');
const {defineConfig, devices} = require('@playwright/test');
const {site, connectOptions, STORAGE_STATE} = require('./lib/wp');

const {baseURL, uploadsDir} = site();
const outputRoot = path.join(uploadsDir, 'osec-visual-regression');
const baselineSet = (process.env.OSEC_BASELINE_SET || 'local').replace(/[^a-z0-9._-]/gi, '').replace(/^\.+/, '');

/** No login: views are shot as visitors see them (no admin bar). */
const LOGGED_OUT = {cookies: [], origins: []};

module.exports = defineConfig({
    testDir: __dirname,
    workers: 1,
    fullyParallel: false,
    retries: 0,
    timeout: 60000,
    outputDir: path.join(outputRoot, 'test-results'),
    snapshotPathTemplate: path.join(outputRoot, 'baselines', baselineSet, '{projectName}', '{arg}{ext}'),
    reporter: [['list'], ['html', {outputFolder: path.join(outputRoot, 'report'), open: 'never'}]],
    // Share of pixels that may differ (default 0.1 %): map tiles and font rendering vary by single pixels between runs.
    expect: {toHaveScreenshot: {maxDiffPixelRatio: Number(process.env.OSEC_MAX_DIFF_RATIO ?? 0.001)}},
    use: {
        baseURL,
        ignoreHTTPSErrors: true,
        storageState: STORAGE_STATE,
        screenshot: process.env.OSEC_REVIEW ? {mode: 'on', fullPage: true} : 'only-on-failure',
        connectOptions: connectOptions('chromium'),
    },
    projects: [
        // Starts logged out: it writes the login state the admin project uses.
        {name: 'content', testMatch: /content\.setup\.js/, use: {storageState: LOGGED_OUT}},
        {name: 'admin', testMatch: /admin\.spec\.js/, dependencies: ['content'], use: devices['Desktop Chrome']},
        {
            name: 'chromium',
            testMatch: /(visual|print)\.spec\.js/,
            dependencies: ['content'],
            use: {...devices['Desktop Chrome'], storageState: LOGGED_OUT},
        },
        {
            name: 'firefox',
            testMatch: /print\.spec\.js/,
            dependencies: ['content'],
            use: {...devices['Desktop Firefox'], storageState: LOGGED_OUT, connectOptions: connectOptions('firefox')},
        },
    ],
});
