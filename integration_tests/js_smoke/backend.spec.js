const h = require('./helpers');

const COMMON = ['scripts/common_scripts/backend/common_backend'];

describe('JS smoke: backend', function () {
    let driver;

    before(async function () {
        driver = await h.buildDriver();
        await h.login(driver);
    });

    after(async function () {
        await driver?.quit();
    });

    describe('@js-backend event list', function () {
        it('loads the common backend bundle without errors', async function () {
            await h.open(driver, '/wp-admin/edit.php?post_type=osec_event', COMMON);
            await h.assertNoConsoleErrors(driver, 'event list');
        });

        // #65: the event editor's assets (and its 68rem width limit) belong to the event editor only.
        for (const [screen, path] of [
            ['event list', '/wp-admin/edit.php?post_type=osec_event'],
            ['page editor', '/wp-admin/post-new.php?post_type=page'],
        ]) {
            it(`does not load the event editor assets on the ${screen}`, async function () {
                await driver.get(h.url(path));
                await driver.wait(h.until.elementLocated(h.By.id('wpbody-content')), h.TIMEOUT);
                const loaded = await driver.executeScript(
                    "return [...document.querySelectorAll('link[href*=\"osec-admin-page-edit-event\"], script[src*=\"leaflet\"]')].map(e => e.href || e.src);"
                );
                h.assert.deepStrictEqual(loaded, [], `event editor assets on the ${screen}`);
            });
        }
    });

    describe('@js-add-new-event event editor', function () {
        const EDITOR = [...COMMON, 'scripts/add_new_event'];

        it('loads the editor bundle without errors', async function () {
            await h.open(driver, '/wp-admin/post-new.php?post_type=osec_event', EDITOR);
            await h.assertNoConsoleErrors(driver, 'new event');
        });

        it('opens the date picker', async function () {
            await h.open(driver, '/wp-admin/post-new.php?post_type=osec_event', EDITOR);
            await h.click(driver, '#osec_start-date-input');
            await h.visible(driver, '.calendricalDatePopup, .ai1ec-datepicker');
            await h.assertNoConsoleErrors(driver, 'editor date picker');
        });

        it('opens the repeat dialog', async function () {
            await h.open(driver, '/wp-admin/post-new.php?post_type=osec_event', EDITOR);
            await h.click(driver, '#osec_repeat');
            await h.visible(driver, '#osec_repeat_box');
            await h.assertNoConsoleErrors(driver, 'repeat dialog');
        });

        it('shows the location map', async function () {
            await h.open(driver, '/wp-admin/post-new.php?post_type=osec_event', EDITOR);
            await driver.executeScript("document.getElementById('osec-map')?.scrollIntoView({block: 'center'});");
            await h.visible(driver, '#osec-map.leaflet-container');
            await h.assertNoConsoleErrors(driver, 'editor map');
        });
    });

    describe('@js-settings settings page', function () {
        it('loads the settings bundle and opens the start date picker', async function () {
            await h.open(driver, '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings', [...COMMON, 'scripts/admin_settings']);
            await h.click(driver, '#exact_date');
            await h.visible(driver, '.ai1ec-datepicker');
            await h.assertNoConsoleErrors(driver, 'settings');
        });
    });

    describe('@js-feeds feeds page', function () {
        it('loads the feeds bundle and initialises the term selects', async function () {
            await h.open(driver, '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds', [...COMMON, 'scripts/calendar_feeds']);
            await h.visible(driver, '.select2-container');
            await h.assertNoConsoleErrors(driver, 'feeds');
        });
    });

    describe('@js-categories category page', function () {
        it('loads the category bundle and sets up the color picker', async function () {
            await h.open(driver, '/wp-admin/edit-tags.php?taxonomy=osec_events_categories&post_type=osec_event', [...COMMON, 'scripts/event_category']);
            // The picker is built on the first click.
            await h.click(driver, '#tag-color');
            await driver.wait(h.until.elementLocated(h.By.css('.colorpicker')), h.TIMEOUT, 'color picker not set up');
            await h.assertNoConsoleErrors(driver, 'categories');
        });
    });

    describe('@js-less-variables theme options', function () {
        it('loads the theme options bundle and sets up the color pickers', async function () {
            await h.open(driver, '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-edit-css', [...COMMON, 'scripts/less_variables_editing']);
            await driver.wait(
                () => driver.executeScript("return document.querySelectorAll('.colorpicker').length > 0;"),
                h.TIMEOUT,
                'color pickers not set up'
            );
            await h.assertNoConsoleErrors(driver, 'theme options');
        });
    });
});
