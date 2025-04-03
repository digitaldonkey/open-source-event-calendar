const {Builder, Browser, By, Select, WebElement, until} = require('selenium-webdriver');
const { writeFile } = require('node:fs/promises');
const fs = require('fs');
const chrome = require('selenium-webdriver/chrome');
const firefox = require('selenium-webdriver/firefox');
const assert = require("node:assert");


// API
// @see https://www.selenium.dev/selenium/docs/api/javascript/

class BasePage {

    constructor(settings, driver){
        if (typeof driver === 'undefined') {
            throw new Error('Cannot be called directly');
        }
        this.assert = assert;
        this.settings = settings;
        this.driver = driver;
        this.driver.manage().window().maximize();
        this.driver.manage().setTimeouts({implicit: (10000)});
        this.screenshotCount = 1;
    }

    static async build () {
        // Check for local settings file, overriding defaults.
        let settings
        if (fs.existsSync(__dirname +  '/../settings.local.js')) {
            settings = require('../settings.local.js');
        }
        else {
            settings = require('../settings.js');
        }
        const theClass = this;
        return this.setupWebDriver(settings)
            .then(function(driver){
                console.log({driver})
                return new theClass(settings, driver);
            });
    }

    /**
     * Set up selenium webdriver.
     *
     * @param isHeadless
     * @returns {!ThenableWebDriver}
     */
    static setupWebDriver (settings) {
        if (settings.isHeadless) {
            return new Builder()
                .forBrowser(Browser.CHROME)
                .setChromeOptions(
                    new chrome.Options()
                    .addArguments('--headless').windowSize(settings.screen)
                    .addArguments('--disable-gpu')
                    .addArguments('--ignore-certificate-errors')
                )
                .setFirefoxOptions(
                    new firefox.Options()
                        .addArguments('--headless')
                        .windowSize(this.settings.screen))
                        .setCapability('acceptInsecureCerts', true)
                .build();
        }
        return new Builder().forBrowser(Browser.CHROME)
        .setChromeOptions(
            new chrome.Options()
                .addArguments('--ignore-certificate-errors')
                .addArguments('--incognito')
        )
        .build();
    }

    async go_to_url(url){
        console.log('      get: ' + url);
        return this.driver.get(url);
    }

    async enterText(findBy, searchText){
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        return this.driver.findElement(findBy).sendKeys(searchText);
    }

    async selectByValue(webElement, value){
        if (!webElement instanceof WebElement) {
            throw new Error('enterText requires instance of WebElement as first param')
        }
        const selectApi = new Select(webElement);
        return selectApi.selectByValue(value);
    }

    async getSelectSingleValue(webElement, textValue = false){
        if (!webElement instanceof WebElement) {
            throw new Error('enterText requires instance of WebElement as first param')
        }
        const select = new Select(webElement);
        const option = await select.getFirstSelectedOption();
        if (textValue) {
            return option.getText()
        }
        return option.getAttribute('value')
    }


    /**
     *
     * @param findBy By
     * @returns {Promise<WebElement>}
     */
    async getElement(findBy) {
        if (!findBy instanceof By) {
            throw new Error('getElement requires instance of By as first param')
        }
        const element = await this.driver.findElement(findBy);
        return this.driver.wait(until.elementIsVisible(element), 6000);
    }

    /**
     * Takes a parent element and strips out the textContent
     * of all child elements and returns textNode content only
     *
     * @return the text from the child textNodes
     * @param className CSS classname (without leading ".")
     */
    async getTextByClassName(className) {
        await className;
        return await this.driver.executeScript(`
        const elm = document.getElementsByClassName('${className}')[0];
        for (const child of elm.childNodes) {    
            if (child.nodeType === Node.TEXT_NODE) {
                    return child.nodeValue;
            }
        }`);
    }


    /**
     * If --debug flag is set, we might wait to see.
     *
     * @param timeout
     * @param alwaysWait bool Also wait without debug.
     * @returns {Promise<void>}
     */
    async waitToSeeWhatHappens(timeout = 2000, alwaysWait = null){
        if (process.env.npm_config_debug || alwaysWait) {
            return new Promise(resolve => setTimeout(resolve, timeout))
        }
        return Promise.resolve();
    }

    async takeScreenshot(mocha){
        // Cout up a digit number for each shot per test.
        const number = this.screenshotCount.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false})
        this.screenshotCount ++;
        const title = mocha.test.parent.title + '__' + mocha.test.title + '_' + number;
        let image = await this.driver.takeScreenshot();
        const screenshotsDir = process.env.MOCHA_SCREENSHOT_DIR ?? this.settings.screenshotsDir;
        const filename = screenshotsDir + '/' + title.replace( /[^\w-]+/g, '_' ).trim() + '.png'
        return writeFile(filename, image, 'base64')
    }
}

module.exports = BasePage;
