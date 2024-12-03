const {Builder, Browser, By, Select, until} = require('selenium-webdriver');
const { writeFile } = require('node:fs/promises');
const fs = require('fs');
const chrome = require('selenium-webdriver/chrome');
const firefox = require('selenium-webdriver/firefox');
const assert = require("node:assert");

class BasePage {
    constructor(){
        // Check for local settings file, overriding defaults.
        if (fs.existsSync(__dirname +  '/../settings.local.js')) {
            this.settings = require('../settings.local.js');
        }
        else {
            this.settings = require('../settings.js');
        }
        this.driver = this.setupWebDriver(this.settings.headless);
        this.assert = assert;
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
        const screenshotsDir = process.env.MOCHA_SCREENSHOT_DIR ?? this.settings.screenshotsDir;
        // screenshotsDir
        const filename = screenshotsDir + '/' + title.replace( /[^\w-]+/g, '_' ).trim() + '.png'
        return writeFile(filename, image, 'base64')
    }


    setupWebDriver (isHeadless = true) {
        let driver = null;
        if (isHeadless) {
            const screen = {
                width: 1280,
                height: 1280
            };
            driver = new Builder()
                .forBrowser(Browser.CHROME)
                .setChromeOptions(new chrome.Options().addArguments('--headless').windowSize(screen))
                .setFirefoxOptions(new firefox.Options().addArguments('--headless').windowSize(screen))
                .build();
        }
        else {
            driver = new Builder().forBrowser(Browser.CHROME).build();
        }
        driver.manage().window().maximize();
        driver.manage().setTimeouts({implicit: (10000)});
        return driver;
    }


}
module.exports = BasePage;
