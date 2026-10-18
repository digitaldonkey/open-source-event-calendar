/**
 * The plugin's wp-admin screens, once per run (project `admin`, logged in), compared full page (decision N1).
 *
 * WordPress-dependent and run-dependent areas are hidden before the shot, not just masked, so a notice that comes or
 * goes does not shift the page: notices, update counters, the admin footer (WordPress version), help tabs; dates of the
 * run and the permalink (site hostname) are blanked in place.
 */
const {test, expect} = require('@playwright/test');
const {manifest, settle} = require('./lib/pages');

/** Removed from every admin shot: they come and go with WordPress and the site's state. */
const REMOVE = [
    '.notice', '.update-nag', 'div.updated', 'div.error', '#wpfooter', '#screen-meta-links',
    // Update counters (admin bar, menu badges).
    '#wp-admin-bar-updates', '.update-plugins', '.awaiting-mod',
].join(', ');

/** Blanked, keeping their space: post dates are the time of the run, the permalink carries the site's hostname. */
const BLANK = ['.column-date', '#timestamp', '#last-edit', '#sample-permalink'].join(', ');

const STYLE = `${REMOVE} { display: none !important; } ${BLANK} { visibility: hidden !important; }`;

const SCREENS = [
    ['events', () => '/wp-admin/edit.php?post_type=osec_event'],
    ['edit-event', (m) => `/wp-admin/post.php?post=${m.events.jazz.id}&action=edit`],
    ['feeds', () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds'],
    ['settings', () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings'],
    ['themes', () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes'],
    ['categories', () => '/wp-admin/edit-tags.php?taxonomy=osec_events_categories&post_type=osec_event'],
];

test.use({viewport: {width: 1280, height: 900}});

for (const [name, path] of SCREENS) {
    test(name, async ({page}) => {
        await page.goto(path(manifest()));
        await settle(page);
        await page.addStyleTag({content: STYLE});
        await expect(page).toHaveScreenshot(`admin-${name}.png`, {fullPage: true});
    });
}

test.describe('block editor', () => {
    // Tall enough for the whole form inside the canvas iframe.
    test.use({viewport: {width: 1280, height: 2400}});

    test('calendar block edit form', async ({page}) => {
        await page.goto(`/wp-admin/post.php?post=${manifest().pages.block.id}&action=edit`);
        await page.waitForFunction(() => window.wp?.data?.select('core/block-editor').getBlocks().length);
        await page.evaluate(() => wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false));
        // The block's form lives in the editor canvas (an iframe), behind its "Settings" toggle; the editor around it is
        // WordPress.
        const block = page.frameLocator('iframe[name="editor-canvas"]').locator('[data-type$="osec-calendar-classic"]');
        await block.getByText('Settings', {exact: false}).first().click();
        // WordPress' block toolbar and breadcrumb bar overlap the block.
        await page.addStyleTag({content: '.block-editor-block-contextual-toolbar, .block-editor-block-popover, .interface-interface-skeleton__footer { display: none !important; }'});
        await settle(page, {networkIdle: false});
        await expect(block).toHaveScreenshot('admin-block-form.png');
    });
});
