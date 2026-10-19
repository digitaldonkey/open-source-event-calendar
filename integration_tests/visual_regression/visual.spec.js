/**
 * Visual checks per calendar theme (lib/theme.js), in Chromium, logged out. See README.md.
 *
 * Calendar views are compared as element shots of #osec-container (decision N13: a layout shift around the
 * calendar would otherwise mark everything): agenda, week, day and month at 320, 480 and 1280 px; the DST-end day
 * expanded; two single events; the block and shortcode pages. Print lives in print.spec.js.
 */
const {test, expect} = require('@playwright/test');
const {themes, activateTheme, isThemeActive} = require('./lib/theme');
const {manifest, calendarPath, lastSunday, settle, about} = require('./lib/pages');

/** The calendar the plugin renders: filters, navigation, the view, subscribe buttons (decision N13: element shots). */
const CALENDAR = '#osec-container';

/** Widths below 800 px get mobile emulation (viewport meta tag, touch). */
const WIDTHS = [320, 480, 1280];

/**
 * View paths. Read per test: spec files load before the content setup runs, so a manifest read at load time would
 * be missing (first run) or hold the previous run's post IDs.
 */
const VIEWS = {
    agenda: {
        title: 'Agenda view',
        description: 'The agenda from the anchor week on, expanded (plugin default).',
        path: (m) => calendarPath(m, 'agenda', m.anchor),
    },
    week: {
        title: 'Week view',
        description: 'The anchor week: overlapping talks, all-day bars crossing the week, an event crossing midnight, '
            + 'an instant event, the Tokyo event on the day it falls on in Berlin.',
        path: (m) => calendarPath(m, 'week', m.anchor),
    },
    day: {
        title: 'Day view: busy day',
        description: 'The day with eight consecutive events next to a multi-day bar and a series.',
        path: (m) => calendarPath(m, 'oneday', m.events['busy-1'].start.slice(0, 10)),
    },
    month: {
        title: 'Month view',
        description: 'The anchor month (May): multi-day bars across weeks and the month end, many events on one day, '
            + 'long titles, category colors, series.',
        path: (m) => calendarPath(m, 'month', m.anchor),
    },
};

for (const theme of themes()) {
    test.describe(`Calendar theme ${theme}`, () => {
        test.beforeAll(({browser}) => activateTheme(theme, browser));

        test('The calendar theme is active', about(
            `Checks that "${theme}" is the active calendar theme before its screenshots (the specs switch themes per block).`,
        ), async ({browser}) => {
            expect(await isThemeActive(theme, browser)).toBe(true);
        });

        for (const width of WIDTHS) {
            test.describe(`${width} px wide`, () => {
                test.use({viewport: {width, height: 900}, isMobile: width < 800, hasTouch: width < 800});

                for (const [view, {title, description, path}] of Object.entries(VIEWS)) {
                    test(title, about(`${description} Element shot of #osec-container${width < 800 ? ', phone emulation' : ''}.`), async ({page}) => {
                        await page.goto(path(manifest()));
                        await settle(page);
                        await expect(page.locator(CALENDAR)).toHaveScreenshot([theme, `${view}-${width}.png`]);
                    });
                }
            });
        }

        test('Day view: end of DST, full day revealed', about(
            'The last Sunday of October (25 hours in Europe/Berlin) with the "show full day" button clicked: the night bus '
            + 'series at 00:30 and two events at 02:15, one in summer time and one in standard time (side finding T3: the '
            + 'grid has 24 rows).',
        ), async ({page}) => {
            const m = manifest();
            await page.goto(calendarPath(m, 'oneday', lastSunday(Number(m.anchor.slice(0, 4)), 10)));
            await settle(page);
            await page.locator('.ai1ec-reveal-full-day button').click();
            await settle(page);
            await expect(page.locator(CALENDAR)).toHaveScreenshot([theme, 'day-dst-end.png']);
        });

        const singles = [
            ['jazz', 'Single event: Jazz in the Park', 'Recurring event with featured image, map (OpenStreetMap tiles), '
                + 'cost, ticket button and organizer contact.'],
            ['tokyo', 'Single event: Early call from Tokyo', 'An event in another timezone (07:00 Asia/Tokyo), shown in '
                + 'the calendar timezone.'],
        ];
        for (const [key, title, description] of singles) {
            test(title, about(`${description} Element shot of .osec-single-event (title, details, description).`), async ({page}) => {
                await page.goto(`/?p=${manifest().events[key].id}`);
                await settle(page);
                // The whole event the plugin wraps (title, details, description), not only the details box.
                await expect(page.locator('.osec-single-event')).toHaveScreenshot([theme, `single-${key}.png`]);
            });
        }

        const pages = [
            ['block', 'Page with the calendar block', 'The "Calendar block" page: the calendar rendered by the block with '
                + 'its default settings.'],
            ['shortcode', 'Page with the [osec] shortcode', 'The "Calendar shortcode" page: [osec view="agenda" '
                + 'agenda_toggle="true"], the agenda expanded on purpose (stays so if the default changes).'],
        ];
        for (const [key, title, description] of pages) {
            test(title, about(`${description} Element shot of #osec-container.`), async ({page}) => {
                await page.goto(manifest().pages[key].path);
                await settle(page);
                await expect(page.locator(CALENDAR)).toHaveScreenshot([theme, `page-${key}.png`]);
            });
        }
    });
}
