/**
 * Capture full-page screenshots of calendar views at several viewport widths.
 *
 * The theme must already be switched (and its CSS cache busted) before this runs - that's
 * the caller's job, since it also writes to the DB (see responsive-compare.sh).
 *
 * Uses CDP device-metrics emulation rather than resizing the actual browser window:
 * Chrome enforces a minimum real window width (~500px) that a narrow phone width like 375
 * can never reach via window.setRect, but the emulated viewport isn't subject to that floor.
 *
 * Usage: node capture.js <version-label> <theme> <outDir> [widths...]
 * Env: BASE_URL, DATE, SELENIUM_CHROME_URL
 *      VIEWS  comma list of calendar actions, default month,week,oneday,agenda. Each is shot
 *             to <theme>-<view>-<version-label>-<width>.png.
 *      VIEW_DATES  per-view exact_date overrides, e.g. oneday=2026-9-23,week=21-9-2026; views
 *             not listed use DATE.
 *      DATE_AS_TIMESTAMP=1  send every date as a Unix timestamp instead of as written. For
 *             releases up to 1.1.14, which parse neither d-m-Y nor Y-m-d there and silently
 *             fall back to today - so the shot would be of a different day than current's.
 */
const path = require('path');
const fs = require('fs');
const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

const [versionLabel, theme, outDir] = process.argv.slice(2);
const widths = (process.argv.length > 5 ? process.argv.slice(5) : ['320', '768', '1440']).map(Number);
const base = (process.env.BASE_URL || 'https://ddev-wordpress.ddev.site') + '/calendar/';
const date = process.env.DATE || '15-9-2026';
const views = (process.env.VIEWS || 'month,week,oneday,agenda').split(',');
const viewDates = Object.fromEntries((process.env.VIEW_DATES || '')
    .split(',').filter(Boolean).map((pair) => pair.split('=')));

// d-m-Y or Y-m-d -> Unix timestamp of that day at 12:00 UTC: midday keeps it on the same
// calendar day in any site timezone within +/-11h.
function toTimestamp(value) {
    const parts = value.split('-').map(Number);
    const [y, m, d] = parts[0] > 31 ? parts : [parts[2], parts[1], parts[0]];
    if (!y || !m || !d) {
        throw new Error(`cannot convert exact_date "${value}" to a timestamp`);
    }
    return Math.floor(Date.UTC(y, m - 1, d, 12) / 1000);
}

function dateFor(view) {
    const value = viewDates[view] || date;
    return process.env.DATE_AS_TIMESTAMP ? toTimestamp(value) : value;
}

if (!versionLabel || !theme || !outDir) {
    console.error('Usage: node capture.js <version-label> <theme> <outDir> [widths...]');
    process.exit(1);
}

function build() {
    return new Builder().forBrowser('chrome')
        .setChromeOptions(new chrome.Options()
            .addArguments('--ignore-certificate-errors', '--hide-scrollbars', '--window-size=1600,1200'))
        .usingServer(process.env.SELENIUM_CHROME_URL || 'http://selenium-chrome:4444/wd/hub')
        .build();
}

async function setViewport(driver, width, height) {
    await driver.sendAndGetDevToolsCommand('Emulation.setDeviceMetricsOverride', {
        width,
        height,
        deviceScaleFactor: 1,
        mobile: width < 768,
    });
}

async function shootView(driver, view) {
    // The device-metrics override must be applied after navigation, not before: set
    // before driver.get(), it's silently dropped for the incoming document (window
    // still reports the real window size) even though the CDP call itself succeeds.
    await driver.get(`${base}action~${view}/exact_date~${dateFor(view)}/`);
    // Every view renders its container as .ai1ec-<action>-view (week reuses oneday.twig
    // but still gets its own class).
    await driver.wait(until.elementLocated(By.css(`.ai1ec-${view}-view`)), 45000);

    for (const width of widths) {
        // Two-step: first the width alone (reflows the responsive CSS), then grow the
        // emulated height to match the now-reflowed content, so the screenshot needs no
        // scrolling and no clipping.
        await setViewport(driver, width, 900);
        await driver.sleep(500);
        const contentHeight = await driver.executeScript(
            'return Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);'
        );
        await setViewport(driver, width, Math.min(contentHeight, 10000));
        // Park the pointer in the corner: once the page reflows under wherever it was left, it
        // can land on an event and open its hover popover - in one version's shot and not the
        // other's (seen in week and day at 1440px), which reads as a difference that isn't one.
        await driver.sendAndGetDevToolsCommand('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 0, y: 0 });
        await driver.sleep(400);

        const res = await driver.sendAndGetDevToolsCommand('Page.captureScreenshot', { format: 'png' });
        const file = path.join(outDir, `${theme}-${view}-${versionLabel}-${width}.png`);
        fs.writeFileSync(file, Buffer.from(res.data, 'base64'));
        // What the page really rendered with, next to the shot: the setting read back from the
        // DB only proves what was stored, not what the stylesheet the page loaded was built from.
        const facts = await driver.executeScript(`
            // The .timely around this view, where the "Base font size" setting lands. Some
            // themes' templates have none outside month (plana), so record that as null rather
            // than grabbing an unrelated one, and the view container's own size either way.
            var view = document.querySelector('.ai1ec-${view}-view');
            var t = view && view.closest('.timely');
            var link = document.querySelector('link[href*="osec-compiled"], link[href*="osec_compiled"], link[href*="osec-css-cache"], link[href*="osec_parsed"]');
            return {
                timelyFontSize: t ? getComputedStyle(t).fontSize : null,
                viewFontSize: view ? getComputedStyle(view).fontSize : null,
                css: link ? link.getAttribute('href') : (document.getElementById('osec-frontend-css-inline-css') ? 'inline' : 'none'),
                shownDate: (document.querySelector('.ai1ec-calendar-title, .ai1ec-clndr-title, .ai1ec-date-title') || {}).textContent || null,
            };`);
        fs.writeFileSync(file.replace(/\.png$/, '.json'), JSON.stringify({ ...facts, url: await driver.getCurrentUrl() }, null, 1));
        console.log(file + '  .timely ' + facts.timelyFontSize + '  css ' + facts.css);
    }
}

(async () => {
    fs.mkdirSync(outDir, { recursive: true });
    const driver = await build();
    let failed = 0;
    try {
        for (const view of views) {
            try {
                await shootView(driver, view);
            } catch (e) {
                // Keep going: one broken view shouldn't cost the shots of the others.
                console.error(`${theme}/${view}/${versionLabel}: ${e.message}`);
                failed++;
            }
        }
    } finally {
        await driver.quit();
    }
    if (failed) {
        process.exit(1);
    }
})().catch((e) => {
    console.error(`${theme}/${versionLabel}: ${e.message}`);
    process.exit(1);
});
