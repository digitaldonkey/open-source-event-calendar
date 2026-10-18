/**
 * Playwright config of the visual suite. See README.md.
 *
 *   npm run test      compare with the baseline set
 *   npm run review    the same, with a full-page screenshot of every test in the report
 *   npm run update    shoot the baseline set (accept the current look)
 *
 * Env: WP_SANDBOX_DB (site, see lib/wp.js), OSEC_BASELINE_SET (default "local"), OSEC_THEMES (default
 * "plana,gamma"), OSEC_REVIEW (set by `npm run review`).
 *
 * Projects run one after the other (workers: 1), chained by dependencies: `content` generates the test content and
 * logs in, then per calendar theme `setup-<theme>` activates it and `<theme>` runs visual.spec.js. Two themes cannot
 * be active on one site at the same time, so the chain is the only order that works.
 */
const path = require('path');
const {defineConfig} = require('@playwright/test');
const {site, hostRules, STORAGE_STATE} = require('./lib/wp');

const {baseURL, uploadsDir} = site();
const outputRoot = path.join(uploadsDir, 'osec-playwright');
const baselineSet = (process.env.OSEC_BASELINE_SET || 'local').replace(/[^a-z0-9_-]/gi, '');
const themes = (process.env.OSEC_THEMES || 'plana,gamma').split(',').map((t) => t.trim()).filter(Boolean);

/** No login: views are shot as visitors see them (no admin bar). */
const LOGGED_OUT = {cookies: [], origins: []};

const themeProjects = [];
themes.forEach((theme, i) => {
    themeProjects.push(
        {
            name: `setup-${theme}`,
            testMatch: /theme\.setup\.js/,
            dependencies: [0 === i ? 'content' : themes[i - 1]],
            metadata: {theme},
        },
        {
            name: theme,
            testMatch: /visual\.spec\.js/,
            dependencies: [`setup-${theme}`],
            metadata: {theme},
            use: {storageState: LOGGED_OUT},
        },
    );
});

module.exports = defineConfig({
    testDir: __dirname,
    workers: 1,
    fullyParallel: false,
    retries: 0,
    timeout: 60000,
    outputDir: path.join(outputRoot, 'test-results'),
    snapshotPathTemplate: path.join(outputRoot, 'baselines', baselineSet, '{projectName}', '{arg}{ext}'),
    reporter: [['list'], ['html', {outputFolder: path.join(outputRoot, 'report'), open: 'never'}]],
    expect: {toHaveScreenshot: {maxDiffPixelRatio: 0}},
    use: {
        baseURL,
        ignoreHTTPSErrors: true,
        storageState: STORAGE_STATE,
        screenshot: process.env.OSEC_REVIEW ? {mode: 'on', fullPage: true} : 'only-on-failure',
        connectOptions: {
            wsEndpoint: process.env.PLAYWRIGHT_WS || 'ws://playwright:3000/',
            headers: {'x-playwright-launch-options': JSON.stringify({args: [`--host-resolver-rules=${hostRules()}`]})},
        },
    },
    projects: [
        // Starts logged out: it writes the login state the wp-admin project uses.
        {name: 'content', testMatch: /content\.setup\.js/, use: {storageState: LOGGED_OUT}},
        ...themeProjects,
    ],
});
