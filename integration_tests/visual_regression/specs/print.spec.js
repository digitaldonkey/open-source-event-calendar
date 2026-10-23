/**
 * Print checks per calendar theme (lib/theme.js): the calendar's print button for month, week and day, shot full
 * page with print media (decision N5). Runs in the chromium and the firefox project; both attach the PDF for a look
 * at the page breaks (Chromium: page.pdf(); Firefox: silent print to a file in the playwright container, fetched as
 * a download). The printed views are listed in screens.js (PRINT, paths from VIEWS).
 */
const { test, expect } = require('@playwright/test');
const { manifest, printView, firefoxPdf, about, compareShot, replaceForShot } = require('../lib/pages');
const { themes, activateTheme } = require('../lib/theme');
const { VIEWS, PRINT } = require('./screens');

test.use({
    viewport: {
        width: 1280,
        height: 900,
    },
});

for (const theme of themes()) {
    test.describe(`Calendar theme ${theme}`, () => {
        test.beforeAll(({ browser }) => activateTheme(theme, browser));

        for (const { view: name, title, replace = [] } of PRINT) {
            const { path } = VIEWS.find((v) => v.name === name);
            const replaced = replace.map((r) => r.label).join(', ');
            test(title, about(
                `The calendar's print button, shot full page with print media, exactly as printed. Replaced by a red
                marker: ${replaced}. The PDF is attached for a look at the page breaks (Chromium: page.pdf();
                Firefox: its own silent print to a file).`,
            ), async ({ page, browserName }, testInfo) => {
                await page.goto(path(manifest()));
                await printView(page);
                await replaceForShot(page, replace);
                // Guard: the print page must show the calendar, or an empty shot would match an empty baseline.
                await expect(page.locator('.ai1ec-calendar').first()).toBeVisible();
                try {
                    await compareShot(page, [
                        theme,
                        `print-${name}.png`,
                    ], { fullPage: true });
                } finally {
                    // The PDF for a look at the page breaks, also when the comparison failed. After the shot: Firefox's
                    // real print fires afterprint, which the calendar's script may react to.
                    await testInfo.attach(`${theme}-print-${name}-${browserName}.pdf`, {
                        body: 'chromium' === browserName
                            ? await page.pdf({
                                preferCSSPageSize: true,
                                printBackground: true,
                            })
                            : await firefoxPdf(page),
                        contentType: 'application/pdf',
                    });
                }
            });
        }
    });
}
