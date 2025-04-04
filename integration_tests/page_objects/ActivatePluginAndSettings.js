const WpLogin = require ('./WpLogin');
const {Key, By, Select, WebElement, until} = require('selenium-webdriver');

class ActivatePluginAndSettings extends WpLogin {

    /**
     * Activate Osec WP Plugin
     *
     * @returns bool
     */
    async activateOsecPlugin(){
        const revealed = await this.driver.findElement(By.id('activate-open-source-event-calendar'));
        await this.driver.wait(until.elementIsVisible(revealed));
        await revealed.click();
        const isActivated = await this.driver.findElement(By.id('message'));
        await this.driver.wait(until.elementIsVisible(isActivated));
        const message = await this.driver.findElement(By.css('#message>p'));
        const xxx = await message.getText();

        if (xxx !== 'Plugin activated.') {
            throw new Error('Can not find `Plugin activated` message');
        }

        const configureMessage = await this.driver.findElement(By.css('.message a[href="' + this.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings"]'));
        const yyy = await configureMessage.getText();
        if (yyy !== 'Click here to set it up now »') {
            throw new Error('Can not find `Click here to set it up now »` message');
        }
        return true;
    }


    /**
     * Set Weekday in Osec Settings Form.
     *
     * @param findBy
     * @param weekDay ?int [0->Sun, 1->Mon ... 6->Sat].
     * @returns {Promise<void>}
     */
    async setWeekDay(findBy, weekDay = null){
        if (!weekDay) {
            weekDay = this.settings.AdminPageSettings.weekDay;
        }
        if (!findBy instanceof By) {
            throw new Error('enterText requires instance of By as first param')
        }
        const weekStartSelectElement = await this.getElement(findBy)
        const select = new Select(weekStartSelectElement)
        return await select.selectByValue(weekDay)
    }

    /**
     * Set Timezone in Osec Settings Form.
     *
     * @returns {Promise<void>}
     * @param timezone
     */
    async setTimezone(webElement, timezone = null){
        if (!timezone) {
            timezone = this.settings.AdminPageSettings.timeZone;
        }
        if (!webElement instanceof WebElement) {
            throw new Error('enterText requires instance of WebElement as first param')
        }
        return await this.selectByValue(webElement, timezone);
    }

}
module.exports = ActivatePluginAndSettings;
