/**
 * Visual checks per calendar theme (lib/theme.js), in Chromium, logged out. See README.md.
 *
 * Calendar views are compared as element shots of #osec-calendar-view (decision N13: a layout shift around the
 * calendar would otherwise mark everything): agenda, week, day and month at 320, 480 and 1280 px; the DST-end day
 * expanded; two single events; the block and shortcode pages. Print lives in print.spec.js.
 */
const {test, expect} = require('@playwright/test');
const {themes, activateTheme, activeTheme} = require('./lib/theme');
const {manifest, calendarPath, lastSunday, settle} = require('./lib/pages');

/** Widths below 800 px get mobile emulation (viewport meta tag, touch). */
const WIDTHS = [320, 480, 1280];

/**
 * View paths. Read per test: spec files load before the content setup runs, so a manifest read at load time would
 * be missing (first run) or hold the previous run's post IDs.
 */
const VIEWS = {
    agenda: (m) => calendarPath(m, 'agenda', m.anchor),
    week: (m) => calendarPath(m, 'week', m.anchor),
    day: (m) => calendarPath(m, 'oneday', m.events['busy-1'].start.slice(0, 10)),
    month: (m) => calendarPath(m, 'month', m.anchor),
};

for (const theme of themes()) {
    test.describe(theme, () => {
        test.beforeAll(() => activateTheme(theme));

        test('the theme is active', async () => {
            expect(activeTheme()).toBe(theme);
        });

        for (const width of WIDTHS) {
            test.describe(`${width} px`, () => {
                test.use({viewport: {width, height: 900}, isMobile: width < 800, hasTouch: width < 800});

                for (const [view, path] of Object.entries(VIEWS)) {
                    test(view, async ({page}) => {
                        await page.goto(path(manifest()));
                        await settle(page);
                        await expect(page.locator('#osec-calendar-view')).toHaveScreenshot([theme, `${view}-${width}.png`]);
                    });
                }
            });
        }

        test('day with the end of DST, full day revealed', async ({page}) => {
            const m = manifest();
            await page.goto(calendarPath(m, 'oneday', lastSunday(Number(m.anchor.slice(0, 4)), 10)));
            await settle(page);
            await page.locator('.ai1ec-reveal-full-day button').click();
            await settle(page);
            await expect(page.locator('#osec-calendar-view')).toHaveScreenshot([theme, 'day-dst-end.png']);
        });

        for (const [key, caption] of [['jazz', 'recurring, image, map, cost, ticket, contact'], ['tokyo', 'other timezone']]) {
            test(`single event: ${key} (${caption})`, async ({page}) => {
                await page.goto(`/?p=${manifest().events[key].id}`);
                await settle(page);
                await expect(page.locator('.ai1ec-single-event')).toHaveScreenshot([theme, `single-${key}.png`]);
            });
        }

        for (const key of ['block', 'shortcode']) {
            test(`${key} page`, async ({page}) => {
                await page.goto(manifest().pages[key].path);
                await settle(page);
                await expect(page.locator('#osec-calendar-view')).toHaveScreenshot([theme, `page-${key}.png`]);
            });
        }
    });
}
