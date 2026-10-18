/**
 * Print checks per calendar theme (lib/theme.js): the calendar's print button for month, week and day, shot full page with print
 * media (decision N5). Runs in the chromium and the firefox project; Chromium also attaches the
 * PDF (page.pdf() is Chromium only) for a look at the page breaks.
 */
const {test, expect} = require('@playwright/test');
const {manifest, calendarPath, printView} = require('./lib/pages');
const {themes, activateTheme} = require('./lib/theme');

/** View paths, read per test (see visual.spec.js). */
const VIEWS = {
    month: (m) => calendarPath(m, 'month', m.anchor),
    week: (m) => calendarPath(m, 'week', m.anchor),
    day: (m) => calendarPath(m, 'oneday', m.events['busy-1'].start.slice(0, 10)),
};

test.use({viewport: {width: 1280, height: 900}});

for (const theme of themes()) {
    test.describe(theme, () => {
        test.beforeAll(() => activateTheme(theme));

        for (const [view, path] of Object.entries(VIEWS)) {
            test(`print ${view}`, async ({page, browserName}, testInfo) => {
                await page.goto(path(manifest()));
                await printView(page);
                await expect(page).toHaveScreenshot([theme, `print-${view}.png`], {
                    fullPage: true,
                    // The printed page URL carries the site's hostname: a set shot in one sandbox stays comparable on another site.
                    mask: [page.locator('.osec-print-url')],
                });
                if ('chromium' === browserName) {
                    await testInfo.attach(`${theme}-print-${view}.pdf`, {
                        body: await page.pdf({preferCSSPageSize: true, printBackground: true}),
                        contentType: 'application/pdf',
                    });
                }
            });
        }
    });
}
