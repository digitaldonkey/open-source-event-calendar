/**
 * The plugin's wp-admin screens, once per run (project `admin`, logged in), compared full page (decision N1).
 *
 * Only what decision N1 names is hidden before the shot: notices and the admin footer (WordPress version) are removed,
 * so one that comes or goes does not shift the page; dates of the run are removed or blanked. Everything else stays as
 * WordPress shows it (update counters, help tabs, permalink with the site's hostname).
 */
const { test, expect } = require('@playwright/test');
const { manifest, settle, about, compareShot } = require('../lib/pages');

/**
 * Removed from every admin shot (decision N1): notices and the footer with the WordPress version come and go with
 * WordPress and the site's state; the post date column holds the time of the run (blanking it would keep its
 * wrapping, which changes the row heights).
 */
const REMOVE = [
    '.notice',
    '.update-nag',
    'div.updated',
    'div.error',
    '#wpfooter',
    '.column-date',
].join(', ');

/** Blanked in place (decision N1): dates of the run in the event editor. */
const BLANK = [
    '#timestamp',
    '#last-edit',
].join(', ');

const STYLE = `${REMOVE} { display: none !important; } ${BLANK} { visibility: hidden !important; }`;

/**
 * wp-admin screens, shot full page; `name` is the snapshot name. `widths` (default [1280]) adds narrower shots: below
 * 800 px with phone emulation, as in visual.spec.js. 768 is a tablet just below WordPress' 782 px breakpoint, where
 * list tables switch to their compact layout.
 */
const SCREENS = [
    {
        name: 'events',
        title: 'Events list',
        description: `The first page of the events list, sorted by title (all share the post date of the run, so the
            default order is not stable), as the Screen Options setup sets it: 5 per page, extended view
            (excerpts), all columns.`,
        path: () => '/wp-admin/edit.php?post_type=osec_event&orderby=title&order=asc',
        widths: [
            320,
            768,
            1280,
        ],
    },
    {
        name: 'edit-event',
        title: 'Edit event: Jazz in the Park',
        description: `The event editor with every box filled: date and time, weekly recurrence, venue with map and
            coordinates, cost and ticket URL, organizer contact, featured image.`,
        path: (m) => `/wp-admin/post.php?post=${m.events.jazz.id}&action=edit`,
    },
    {
        name: 'feeds',
        title: 'Calendar feeds',
        description: 'The feeds page with the imported "Public holidays" feed of the test content.',
        path: () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds',
    },
    {
        name: 'settings',
        title: 'Settings',
        description: 'The plugin settings page, including the cache report and the charset box.',
        path: () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings',
    },
    {
        name: 'themes',
        title: 'Calendar themes',
        description: 'The list of calendar themes with the active one first.',
        path: () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes',
    },
    {
        name: 'categories',
        title: 'Event categories',
        description: 'Categories with colors, images and a sub-category (Football under Sports).',
        path: () => '/wp-admin/edit-tags.php?taxonomy=osec_events_categories&post_type=osec_event',
    },
];

for (const { name, title, description, path, widths = [1280] } of SCREENS) {
    for (const width of widths) {
        // 1280 keeps the name without a width, as in sets shot before screens had widths (e.g. 1.1.16).
        const file = 1280 === width ? `admin-${name}.png` : `admin-${name}-${width}.png`;
        const emulation = width < 800 ? ', phone emulation' : '';
        const details = `${description} Full page${emulation}; notices, footer and dates of the run removed or blanked
            (decision N1).`;

        test.describe(`${width} px wide`, () => {
            test.use({
                viewport: {
                    width,
                    height: 900,
                },
                isMobile: width < 800,
                hasTouch: width < 800,
            });

            test(title, about(details), async ({ page }) => {
                await page.goto(path(manifest()));
                await settle(page);
                await page.addStyleTag({ content: STYLE });
                await compareShot(page, [file], { fullPage: true });
            });
        });
    }
}

test.describe('Block editor', () => {
    // Tall enough for the whole form inside the canvas iframe.
    test.use({
        viewport: {
            width: 1280,
            height: 2400,
        },
    });

    test('Calendar block: edit form', about(
        `The "Calendar block" page in the block editor with the block's settings opened: view, agenda options, fixed
        date, category/tag/event filters, view settings. Only the block is shot (WordPress' block toolbar may
        overlap it, as in the editor).`,
    ), async ({ page }) => {
        await page.goto(`/wp-admin/post.php?post=${manifest().pages.block.id}&action=edit`);
        await page.waitForFunction(() => window.wp?.data?.select('core/block-editor').getBlocks().length);
        await page.evaluate(() => wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false));
        // The block's form lives in the editor canvas (an iframe), behind its toggle; the editor around it is
        // WordPress.
        const block = page.frameLocator('iframe[name="editor-canvas"]').locator('[data-type$="osec-calendar-classic"]');
        // "Settings" since 1.2.0, "Edit" in 1.1.x.
        await block.getByText(/^(Settings|Edit)\b/).first().click();
        await settle(page, { networkIdle: false });
        await compareShot(block, ['admin-block-form.png']);
    });
});
