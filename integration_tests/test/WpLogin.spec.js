const  WpLogin = require('../page_objects/WpLogin');
const pageObject = new WpLogin();
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
        pageObject.driver.manage().deleteAllCookies();

    });

    after(async () => {
        // suite after all emission
        await pageObject.driver.quit();
    });


    it('Admin login to WordPress', async function () {
        await pageObject.go_to_url(pageObject.settings.domain + '/wp-login.php');
        let adminPageLoaded = pageObject.doLogin();

        pageObject.assert.ok(adminPageLoaded);
    });

})
