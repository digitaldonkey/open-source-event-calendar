//wp-admin/plugins.php
const WpPlugin = require('../page_objects/ActivatePluginAndSettings');
const pageObject = new WpPlugin();

const {
    Select,
    until,
    By
} = require('selenium-webdriver');

// @see https://www.selenium.dev/selenium/docs/api/javascript/

describe('Plugin install', function(){
    this.timeout(50000);
    beforeEach(async function(){
        //Enter actions performed before test
    });

    afterEach(async function(){
        //Enter actions to be performed after test
        pageObject.driver.manage().deleteAllCookies();
    });

    after(async () => {
        // suite after all emission
        await pageObject.driver.quit();
    });


    it('WordPress admin login', async function () {
        await pageObject.go_to_url(pageObject.settings.domain + '/wp-admin/plugins.php');
        const adminPluginPageLoaded = await pageObject.doLogin();
        const sucess = await pageObject.activateOsecPlugin();
        pageObject.assert.ok(sucess);
        await pageObject.takeScreenshot(this.test.fullTitle());
    });

    it('Update osec settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_to_url(url);
        await pageObject.doLogin();
        let selectElement = await pageObject.driver.findElement(By.id('calendar_page_id'));
        await pageObject.driver.wait(until.elementIsVisible(selectElement));

        // Set day
        await pageObject.setWeekDayTo();

        // Submit/Save
        await pageObject.driver.findElement(By.id('osec_save_settings')).click();
        // Reqired second call.
        selectElement = await pageObject.driver.findElement(By.id('calendar_page_id'));

        await pageObject.driver.wait(until.elementIsVisible(selectElement), 6000);


        // Check settings pages calender page id is saved
        // By default Select is "Create new page" after saving it sould be "Calendar" and a Link is visible.
        const calendarPageSelect =  await pageObject.driver.findElement(By.id('calendar_page_id'));
        await pageObject.driver.wait(until.elementIsVisible(calendarPageSelect), 6000);
        const calendarPageSelectItem = new Select(calendarPageSelect);
        const calendarPageSelectItemOption = await calendarPageSelectItem.getFirstSelectedOption();
        const calenderPageSelected = await calendarPageSelectItemOption.getText();

        // If Calendar page was created, the will be a new Link
        //  below select#calendar_page_id.
        const calendarLink = await pageObject.driver.findElement(By.css('#calendar_page_id~p>a'));
        const calendarPageLinkText = await calendarLink.getText();

        await pageObject.takeScreenshot(this.test.fullTitle());
        pageObject.assert.ok(
            calenderPageSelected === 'Calendar'
            && calendarPageLinkText === 'View "Calendar"'
        )
        /* @var int/string selectedValue WP Page ID */
        // const selectedValue = await calendarPageSelectItemOption.getAttribute('value');
        // console.log({selectedValue })
    });
})
