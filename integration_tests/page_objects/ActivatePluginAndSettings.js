const WpLogin = require ('./WpLogin');
const {Key, By, until} = require('selenium-webdriver');

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
        const isAvtivated = await this.driver.findElement(By.id('message'));
        await this.driver.wait(until.elementIsVisible(isAvtivated));
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
     * @param weekDay ?int [0->Sun, 1->Mon ... 6->Sat].
     * @returns {Promise<void>}
     */
    async setWeekDay(weekDay = null){
        if (!weekDay) {
            weekDay = this.settings.AdminPageSettings.weekDay;
        }
        const weekStartSelect = By.id("week_start_day");

         return this.selectByValue(weekStartSelect,  String(weekDay));
        // return this.waitToSeeWhatHappens();
    }

    /**
     * Set Timezone in Osec Settings Form.
     *
     * @param weekDay ?int [0->Sun, 1->Mon ... 6->Sat].
     * @returns {Promise<void>}
     */
    async setTimezone(timezone = null){
        if (!timezone) {
            timezone = this.settings.AdminPageSettings.timeZone;
        }
        const timezoneSelect = By.id("timezone_string");
        return this.selectByValue(timezoneSelect, timezone);
    }

}
module.exports = ActivatePluginAndSettings;
