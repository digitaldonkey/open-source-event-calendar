const  AdminPageSettings = require('../page_objects/AdminPageSettings');
const pageObject = new AdminPageSettings();
const {
    Select,
    until,
    By
} = require('selenium-webdriver');

// @see https://www.selenium.dev/selenium/docs/api/javascript/

describe('WordPress Login', function(){
    this.timeout(50000);
    beforeEach(async function(){
        //Enter actions performed before test
    });

    afterEach(async function(){
        //Enter actions to be performed after test
        driver.manage().deleteAllCookies();

    });

    after(async () => {
        // suite after all emission
        // await driver.quit();
    });


    it('Admin login to WordPress', async function () {
        await pageObject.go_to_url(settings.domain + '/wp-login.php');
        let adminPageLoaded = pageObject.doLogin();

        assert.ok(adminPageLoaded);
    });

    // it('Load & Submit Osec settings page', async function () {
    //     const url = settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
    //     await pageObject.go_to_url(url);
    //     await pageObject.doLogin();
    //     let selectElement = await driver.findElement(By.id('calendar_page_id'));
    //     let settingsPageLoaded = await driver.wait(until.elementIsVisible(selectElement));
    //     assert.ok(settingsPageLoaded);
    //
    //     pageObject.setWeekDayTo(null);
    //
    //     // submit
    //     driver.findElement(By.id('osec_save_settings')).click();
    //
    //     await driver.wait(until.elementIsVisible(selectElement), 6000);
    //     // driver.wait
    //     const calendarLink = await driver.findElement(By.css('#calendar_page_id~p>a'));
    //     await pageObject.waitToSeeWhatHappens();
    //     const xxx = await calendarLink.getText();
    //     assert.equal(xxx, 'View "Calendar"');
    //
    //     // Check settings pages calender page id is saved
    //     // By default it's "Create new page" after it sould be "Calendar" and a Link is visible.
    //     const calendarPageSelect =  await driver.findElement(By.id('calendar_page_id'));
    //     await driver.wait(until.elementIsVisible(calendarPageSelect), 6000);
    //
    //     const calendarPageSelectItem = new Select(calendarPageSelect);
    //     const calendarPageSelectItemOption = await calendarPageSelectItem.getFirstSelectedOption();
    //     const text = await calendarPageSelectItemOption.getText();
    //     assert.equal(text, 'Calendar');
    //
    //     /* @var int/string selectedValue WP Page ID */
    //     const selectedValue = await calendarPageSelectItemOption.getAttribute('value');
    //     // console.log({selectedValue })
    // });

    // it('Settings was saved', async function () {
    //
    //
    //
    // });

})
