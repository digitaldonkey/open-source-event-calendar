//wp-admin/plugins.php
const WpLogin = require('../page_objects/WpLogin');
const pageObject = new WpLogin();
const {
    Select,
    until,
    By
} = require('selenium-webdriver');

// @see https://www.selenium.dev/selenium/docs/api/javascript/

describe('Osec Plugin install', function(){
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
        await pageObject.go_to_url(settings.domain + '/wp-admin/plugins.php');
        let adminPluginPageLoaded = pageObject.doLogin();

        await pageObject.waitToSeeWhatHappens();
        assert.ok(adminPluginPageLoaded);
    });

})
