//wp-admin/plugins.php
const WpPlugin = require('../page_objects/ActivatePluginAndSettings');
const {
    until,
    By
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
        const sucess = await pageObject.activateOsecPlugin();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(sucess);
        await pageObject.takeScreenshot(this);
    });

    it('Update osec settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        const calendarPageSelectID = By.id('calendar_page_id');
        let calendarPageSelect = await pageObject.driver.findElement(calendarPageSelectID);
        await pageObject.driver.wait(until.elementIsVisible(calendarPageSelect));

        // Set day
        await pageObject.setWeekDay();

        // Set timezon to Europe/Berlin
        await pageObject.setTimezone();

        // Submit/Save
        await pageObject.driver.findElement(By.id('osec_save_settings')).click();

        // Required to call again.
        calendarPageSelect = await pageObject.driver.findElement(calendarPageSelectID);
        await pageObject.driver.wait(until.elementIsVisible(calendarPageSelect), 6000);

        // Check settings pages calendar page id is saved
        // By default Select is "Create new page" after saving it sould be "Calendar" and a Link is visible.
        // calenderPage value would be int WP Page ID.
        const calenderPageSelectedText = await pageObject.getSelectSingleValue(calendarPageSelectID, true);

        // Check timezone saved.
        const timeZoneSelectedValue = await pageObject.getSelectSingleValue(By.id('timezone_string'));

        // New view-link below select#calendar_page_id
        const calendarLink = await pageObject.driver.findElement(By.css('#calendar_page_id~p>a'));
        const calendarPageLinkText = await calendarLink.getText();

        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            calenderPageSelectedText === 'Calendar'
            && calendarPageLinkText === 'View "Calendar"'
            && timeZoneSelectedValue === pageObject.settings.AdminPageSettings.timeZone
        )
    });

    it('View osec cache settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();

        // Switch to cache report
        const cacheMenuId = By.id('osec-link-cache');
        let cacheMenuLink = await pageObject.driver.findElement(cacheMenuId);
        await cacheMenuLink.click();

        const cacheId = By.id('osec-cache');
        let cacheDiv = await pageObject.driver.findElement(cacheId);
        await pageObject.driver.wait(until.elementIsVisible(cacheDiv));

        await pageObject.takeScreenshot(this);
        // TODO For now just a screenshot to see...
        pageObject.assert.ok( true )
    })
})
