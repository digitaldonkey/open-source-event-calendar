/**
 * The plugin's wp-admin screens, once per run (project `admin`, logged in), compared full page (decision N1).
 *
 * WordPress-dependent and run-dependent areas are hidden before the shot, not just masked, so a notice that comes or
 * goes does not shift the page: notices, update counters, the admin footer (WordPress version), help tabs; dates of the
 * run and the permalink (site hostname) are blanked in place.
 */
const {test, expect} = require('@playwright/test');
const {manifest, settle, about} = require('./lib/pages');

/** Removed from every admin shot: they come and go with WordPress and the site's state. */
const REMOVE = [
    '.notice', '.update-nag', 'div.updated', 'div.error', '#wpfooter', '#screen-meta-links',
    // Update counters (admin bar, menu badges).
    '#wp-admin-bar-updates', '.update-plugins', '.awaiting-mod',
    // Post dates (the time of the run): blanking keeps their text's wrapping, which changes the row heights.
    '.column-date',
].join(', ');

/** Blanked, keeping their space: dates of the run, the permalink (site hostname). */
const BLANK = ['#timestamp', '#last-edit', '#sample-permalink'].join(', ');

const STYLE = `${REMOVE} { display: none !important; } ${BLANK} { visibility: hidden !important; }`;

/** [snapshot name, title, description, path]. */
const SCREENS = [
    ['events', 'Events list', 'All events of the test content, sorted by title (all share the post date of the run, so '
        + 'the default order is not stable). Columns: event date and time, recurrence, categories, author.',
    // By title: all events share the post date of the run.
    () => '/wp-admin/edit.php?post_type=osec_event&orderby=title&order=asc'],
    ['edit-event', 'Edit event: Jazz in the Park', 'The event editor with every box filled: date and time, weekly '
        + 'recurrence, venue with map and coordinates, cost and ticket URL, organizer contact, featured image.',
    (m) => `/wp-admin/post.php?post=${m.events.jazz.id}&action=edit`],
    ['feeds', 'Calendar feeds', 'The feeds page with the imported "Public holidays" feed of the test content.',
        () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds'],
    ['settings', 'Settings', 'The plugin settings page, including the cache report and the charset box.',
        () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings'],
    ['themes', 'Calendar themes', 'The list of calendar themes with the active one first.',
        () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes'],
    ['categories', 'Event categories', 'Categories with colors, images and a sub-category (Football under Sports).',
        () => '/wp-admin/edit-tags.php?taxonomy=osec_events_categories&post_type=osec_event'],
];

test.use({viewport: {width: 1280, height: 900}});

for (const [name, title, description, path] of SCREENS) {
    test(title, about(`${description} Full page; notices, update counters, footer and dates removed or blanked.`), async ({page}) => {
        await page.goto(path(manifest()));
        await settle(page);
        await page.addStyleTag({content: STYLE});
        await expect(page).toHaveScreenshot(`admin-${name}.png`, {fullPage: true});
    });
}

test.describe('Block editor', () => {
    // Tall enough for the whole form inside the canvas iframe.
    test.use({viewport: {width: 1280, height: 2400}});

    test('Calendar block: edit form', about(
        'The "Calendar block" page in the block editor with the block\'s settings opened: view, agenda options, fixed '
        + 'date, category/tag/event filters, view settings. Only the block is shot; the editor around it is WordPress.',
    ), async ({page}) => {
        await page.goto(`/wp-admin/post.php?post=${manifest().pages.block.id}&action=edit`);
        await page.waitForFunction(() => window.wp?.data?.select('core/block-editor').getBlocks().length);
        await page.evaluate(() => wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false));
        // The block's form lives in the editor canvas (an iframe), behind its toggle; the editor around it is WordPress.
        const block = page.frameLocator('iframe[name="editor-canvas"]').locator('[data-type$="osec-calendar-classic"]');
        // "Settings" since 1.2.0, "Edit" in 1.1.x.
        await block.getByText(/^(Settings|Edit)\b/).first().click();
        // WordPress' block toolbar and breadcrumb bar overlap the block.
        await page.addStyleTag({content: '.block-editor-block-contextual-toolbar, .block-editor-block-popover, .interface-interface-skeleton__footer { display: none !important; }'});
        await settle(page, {networkIdle: false});
        await expect(block).toHaveScreenshot('admin-block-form.png');
    });
});
