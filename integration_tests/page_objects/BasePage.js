const {webdriver, Builder, Browser, By, Select} = require('selenium-webdriver');
// const driver = new Builder().forBrowser(Browser.CHROME).build();

const assert = require("node:assert");
const settings= require('../settings');

driver.manage().setTimeouts({implicit: (10000)});
driver.manage().window().maximize();


class BasePage {
    constructor(){
        global.driver = new Builder().forBrowser(Browser.CHROME).build();
        global.assert = assert;
        global.settings = settings
    }

    async go_to_url(url){
        await driver.get(url);
    }

    async enterText(findBy, searchText){
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        await driver.findElement(findBy).sendKeys(searchText);
    }

    async selectByValue(findBy, value){
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        const select = new Select(driver.findElement(findBy));
        await select.selectByValue(value);
    }

    /**
     * If --debug flag is set, we might wait to see.
     *
     * @param timeout
     * @returns {Promise<void>}
     */
    async waitToSeeWhatHappens(timeout = 2000){
        if (process.env.npm_config_debug) {
            return new Promise(resolve => setTimeout(resolve, timeout))
        }
        return Promise.resolve();
    }


}
module.exports = BasePage;
