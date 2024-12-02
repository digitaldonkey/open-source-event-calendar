const {webdriver, Builder, Browser, By, Select, until} = require('selenium-webdriver');
const { writeFile } = require('node:fs/promises');

class BasePage {
    constructor(){
        this.driver = new Builder().forBrowser(Browser.CHROME).build();
        this.driver.manage().setTimeouts({implicit: (10000)});
        this.driver.manage().window().maximize();
        this.assert = require("node:assert");
        this.settings = require('../settings')
    }

    async go_to_url(url){
        await this.driver.get(url);
    }

    async enterText(findBy, searchText){
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        return this.driver.findElement(findBy).sendKeys(searchText);
    }

    async selectByValue(findBy, value){
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        const select = await this.driver.findElement(findBy);
        const selectApi = new Select(select);
        return selectApi.selectByValue(value);
    }

    async getSelectSingleValue(findBy, textValue = false){
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        const element = await this.driver.findElement(findBy);
        await this.driver.wait(until.elementIsVisible(element), 6000);
        const select = new Select(element);
        const option = await select.getFirstSelectedOption();
        if (textValue) {
            return option.getText()
        }
        return option.getAttribute('value')
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


    async takeScreenshot(title){
        let image = await this.driver.takeScreenshot()

        // screenshotsDir
        const filename = this.settings.screenshotsDir + '/' + title.replace( /[^\w-]+/g, '_' ).trim() + '.png'
        return writeFile(filename, image, 'base64')
    }


}
module.exports = BasePage;
