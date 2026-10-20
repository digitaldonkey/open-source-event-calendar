/**
 * Playwright config of the visual suite. See README.md.
 *
 *   npm run test      compare with the baseline set
 *   npm run review    the same, with a full-page screenshot of every test in the report
 *   npm run update    shoot the baseline set (accept the current look)
 *
 * Env: WP_SANDBOX_DB (site, see lib/wp.js), OSEC_BASELINE_SET (default "local"), OSEC_THEMES (default
 * "plana,vortex"), OSEC_MAX_DIFF_PIXELS (pixels per shot that may differ, default 20), OSEC_REVIEW (set by
 * `npm run review`), PLAYWRIGHT_WS.
 *
 * Projects (workers: 1, in this order): `content` generates the test content and logs in; `admin`, `chromium` (views
 * and print) and `firefox` (print) depend only on it, so a failed shot in one does not skip the others. The specs
 * switch the calendar theme per block (lib/theme.js).
 */
const path = require('path');
const { defineConfig, devices } = require('@playwright/test');
const { site, connectOptions, STORAGE_STATE } = require('./lib/wp');

const { baseURL, uploadsDir } = site();
const outputRoot = path.join(uploadsDir, 'osec-visual-regression');
const baselineSet = (process.env.OSEC_BASELINE_SET || 'local').replace(/[^a-z0-9._-]/gi, '').replace(/^\.+/, '');

/** No login: views are shot as visitors see them (no admin bar). */
const LOGGED_OUT = {
    cookies: [],
    origins: [],
};

module.exports = defineConfig({
    testDir: path.join(__dirname, 'specs'),
    workers: 1,
    fullyParallel: false,
    retries: 0,
    timeout: 60000,
    // Per baseline set, so a run against one set never replaces the report of a comparison with another.
    outputDir: path.join(outputRoot, 'test-results', baselineSet),
    snapshotPathTemplate: path.join(outputRoot, 'baselines', baselineSet, '{projectName}', '{arg}{ext}'),
    reporter: [
        ['list'],
        [
            'html',
            {
                outputFolder: path.join(outputRoot, 'report', baselineSet),
                open: 'never',
            },
        ],
        // The review page: baseline | diff | current per screenshot (lib/review-reporter.js).
        [
            './lib/review-reporter.js',
            {
                outputFolder: path.join(outputRoot, 'review', baselineSet),
                baselinesFolder: path.join(outputRoot, 'baselines', baselineSet),
                set: baselineSet,
                site: baseURL,
            },
        ],
    ],
    // Pixels per shot that may differ, the same for every shot (decision A2): three runs at 0 differed by 0 px
    // (2026-10-20); the smallest real change seen was 186 px. A share of the image (0.1 % before) let changes of up
    // to 6,886 px pass on tall pages. The per-pixel color tolerance stays Playwright's `threshold` default (0.2).
    expect: {
        toHaveScreenshot: {
            maxDiffPixels: Number(process.env.OSEC_MAX_DIFF_PIXELS ?? 20),
            // Playwright's defaults, set on purpose (maintainer 2026-10-19): CSS animations and transitions frozen,
            // text cursor hidden.
            animations: 'disabled',
            caret: 'hide',
        },
    },
    use: {
        baseURL,
        ignoreHTTPSErrors: true,
        storageState: STORAGE_STATE,
        screenshot: process.env.OSEC_REVIEW ? {
            mode: 'on',
            fullPage: true,
        } : 'only-on-failure',
        connectOptions: connectOptions('chromium'),
    },
    projects: [
        // Starts logged out: it writes the login state the admin project uses.
        {
            name: 'content',
            testMatch: /content\.setup\.js/,
            use: { storageState: LOGGED_OUT },
        },
        {
            name: 'admin',
            testMatch: /admin\.spec\.js/,
            dependencies: ['content'],
            use: devices['Desktop Chrome'],
        },
        {
            name: 'chromium',
            testMatch: /(visual|print)\.spec\.js/,
            dependencies: ['content'],
            use: {
                ...devices['Desktop Chrome'],
                storageState: LOGGED_OUT,
            },
        },
        {
            name: 'firefox',
            testMatch: /print\.spec\.js/,
            dependencies: ['content'],
            use: {
                ...devices['Desktop Firefox'],
                storageState: LOGGED_OUT,
                connectOptions: connectOptions('firefox'),
            },
        },
    ],
});
