const  WpLogin = require('../page_objects/WpLogin');
const {
    Select,
    until,
    By
} = require('selenium-webdriver');
const WpPlugin = require("../page_objects/ActivatePluginAndSettings");
let pageObject = null;


describe('WordPress Login', function(){
    this.timeout(50000);
    before (async function() {
        pageObject = await WpPlugin.build();
    })

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
        await pageObject.takeScreenshot(this);
        let adminPageLoaded = await pageObject.doLogin();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(adminPageLoaded);
    });

})
