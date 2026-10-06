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
            await h.open(driver, '/calendar/', CAL);
            await h.waitForModules(driver, ['scripts/calendar', 'scripts/calendar/load_views']);
            await h.assertNoConsoleErrors(driver, 'calendar');
        });

        for (const view of views) {
            it(`switches to ${view} view by AJAX`, async function () {
                // The current view has no link of its own.
                await h.open(driver, view === 'month' ? '/calendar/action~agenda/' : '/calendar/action~month/', CAL);
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
            await h.open(driver, '/calendar/action~agenda/', CAL);
            const start = await driver.getCurrentUrl();
            await h.click(driver, '.ai1ec-next-page');
            await driver.wait(async () => (await driver.getCurrentUrl()) !== start, h.TIMEOUT, 'next page did not load');
            await h.visible(driver, '.ai1ec-agenda-view');
            await h.click(driver, '.ai1ec-prev-page');
            await h.visible(driver, '.ai1ec-agenda-view');
            await h.assertNoConsoleErrors(driver, 'paging');
        });

        it('opens the date picker', async function () {
            await h.open(driver, '/calendar/action~month/', CAL);
            await h.click(driver, '.ai1ec-minical-trigger');
            await h.visible(driver, '.ai1ec-datepicker');
            await h.assertNoConsoleErrors(driver, 'date picker');
        });

        // Picking the date already shown used to leave the picker without its handler:
        // the next pick did nothing and the picker stayed open.
        it('date picker keeps working when the shown date is picked again', async function () {
            const pickerOpen = () => driver.executeScript(
                "return [...document.querySelectorAll('.ai1ec-datepicker')].some(p => p.offsetParent !== null);"
            );
            const clickDay = (css) => driver.executeScript(
                "const c = [...document.querySelectorAll('.ai1ec-datepicker ' + arguments[0])].find(e => e.offsetParent !== null); if (c) { c.click(); } return !!c;",
                css
            );
            await h.open(driver, '/calendar/action~month/', CAL);
            for (let i = 1; i <= 3; i++) {
                await h.click(driver, '.ai1ec-minical-trigger');
                await driver.wait(pickerOpen, h.TIMEOUT, `picker did not open (${i})`);
                assert.ok(await clickDay('td.ai1ec-today.ai1ec-day'), `no today cell (${i})`);
                await driver.wait(async () => !(await pickerOpen()), h.TIMEOUT, `picker stayed open after pick ${i}`);
            }
            await h.click(driver, '.ai1ec-minical-trigger');
            await driver.wait(pickerOpen, h.TIMEOUT, 'picker did not open');
            const before = await driver.getCurrentUrl();
            const day = (await driver.executeScript(
                "return [...document.querySelectorAll('.ai1ec-datepicker td.ai1ec-day:not(.ai1ec-old):not(.ai1ec-new):not(.ai1ec-active):not(.ai1ec-today)')].find(e => e.offsetParent !== null).textContent.trim();"
            ));
            assert.ok(await clickDay(`td.ai1ec-day:not(.ai1ec-old):not(.ai1ec-new):not(.ai1ec-active):not(.ai1ec-today)`), 'no other day');
            await driver.wait(async () => (await driver.getCurrentUrl()) !== before, h.TIMEOUT, 'picking another day did not navigate');
            assert.ok((await driver.getCurrentUrl()).includes(`exact_date~${day}-`), 'navigated to the picked day');
            await h.assertNoConsoleErrors(driver, 'date picker');
        });

        it('opens the category filter', async function () {
            await h.open(driver, '/calendar/', CAL);
            await h.click(driver, '.ai1ec-category-filter .ai1ec-dropdown-toggle');
            // vortex: bootstrap dropdown, plana: flyout menu.
            await h.visible(driver, '.ai1ec-category-filter .ai1ec-dropdown-menu, .ai1ec-category-filter .plana-flyout-menu--menu');
            await h.assertNoConsoleErrors(driver, 'category filter');
        });

        it('shows an event popover in month view', async function () {
            await h.open(driver, '/calendar/action~month/', CAL);
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

        for (const view of ['month', 'agenda']) {
            it(`prints event values as text in ${view} view rendered by twig.js`, async function () {
                // The seeded "Hostile venue" event: +6 days 13:00 in the site's timezone (bin/dev-seed-block-test-data.php).
                const day = new Date(Date.now() + 6 * 864e5).toLocaleDateString('sv-SE', {timeZone: 'Europe/Berlin'});
                // Start in the other view, so the checked one is rendered from JSON by twig.js.
                await h.open(driver, `/calendar/action~${view === 'month' ? 'agenda' : 'month'}/exact_date~${day}/`, CAL);
                const link = await driver.findElement(By.id(`ai1ec-view-${view}`));
                await driver.executeScript('arguments[0].click();', link);
                await h.visible(driver, `.ai1ec-${view}-view`);
                const result = await driver.executeScript(`return {
                    xss: window.osecXss || 0,
                    handlers: document.querySelectorAll('#osec-calendar-view img[onerror]').length,
                    asText: document.querySelector('#osec-calendar-view').textContent.includes('D&D <img src=x'),
                };`);
                assert.deepStrictEqual(result, {xss: 0, handlers: 0, asText: true});
                await h.assertNoConsoleErrors(driver, `escaped ${view} view`);
            });
        }

        it('has a working print button', async function () {
            await h.open(driver, '/calendar/action~month/', CAL);
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
