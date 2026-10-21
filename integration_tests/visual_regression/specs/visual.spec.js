/**
 * Visual checks per calendar theme (lib/theme.js), in Chromium, logged out. See README.md.
 *
 * Every page is compared as a shot of its <main> (the plugin's output with page title and theme margins, no theme
 * header/footer): agenda, week, day and month at 320, 480 and 1280 px; the DST-end day expanded; two single events;
 * the block and shortcode pages. The lists are in screens.js; print lives in print.spec.js.
 */
const { test, expect } = require('@playwright/test');
const { themes, activateTheme, isThemeActive } = require('../lib/theme');
const { manifest, calendarPath, lastSunday, settle, about, compareShot } = require('../lib/pages');
const { WIDTHS, VIEWS, SINGLES, PAGES } = require('./screens');

/**
 * What every front-end shot shows: the page's <main> - the plugin's output with the page title and the theme's
 * margins, without the theme's header and footer. All runs use the same WordPress theme (Twenty Twenty-Five), which
 * has exactly one <main>; there is deliberately no fallback for other themes.
 */
const MAIN = 'main';

for (const theme of themes()) {
    test.describe(`Calendar theme ${theme}`, () => {
        test.beforeAll(({ browser }) => activateTheme(theme, browser));

        test('The calendar theme is active', about(
            `Checks that "${theme}" is the active calendar theme before its screenshots (the specs switch themes per
            block).`,
        ), async ({ browser }) => {
            expect(await isThemeActive(theme, browser)).toBe(true);
        });

        for (const width of WIDTHS) {
            test.describe(`${width} px wide`, () => {
                test.use({
                    viewport: {
                        width,
                        height: 900,
                    },
                    isMobile: width < 800,
                    hasTouch: width < 800,
                });

                for (const { name, title, description, path } of VIEWS) {
                    const emulation = width < 800 ? ', phone emulation' : '';
                    test(title, about(`${description} Shot of <main>${emulation}.`), async ({ page }) => {
                        await page.goto(path(manifest()));
                        await settle(page);
                        await compareShot(page.locator(MAIN), [
                            theme,
                            `${name}-${width}.png`,
                        ]);
                    });
                }
            });
        }

        test('Day view: end of DST, full day revealed', about(
            `The last Sunday of October (25 hours in Europe/Berlin) with the "show full day" button clicked: the night
            bus series at 00:30 and two events at 02:15, one in summer time and one in standard time (side finding
            T3: the grid has 24 rows).`,
        ), async ({ page }) => {
            const m = manifest();
            await page.goto(calendarPath(m, 'oneday', lastSunday(Number(m.anchor.slice(0, 4)), 10)));
            await settle(page);
            await page.locator('.ai1ec-reveal-full-day button').click();
            await settle(page);
            await compareShot(page.locator(MAIN), [
                theme,
                'day-dst-end.png',
            ]);
        });

        for (const { key, title, description } of SINGLES) {
            const details = `${description} Shot of <main> (title, details, description, post navigation).`;
            test(title, about(details), async ({ page }) => {
                await page.goto(`/?p=${manifest().events[key].id}`);
                await settle(page);
                await compareShot(page.locator(MAIN), [
                    theme,
                    `single-${key}.png`,
                ]);
            });
        }

        for (const { key, title, description } of PAGES) {
            test(title, about(`${description} Shot of <main>.`), async ({ page }) => {
                await page.goto(manifest().pages[key].path);
                await settle(page);
                await compareShot(page.locator(MAIN), [
                    theme,
                    `page-${key}.png`,
                ]);
            });
        }
    });
}
