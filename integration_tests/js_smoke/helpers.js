/*
 * Non-destructive JS smoke tests: every page that loads an OSEC JS bundle,
 * checked for browser console errors, failed requests and a few interactions.
 *
 * Unlike test/, nothing here installs, uninstalls or trashes anything. It reads
 * whatever the dev site has; `bin/dev-seed-block-test-data.php` gives it events,
 * categories and tags.
 *
 *   npm run test:js                      # all groups
 *   npm run test:js -- --grep @js-calendar
 *   BASE_URL=http://web:9400 npm run test:js     # another site
 *   Env: BASE_URL, CALENDAR_PATH, WP_ADMIN_USER/WP_ADMIN_PASS, SELENIUM_REMOTE_URL, SELENIUM_LOCAL=1
 */
const fs = require('fs');
const assert = require('node:assert');
const {Builder, Browser, By, until, logging} = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

const settings = Object.assign({}, fs.existsSync(__dirname + '/../settings.local.js')
    ? require('../settings.local.js')
    : require('../settings.js'));
// Another site than the settings name, e.g. in CI or a WordPress Playground (SQLite) server.
if (process.env.BASE_URL) {
    settings.domain = process.env.BASE_URL;
}
if (process.env.WP_ADMIN_USER) {
    settings.wpLogin = {admin: {user: process.env.WP_ADMIN_USER, pass: process.env.WP_ADMIN_PASS || ''}};
}

const TIMEOUT = 15000;

// Path of the calendar page, e.g. "/calendar-2/" where an older calendar page is in the trash.
const CAL_PATH = (process.env.CALENDAR_PATH || '/calendar/').replace(/\/?$/, '/');

async function buildDriver() {
    const prefs = new logging.Preferences();
    prefs.setLevel(logging.Type.BROWSER, logging.Level.ALL);
    const options = new chrome.Options()
        .addArguments('--headless=new', '--disable-gpu', '--ignore-certificate-errors')
        .windowSize(settings.screen);
    options.setAcceptInsecureCerts(true);
    const builder = new Builder()
        .forBrowser(Browser.CHROME)
        .setChromeOptions(options)
        .setLoggingPrefs(prefs);
    // DDEV's Selenium grid by default; SELENIUM_LOCAL=1 starts a local Chrome (CI's browsers image).
    if (! process.env.SELENIUM_LOCAL) {
        builder.usingServer(process.env.SELENIUM_REMOTE_URL || 'http://selenium-chrome:4444/wd/hub');
    }
    return builder.build();
}

function url(path) {
    return settings.domain.replace(/\/$/, '') + path;
}

async function login(driver) {
    await driver.get(url('/wp-login.php'));
    const user = await driver.wait(until.elementLocated(By.id('user_login')), TIMEOUT);
    await user.clear();
    await user.sendKeys(settings.wpLogin.admin.user);
    await driver.findElement(By.id('user_pass')).sendKeys(settings.wpLogin.admin.pass);
    await driver.findElement(By.id('wp-submit')).click();
    await driver.wait(until.elementLocated(By.id('wpadminbar')), TIMEOUT);
}

/**
 * Loads a page, discarding earlier log entries, and waits until the given
 * modules have run. Only required modules run: "pages/<page>" and
 * "scripts/common_scripts/page_ready" are defined but never executed, so wait
 * for the page's main module (e.g. "scripts/calendar").
 */
async function open(driver, path, modules = []) {
    await driver.manage().logs().get(logging.Type.BROWSER);
    await driver.get(url(path));
    await driver.wait(
        async () => 'complete' === await driver.executeScript('return document.readyState;'),
        TIMEOUT,
        'page did not finish loading'
    );
    await waitForModules(driver, ['jquery_timely', 'domReady', ...modules]);
}

/**
 * Names of all modules the OSEC requirejs context has executed.
 */
async function definedModules(driver) {
    return driver.executeScript(
        'return window.timely && timely.require.s.contexts._ ? Object.keys(timely.require.s.contexts._.defined) : null;'
    );
}

async function waitForModules(driver, names) {
    await driver.wait(async () => {
        const defined = await definedModules(driver);
        return defined && names.every(n => defined.includes(n));
    }, TIMEOUT, 'modules not defined: ' + names.join(', '));
}

/**
 * Console errors and failed requests since the last call.
 *
 * Ignored: requests the page makes to other sites (map tiles, gravatar) and
 * the favicon, which say nothing about the plugin's JS.
 */
async function assertNoConsoleErrors(driver, context) {
    const entries = await driver.manage().logs().get(logging.Type.BROWSER);
    const host = new URL(settings.domain).host;
    const errors = entries
        .filter(e => e.level.value >= logging.Level.SEVERE.value)
        .map(e => e.message)
        .filter(m => !/favicon\.ico/.test(m))
        .filter(m => !/^https?:\/\//.test(m) || m.includes(host));
    assert.deepStrictEqual(errors, [], `${context}: browser console errors`);
}

async function visible(driver, css, timeout = TIMEOUT) {
    const el = await driver.wait(until.elementLocated(By.css(css)), timeout, `not found: ${css}`);
    await driver.wait(until.elementIsVisible(el), timeout, `not visible: ${css}`);
    return el;
}

/**
 * Clicks an element, looking it up again if an AJAX view reload replaced it in
 * between, and moving the mouse away if a tooltip left by an earlier hover
 * covers it.
 */
async function click(driver, css) {
    for (let attempt = 1; ; attempt++) {
        try {
            const el = await visible(driver, css);
            await driver.executeScript('arguments[0].scrollIntoView({block: "center"});', el);
            await el.click();
            return el;
        } catch (e) {
            if (!['StaleElementReferenceError', 'ElementClickInterceptedError'].includes(e.name) || attempt === 3) {
                throw e;
            }
            if (e.name === 'ElementClickInterceptedError') {
                await driver.actions({async: true}).move({x: 1, y: 1}).perform();
                await driver.wait(
                    () => driver.executeScript("return !document.querySelector('.ai1ec-tooltip.ai1ec-in');"),
                    TIMEOUT
                ).catch(() => {});
            }
        }
    }
}

module.exports = {
    settings, TIMEOUT, CAL_PATH, By, until, assert,
    buildDriver, url, login, open, definedModules, waitForModules, assertNoConsoleErrors, visible, click,
};
