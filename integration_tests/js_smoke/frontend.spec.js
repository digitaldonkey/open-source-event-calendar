const h = require('./helpers');
const {By, assert} = h;

describe('JS smoke: frontend', function () {
    let driver;

    before(async function () {
        driver = await h.buildDriver();
    });

    after(async function () {
        await driver?.quit();
    });

    describe('@js-calendar calendar page', function () {
        const views = ['month', 'week', 'oneday', 'agenda'];
        const CAL = ['scripts/calendar'];

        it('loads the calendar bundle without errors', async function () {
            await h.open(driver, h.CAL_PATH, CAL);
            await h.waitForModules(driver, ['scripts/calendar', 'scripts/calendar/load_views']);
            await h.assertNoConsoleErrors(driver, 'calendar');
        });

        for (const view of views) {
            it(`switches to ${view} view by AJAX`, async function () {
                // The current view has no link of its own.
                await h.open(driver, h.CAL_PATH + (view === 'month' ? 'action~agenda/' : 'action~month/'), CAL);
                const link = await driver.findElement(By.id(`ai1ec-view-${view}`));
                // The view links live in a dropdown; trigger them as the dropdown item would.
                await driver.executeScript('arguments[0].click();', link);
                await h.visible(driver, `.ai1ec-${view}-view`);
                assert.ok(
                    (await driver.getCurrentUrl()).includes(`action~${view}`),
                    'history state points to the view'
                );
                await h.assertNoConsoleErrors(driver, `${view} view`);
            });
        }

        it('pages forward and back', async function () {
            await h.open(driver, h.CAL_PATH + 'action~agenda/', CAL);
            const start = await driver.getCurrentUrl();
            await h.click(driver, '.ai1ec-next-page');
            await driver.wait(async () => (await driver.getCurrentUrl()) !== start, h.TIMEOUT, 'next page did not load');
            await h.visible(driver, '.ai1ec-agenda-view');
            await h.click(driver, '.ai1ec-prev-page');
            await h.visible(driver, '.ai1ec-agenda-view');
            await h.assertNoConsoleErrors(driver, 'paging');
        });

        it('opens the date picker', async function () {
            await h.open(driver, h.CAL_PATH + 'action~month/', CAL);
            await h.click(driver, '.ai1ec-minical-trigger');
            await h.visible(driver, '.ai1ec-datepicker');
            await h.assertNoConsoleErrors(driver, 'date picker');
        });

        it('opens the category filter', async function () {
            await h.open(driver, h.CAL_PATH, CAL);
            await h.click(driver, '.ai1ec-category-filter .ai1ec-dropdown-toggle');
            // vortex: bootstrap dropdown, plana: flyout menu.
            await h.visible(driver, '.ai1ec-category-filter .ai1ec-dropdown-menu, .ai1ec-category-filter .plana-flyout-menu--menu');
            await h.assertNoConsoleErrors(driver, 'category filter');
        });

        it('shows an event popover in month view', async function () {
            await h.open(driver, h.CAL_PATH + 'action~month/', CAL);
            const event = await h.visible(driver, '.ai1ec-month-view .ai1ec-event-container');
            await driver.actions({async: true}).move({origin: event}).perform();
            // Every event carries a hidden popover; hovering shows one.
            await driver.wait(
                () => driver.executeScript("return [...document.querySelectorAll('.ai1ec-popover')].some(p => p.offsetParent !== null);"),
                h.TIMEOUT,
                'no popover shown'
            );
            await h.assertNoConsoleErrors(driver, 'popover');
        });

        it('has a working print button', async function () {
            await h.open(driver, h.CAL_PATH + 'action~month/', CAL);
            await h.visible(driver, '#ai1ec-print-button');
            await h.waitForModules(driver, ['scripts/calendar/print']);
            await h.assertNoConsoleErrors(driver, 'print button');
        });
    });

    describe('@js-event single event', function () {
        it('loads the event bundle and shows the map', async function () {
            // Created by bin/dev-seed-block-test-data.php.
            await h.open(driver, '/?post_type=osec_event&name=osec-test-open-air-cinema-map', ['scripts/event']);
            // The map starts when it is visible, or on a click on the placeholder
            // ("Hide maps until clicked"). Tiles come from the network, the marker does not.
            await driver.executeScript("document.getElementById('osec-map').scrollIntoView({block: 'center'});");
            await driver.executeScript("document.querySelector('.osec-map-placeholder')?.click();");
            await h.visible(driver, '#osec-map.leaflet-container .leaflet-marker-icon');
            await h.assertNoConsoleErrors(driver, 'single event');
        });
    });
});
