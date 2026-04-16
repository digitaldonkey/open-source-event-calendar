//wp-admin/plugins.php
const WpPlugin = require('../page_objects/ActivatePluginAndSettings');

const {
    until,
    By, Select,
} = require('selenium-webdriver');
let pageObject = null;



// TODO
//   - Current Theme is not kept when selected.


describe('Frontend tests', function(){
    this.timeout(50000);
    let themes = [
        'gamma',
        'plana',
        'umbra',
        'vortex',
    ];

    before (async function() {
        pageObject = await WpPlugin.build();

        if (pageObject.settings.currentThemeOnly) {
            // Will set pageObject.settings.currentTheme
            await pageObject.getActiveTheme();
        }
        // console.log({settings: pageObject.settings})
    })

    after(async () => {
        await pageObject.driver.quit();
    });


    // Running test for every Theme.
    themes.forEach(function (theme) {
        describe('Test theme: ' + theme + '?', function () {

            /* shouldSkip Is set if we want to test only one theme by settings.*/
            let shouldSkip = false;

            /**
             * isReady
             *   If OSEC_UNINSTALL_PLUGIN_DATA is not set tests would fail.
             */
            let isReady = false;

            before(async function(){
                if (pageObject.settings.currentThemeOnly) {
                    shouldSkip = theme !== pageObject.settings.currentTheme;
                    if (shouldSkip) {
                        console.info(`Skipping tests for ${theme}.`)
                        return this.skip();
                    }
                }
                // Resets Plugin and enables current theme.
                isReady = await pageObject.resetOsecPlugin(theme);
            });

            afterEach(async function () {
                await pageObject.doLogout();
            });

            describe('Run tests for: ' + theme, function(){

                it('Set up theme', async function () {
                    pageObject.doFailTest(isReady);
                    console.info('Set up theme: ' + theme + ' Should skip: ' + shouldSkip);
                    await pageObject.setTheme(theme);
                });

                it('Add daily repeating event', async function () {
                    pageObject.doFailTest(isReady);
                    const url = pageObject.settings.domain + '/wp-admin/post-new.php?post_type=osec_event';
                    await pageObject.go_and_do_login(url);

                    // Repeat checkbox should be visible
                    const repeatCheckbox = await pageObject.getElement(By.id('osec_repeat'));
                    // Open repeat panel
                    await repeatCheckbox.click();
                    await pageObject.waitToSeeWhatHappens(500, true);


                    // Click on Daily tab
                    // Default should be Repeats: 'every day', Ending: 'Never'
                    const dailyButton = await pageObject.getElement(By.css('a[href="#osec_daily_content"]'));
                    await dailyButton.click();

                    // Confirm repeat settings
                    const confirmButton = await pageObject.getElement(By.id('osec_repeat_apply'));
                    await confirmButton.click();

                    // Add Event title
                    const titleInput = await pageObject.getElement(By.id('title'));
                    await titleInput.sendKeys('Daily repeating event');
                    await pageObject.waitToSeeWhatHappens(500, true);

                    const publishButton = await pageObject.getElement(By.id('publish'));
                    await publishButton.click();
                    await pageObject.waitToSeeWhatHappens(500, true);

                    // Wait for save
                    const showPageLink = await pageObject.getElement(By.css('#message a'));
                    // const pageLinkText = await showPageLink.getText();
                    await showPageLink.click();

                    // Wait for single event to load
                    //
                    const backToCalendarLink = await pageObject.getElement(By.css('.osec-calendar-back-link'));
                    await backToCalendarLink.click();

                    // Calendar view Month should be visible by default.
                    // Switch to next month a.ai1ec-next-month
                    const nextMonthLink = await pageObject.getElement(By.css('a.ai1ec-next-month'));
                    await nextMonthLink.click();

                    // TODO Maybe there is a more stable way to ensure ajax did finish?

                    // Wait for Ajax load.
                    await pageObject.getElement(By.id('osec-calendar-view'));
                    await pageObject.waitToSeeWhatHappens(700, true);
                    await pageObject.takeScreenshot(this);

                    // Count Occurrences of ai1ec-event-title
                    const eventsInMonth = await pageObject.driver.findElements(By.className('ai1ec-event-title'));
                    const eventCountMonth = await eventsInMonth.length;

                    const daysInNextMonth = new Date(new Date().getFullYear(), new Date().getUTCMonth()+2, 0).getDate()

                    pageObject.assert.equal(
                        eventCountMonth,
                        daysInNextMonth,
                        'Number of Events matches days in next month'
                    )

                    // Switch view to agenda
                    await pageObject.openViewSelectorMenue(theme);
                    const agendaDropdownLink = await pageObject.getElement(By.id('ai1ec-view-agenda'));
                    await agendaDropdownLink.click();

                    // Wait for Ajax load.
                    await pageObject.getElement(By.id('osec-calendar-view'));
                    await pageObject.waitToSeeWhatHappens(1200, true);
                    await pageObject.takeScreenshot(this);

                    const eventsInAgenda = await pageObject.driver.findElements(By.className('ai1ec-event-title'));
                    const eventCountAgenda =  await eventsInAgenda.length;

                    // By defaultAgenda should show 10 events.
                    pageObject.assert.ok(
                        10 === eventCountAgenda
                    )
                    // In this case (Daily Event) we also should see 10 days.
                    const daysInAgenda = await pageObject.driver.findElements(By.className('ai1ec-day'));
                    const daysInAgendaCount =  await daysInAgenda.length;
                    pageObject.assert.equal(
                        daysInAgendaCount,
                        10,
                        'Agenda contains 10 Events (default setting)'
                    )

                    // Switch view to weekly
                    await pageObject.openViewSelectorMenue(theme);
                    const weeklyDropdownLink = await pageObject.getElement(By.id('ai1ec-view-week'));
                    await weeklyDropdownLink.click();

                    // Calendar view Week should be visible by default.
                    // Switch to next week .ai1ec-next-week
                    const nextWeekLink = await pageObject.getElement(By.css('a.ai1ec-next-week'));
                    await nextWeekLink.click();
                    await pageObject.waitToSeeWhatHappens(500);


                    // Wait for Ajax load.
                    await pageObject.getElement(By.id('osec-calendar-view'));
                    await pageObject.waitToSeeWhatHappens(1200, true);
                    await pageObject.takeScreenshot(this);

                    const eventsInWeek = await pageObject.driver.findElements(By.className('ai1ec-event-title'));
                    const eventCountWeek =  await eventsInWeek.length;
                    pageObject.assert.equal(
                        eventCountWeek,
                        7,
                        'Daily event occurs 7 times a week.'
                    );

                    // Switch view to daily
                    await pageObject.openViewSelectorMenue(theme);
                    const dailyDropdownLink = await pageObject.getElement(By.id('ai1ec-view-oneday'));
                    await dailyDropdownLink.click();

                    // Wait for Ajax load.
                    await pageObject.getElement(By.id('osec-calendar-view'));
                    await pageObject.waitToSeeWhatHappens(700, true);
                    await pageObject.takeScreenshot(this);

                    const eventsInDay = await pageObject.driver.findElements(By.className('ai1ec-event-title'));
                    const eventCountDay =  await eventsInDay.length;
                    pageObject.assert.equal(
                        eventCountDay,
                        1,
                        'Daily Event occurs once on day view.'
                    );
                    await pageObject.go_to_url(url);
                });

                it('Delete daily repeating event', async function () {
                    pageObject.doFailTest(isReady);
                    const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event';
                    await pageObject.go_and_do_login(url);

                    // Switch to extended sceen options (to avoid hovering).
                    await pageObject.waitToSeeWhatHappens(500, true);
                    const screenOptions = await pageObject.getElement(By.id('show-settings-link'));
                    await screenOptions.click();

                    const wpViewMode = await pageObject.getElement(By.id('excerpt-view-mode'));
                    await wpViewMode.click();

                    const applyViewMode = await pageObject.getElement(By.id('screen-options-apply'));
                    await applyViewMode.click();

                    // Trash Event
                    await pageObject.waitToSeeWhatHappens(700, true);
                    const trashPostLink = await pageObject.getElement(By.className('submitdelete'));
                    await trashPostLink.click();
                    await pageObject.waitToSeeWhatHappens(500, true);

                    // Go to trash view
                    const goToTrash = await pageObject.getElement(By.css('li.trash a'));
                    await goToTrash.click();
                    await pageObject.waitToSeeWhatHappens(500, true);

                    // Delete permanently
                    const trashPermanentlyLink = await pageObject.getElement(By.className('submitdelete'));
                    await trashPermanentlyLink.click();

                    await pageObject.waitToSeeWhatHappens(500, true);
                })

                it('Add Event with map', async function () {
                    pageObject.doFailTest(isReady);
                    const url = pageObject.settings.domain + '/wp-admin/post-new.php?post_type=osec_event';
                    await pageObject.go_and_do_login(url);

                    // Add Event title
                    const titleInput = await pageObject.getElement(By.id('title'));
                    await titleInput.sendKeys('Event with map');
                    await pageObject.waitToSeeWhatHappens(500, true);

                    // Search on map
                    const mapSearchInput = await pageObject.getElement(By.css('#osec-map input[type="search"]'));
                    // Note: We put in German name but expect English result "Brandenburg Gate"
                    await mapSearchInput.sendKeys('Brandenburger Tor, Pariser Platz 1, 10117 Berlin, Deutschland');
                    await pageObject.waitToSeeWhatHappens(500, true);
                    // Click result
                    const resultSuggestion = await pageObject.getElement(By.css('#osec-map .leaflet-control-geocoder-alternatives li[data-result-index="0"]'));
                    await resultSuggestion.click();
                    // Ensure Map is displayed
                    const mapCheckbox = await pageObject.getElement(By.id('osec_google_map'));
                    await mapCheckbox.click();

                    await pageObject.takeScreenshot(this);


                    // Save event
                    const publishButton = await pageObject.getElement(By.id('publish'));
                    await pageObject.driver.executeScript("arguments[0].scrollIntoView(true);", publishButton);
                    await pageObject.waitToSeeWhatHappens(500, true);
                    await publishButton.click();
                    await pageObject.waitToSeeWhatHappens(1000, true);
                    // Go to Event
                    const showPageLink = await pageObject.getElement(By.css('#message a'));
                    await showPageLink.click();
                    await pageObject.waitToSeeWhatHappens(1000, true);

                    // Check for address

                    const addressValue = await pageObject.getElement(By.css('.entry-content .osec-location'));
                    const text = await addressValue.getText();
                    const textArray = text.split('\n');

                    // Cant manage to force leaflets language
                    // to be consistent locally and in pipeline.
                    // So we split handle German and English by or condition.
                    // This might fail if you browser has a differen language.
                    const locationTitle = textArray.shift();
                    pageObject.assert.ok(
                        locationTitle === 'Brandenburger Tor' || locationTitle === 'Brandenburg Gate',
                        'Location Title is set'
                    )
                    const country = textArray.pop();
                    pageObject.assert.ok(
                        country === 'Deutschland' || country === 'Germany',
                        'Location country is set'
                    )

                    const address = textArray.join("\n")
                    pageObject.assert.equal(
                        address,
                        'Pariser Platz 1\n10117 Berlin\nMitte',
                        'Address as expected'
                    );

                    // Click map placeholder
                    const placeholder = await pageObject.getElement(By.css('.osec-map-placeholder'));
                    await placeholder.click();
                    await pageObject.waitToSeeWhatHappens(1000, true);

                    await pageObject.takeScreenshot(this);

                    // Check for Map marker
                    const marker = await pageObject.getElement(By.css('.leaflet-marker-pane > img'));
                    const markerImgUrl = await marker.getAttribute('src');
                    // https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png
                    // Version is a constant, marker-icon suffix might change depending on screen.
                    pageObject.assert.ok(
                        markerImgUrl.startsWith('https://unpkg.com/leaflet')
                        && markerImgUrl.endsWith('.png')
                        && markerImgUrl.includes('marker-icon')
                    );
                    await pageObject.waitToSeeWhatHappens(800, true);
                });

                it('Verify Excerpt', async function () {
                    pageObject.doFailTest(isReady);
                    const title = 'Event using Excerpt';
                    // WP editor would add line breaks anyway. So they are required.
                    const content = 'CONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT\nCONTENT';
                    const excerptContent = 'EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT EXCERPT';

                    const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
                    await pageObject.go_and_do_login(url);

                    // wait for some form element
                    const excerptCheckbox = await pageObject.getElement(By.id('feature_use_excerpt'))
                    const isEnabled = await excerptCheckbox.isSelected();
                    if (!isEnabled) {
                        await excerptCheckbox.click()
                    }

                    const submitSettings = await pageObject.getElement(By.id('osec_save_settings'))
                    await pageObject.driver.executeScript("arguments[0].scrollIntoView(true); console.log(arguments);", submitSettings);

                    await submitSettings.submit();
                    await pageObject.waitToSeeWhatHappens(300, true);
                    await pageObject.takeScreenshot(this);

                    const editUrl = pageObject.settings.domain + '/wp-admin/post-new.php?post_type=osec_event';
                    await pageObject.go_to_url(editUrl);


                    // Add Event title
                    const titleInput = await pageObject.getElement(By.id('title'));
                    await titleInput.sendKeys(title);

                    // Switch to code view to avoid tinymce
                    const button = await pageObject.getElement(By.id('content-html'));
                    await button.click();
                    await pageObject.waitToSeeWhatHappens(500, true);

                    // Add content
                    await pageObject.driver.executeScript("document.getElementById('content').value = arguments[0]", content);

                    // Add excerpt text
                    const excerptInput = await pageObject.getElement(By.id('excerpt'));
                    await excerptInput.sendKeys(excerptContent);

                    // Save event
                    const publishButton = await pageObject.getElement(By.id('publish'));
                    await pageObject.driver.executeScript("arguments[0].scrollIntoView(true);", publishButton);
                    await pageObject.waitToSeeWhatHappens(500, true);
                    await publishButton.click();
                    await pageObject.waitToSeeWhatHappens(500, true);

                    // Go store ID and navigate to Event
                    const showPageLink = await pageObject.getElement(By.css('#message a'));
                    await showPageLink.click();
                    await pageObject.waitToSeeWhatHappens(500, true);

                    await pageObject.takeScreenshot(this);

                    // Check for content is on Single
                    const singleEventContentSel = await pageObject.getElement(By.tagName('body'));
                    const singleContent = await singleEventContentSel.getText();

                    pageObject.assert.ok(
                        singleContent.includes(content),
                        'The Event single page contains content'
                    );

                    pageObject.assert.ok(
                        ! singleContent.includes(excerptContent),
                        'The Event single page does not contain teaser content'
                    );

                    // Check if content is not on Calendar
                    // Wait for single event to load
                    const backToCalendarLink = await pageObject.getElement(By.css('.osec-calendar-back-link'));
                    await backToCalendarLink.click();
                    await pageObject.waitToSeeWhatHappens(1000, true);

                    // Hover
                    const popupTrigger = await pageObject.getElement(By.partialLinkText(title));
                    const actions = pageObject.driver.actions({async: true});
                    await actions.move({origin: popupTrigger}).perform();

                    const calendarEventContentSel = await pageObject.getElement(By.css('body'));
                    const calendarContent = await calendarEventContentSel.getText();

                    pageObject.assert.ok(
                        calendarContent.includes(excerptContent),
                        'The teaser/excerpt on calendar contains excerpt content'
                    );

                    pageObject.assert.ok(
                        ! calendarContent.includes(content),
                        'Event on calendar does not contain main content'
                    );
                    await pageObject.takeScreenshot(this);
                });

            }); // End run tests for theme.

        }); // End Test theme

    }); // End themes.
})
