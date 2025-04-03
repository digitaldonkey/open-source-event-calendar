//wp-admin/plugins.php
const WpPlugin = require('../page_objects/ActivatePluginAndSettings');

const {
    until,
    By,
} = require('selenium-webdriver');
let pageObject = null;



describe('Plugin install', function(){
    this.timeout(50000);

    before (async function() {
        pageObject = await WpPlugin.build();
    })

    beforeEach(async function(){
        //Enter actions performed before test
    });

    afterEach(async function(){
        //Enter actions to be performed after test
        await pageObject.driver.manage().deleteAllCookies();
    });

    after(async () => {
        // suite after all emission
        await pageObject.driver.quit();
    });


    it('WordPress activate Osec plugin', async function () {
        await pageObject.go_to_url(pageObject.settings.domain + '/wp-admin/plugins.php');
        await pageObject.doLogin();
        const success = await pageObject.activateOsecPlugin();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(success);
        await pageObject.takeScreenshot(this);
    });

    it('Update osec settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        const mainElementID = 'ai1ec-general-settings';
        await pageObject.getElement(By.id(mainElementID))

        // Set day
        await pageObject.setWeekDay(By.id('week_start_day'));

        // Set timezone to Europe/Berlin
        const timezoneSelect = await pageObject.getElement(By.id('timezone_string'))
        await pageObject.setTimezone(timezoneSelect);

        // Submit/Save
        const saveButtonElement = await pageObject.getElement(By.id('osec_save_settings'));
        await saveButtonElement.click();

        // Waiting releoad.
        await pageObject.waitToSeeWhatHappens(700, true);
        await pageObject.getElement(By.id(mainElementID));

        await pageObject.takeScreenshot(this);

        // Check Weekstart Day.
        const monday = await pageObject.getElement(
            By.css(`#week_start_day option[value="1"]`) // 1== Monday.
        );

        const isMondaySelected = await monday.isSelected();
        pageObject.assert.ok(isMondaySelected);

        // Check settings pages calendar page id is saved
        // By default Select is "Create new page" after saving it should be "Calendar" and a Link is visible.
        // calenderPage value would be int WP Page ID.
        const calenderPageSelect = await pageObject.getElement(By.id('calendar_page_id'));
        const calenderPageSelectedText = await pageObject.getSelectSingleValue(calenderPageSelect, true);
        pageObject.assert.ok(
            calenderPageSelectedText === 'Calendar'
        )

        // Check timezone saved.
        const timeZoneSelect = await pageObject.getElement(By.id('timezone_string'));
        const timeZoneSelectedValue = await pageObject.getSelectSingleValue(timeZoneSelect);
        pageObject.assert.ok(
            timeZoneSelectedValue === pageObject.settings.AdminPageSettings.timeZone
        );

        // New view-link below select#calendar_page_id
        const calendarLink = await pageObject.getElement(By.css('#calendar_page_id~p>a'));
        const calendarPageLinkText = await calendarLink.getText();
        pageObject.assert.ok(
            calendarPageLinkText === 'View "Calendar"'
        )
        await pageObject.takeScreenshot(this);
    });

    it('View osec cache settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        // Switch to cache report
        const cacheMenuLink = await pageObject.driver.findElement(By.id('osec-link-cache'));
        await cacheMenuLink.click();
        // Wait.
        // TODO For now just a screenshot to see...
        await pageObject.getElement(By.id('osec-cache'))
        await pageObject.takeScreenshot(this);
    })

    it('View and save osec theme settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-edit-css';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        // Js generated link should be visible.
        await pageObject.getElement(By.id( 'osec-link-general'))

        // Submit/Save
        const saveOptionsButton = await pageObject.getElement(By.id( 'osec_save_themes_options'))
        await saveOptionsButton.click();

        // Required to wait again.
        await pageObject.getElement(By.id( 'osec-link-general'))

        // "Theme options were updated successfully. Visit site" should be visible.
        const expectedText = 'Visit site';
        const optionsSaved = await pageObject.getElement(
            By.xpath("//a[text()='" + expectedText + "']")
        )
        const visibleText = await optionsSaved.getText();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
    });

    it('Add ICS calendar feed', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        // Js generated link should be visible.
        const mainElementID = 'ai1ec-feeds';
        await pageObject.getElement(By.id( 'ai1ec-feeds'));

        // Submit/Save
        const feedUrl = 'https://ics.calendarlabs.com/641/64bc8358/FIFA_Womens_World_Cup.ics'
        const feedUrlInput = await pageObject.getElement(By.id( 'osec_feed_url'));
        await feedUrlInput.sendKeys(feedUrl);

        // osec_ics_add_new
        const feedUrlAddButton = await pageObject.getElement(By.id( 'osec_ics_add_new'));
        await feedUrlAddButton.click();

        // Required to call/wait again.
        await pageObject.getElement(By.id( 'ai1ec-feeds'));

        // .ai1ec-alert-success should contain "Imported 53 events"
        const expectedText = 'Imported 53 events';

        // Get text without child Elements (aka: dismiss alert X)
        await pageObject.getElement(By.css('.ai1ec-alert'));
        const visibleText = await pageObject.getTextByClassName('ai1ec-alert');

        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
        // const optionsSaved = await pageObject.driver.findElement(By.xpath("//.ai1ec-alert-success[text()='" + expectedText + "']"));
        // const visibleText = await optionsSaved.getText();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
    });

    it('Delete ICS calendar feed', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        // Js generated link should be visible.
        await pageObject.getElement(By.id('ai1ec-feeds'));

        // Open ICS panel.
        const panelLink = await pageObject.getElement(By.css('a[data-toggle="ai1ec-collapse"]'));
        await panelLink.click();

        // osec_delete_ics
        const deleteButton = await pageObject.getElement(By.css('.osec_delete_ics'));
        await deleteButton.click();

        // Confirm delete events dialogue
        // osec_delete_ics
        const deleteEventsButton = await pageObject.getElement(By.css('.ai1ec-btn.remove'));
        await deleteEventsButton.click();

        // Required to call/wait again.
        await pageObject.getElement(By.id('ai1ec-feeds'));
        await pageObject.getElement(By.css('.ai1ec-alert'));

        // .ai1ec-alert should contain "Deleted 53 events"
        const expectedText = 'Deleted 53 events';

        // Get text without child Elements (aka: dismiss alert X)
        const visibleText = await pageObject.getTextByClassName('ai1ec-alert');
        //
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
    });

    it('Add daily repeating event', async function () {
        const url = pageObject.settings.domain + '/wp-admin/post-new.php?post_type=osec_event';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

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
        publishButton.click();
        await pageObject.waitToSeeWhatHappens(500, true);

        // Wait for save
        const showPageLink = await pageObject.getElement(By.css('#message a'));
        // const pageLinkText = await showPageLink.getText();
        await showPageLink.click();

        // Wait for single event to load
        const backToCalendarLink = await pageObject.getElement(By.css('.ai1ec-calendar-link'));
        await backToCalendarLink.click();

        // TODO ensure Title Text?

        // Calendar view Month should be visible by default.
        // Switch to next month .ai1ec-title-buttons a.ai1ec-next-month
        const nextMonthLink = await pageObject.getElement(By.css('.ai1ec-title-buttons a.ai1ec-next-month'));
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
        console.log({daysInNextMonth, eventCountMonth});

        pageObject.assert.ok(
            daysInNextMonth === eventCountMonth
        )

        // Switch view to agenda
        let viewsDropdownLink = await pageObject.getElement(By.css('a[data-toggle="ai1ec-dropdown"]'));
        await viewsDropdownLink.click();

        const agendaDropdownLink = await pageObject.getElement(By.id('ai1ec-view-agenda'));
        await agendaDropdownLink.click();

        // Wait for Ajax load.
        await pageObject.getElement(By.id('osec-calendar-view'));
        await pageObject.waitToSeeWhatHappens(700, true);
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
        pageObject.assert.ok(
            10 === daysInAgendaCount
        )

        // Switch view to weekly
        viewsDropdownLink = await pageObject.getElement(By.css('a[data-toggle="ai1ec-dropdown"]'));
        await viewsDropdownLink.click();
        const weeklyDropdownLink = await pageObject.getElement(By.id('ai1ec-view-week'));
        await weeklyDropdownLink.click();

        // Wait for Ajax load.
        await pageObject.getElement(By.id('osec-calendar-view'));
        await pageObject.waitToSeeWhatHappens(700, true);
        await pageObject.takeScreenshot(this);

        const eventsInWeek = await pageObject.driver.findElements(By.className('ai1ec-event-title'));
        const eventCountWeek =  await eventsInWeek.length;
        pageObject.assert.ok(
            7 === eventCountWeek
        )

        // Switch view to daily
        viewsDropdownLink = await pageObject.getElement(By.css('a[data-toggle="ai1ec-dropdown"]'));
        await viewsDropdownLink.click();

        const dailyDropdownLink = await pageObject.getElement(By.id('ai1ec-view-oneday'));
        await dailyDropdownLink.click();

        // Wait for Ajax load.
        await pageObject.getElement(By.id('osec-calendar-view'));
        await pageObject.waitToSeeWhatHappens(700, true);
        await pageObject.takeScreenshot(this);

        const eventsInDay = await pageObject.driver.findElements(By.className('ai1ec-event-title'));
        const eventCountDay =  await eventsInDay.length;
        pageObject.assert.ok(
            1 === eventCountDay
        );
        await pageObject.go_to_url(url);
    });

    it('Delete daily repeating event', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event';
        await pageObject.go_to_url(url);

        // TODO Why we do not get logged out before?
        await pageObject.doLogin();

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
    });
})

