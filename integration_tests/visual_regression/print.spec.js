/**
 * Print checks per calendar theme (lib/theme.js): the calendar's print button for month, week and day, shot full page with print
 * media (decision N5). Runs in the chromium and the firefox project; Chromium also attaches the
 * PDF (page.pdf() is Chromium only) for a look at the page breaks.
 */
const {test, expect} = require('@playwright/test');
const {manifest, calendarPath, printView, about} = require('./lib/pages');
const {themes, activateTheme} = require('./lib/theme');

/** View paths, read per test (see visual.spec.js). */
const VIEWS = {
    month: {title: 'Print: month view', path: (m) => calendarPath(m, 'month', m.anchor)},
    week: {title: 'Print: week view', path: (m) => calendarPath(m, 'week', m.anchor)},
    day: {title: 'Print: busy day', path: (m) => calendarPath(m, 'oneday', m.events['busy-1'].start.slice(0, 10))},
};

test.use({viewport: {width: 1280, height: 900}});

for (const theme of themes()) {
    test.describe(`Calendar theme ${theme}`, () => {
        test.beforeAll(({browser}) => activateTheme(theme, browser));

        for (const [view, {title, path}] of Object.entries(VIEWS)) {
            test(title, about(
                'The calendar\'s print button, shot full page with print media (the printed URL blanked: it carries the '
                + 'hostname). Chromium also attaches the PDF for a look at the page breaks.',
            ), async ({page, browserName}, testInfo) => {
                await page.goto(path(manifest()));
                await printView(page);
                // Guard: the print page must show the calendar, or an empty shot would match an empty baseline.
                await expect(page.locator('.ai1ec-calendar').first()).toBeVisible();
                // The printed URL carries the site's hostname: blanked, so a set shot on one site stays comparable on
                // another. CSS, not Playwright's mask: the mask leaves the print page empty.
                await page.addStyleTag({content: '.osec-print-url { visibility: hidden !important; }'});
                await expect(page).toHaveScreenshot([theme, `print-${view}.png`], {fullPage: true});
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
