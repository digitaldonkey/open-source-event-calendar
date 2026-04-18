const WpLogin = require ('./WpLogin');
const { By, Select, WebElement, until} = require('selenium-webdriver');

/**
 * Get current active theme if not set yet.
 * At first call we will save to settings.
 */
class ActivatePluginAndSettings extends WpLogin {

    async getActiveTheme(){
        if (this.settings.currentTheme) {
            return this.settings.currentTheme;
        }
        const url = this.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes';
        await this.go_and_do_login(url);
        const currenTheme = await this.getThemeIdOnThemePage();
        this.settings.currentTheme = currenTheme;
        return currenTheme;
    }

    async getThemeIdOnThemePage() {
        const currentTheme = await this.getElement(By.id('current-theme'));
        const currentThemeId = await currentTheme.getAttribute('data-id');
        return currentThemeId;
    }

    async setTheme(theme) {
        const url = this.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes';
        await this.go_and_do_login(url);
        const current = await this.getThemeIdOnThemePage();
        if (theme !== current) {
            const activateThemeLink = await this.getElement(By.className(`activatelink-${theme}`));
            await activateThemeLink.click();
            await this.waitToSeeWhatHappens(500);
        }
    }

    /**
     * Resets Osec Plugin.
     *
     * Deletes all Settings and Content.
     * Re-enables current theme if required.
     *
     * @returns {Promise<boolean>}
     */
    async resetOsecPlugin(theme = null)    {
        if (!theme) {
            theme = this.getActiveTheme();
        }
        const isActivatedClean = await this.activateOsecPlugin();
        if (!isActivatedClean) {
            return false;
        }

        const settingsUrl = this.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await this.go_and_do_login(settingsUrl);
        await this.setupOsecPlugin();
        if (theme && theme !== 'plana') {
           await this.setTheme(theme);
        }
        else if (this.settings.currentTheme && this.settings.currentTheme !== 'plana') {
            await this.setTheme(this.settings.currentTheme);
        }
        await this.doLogout();
        return true;
    }

    /**
     * Activate Osec WP Plugin
     *
     * @returns bool If install was clean. (Osec Settings not set yet).
     */
    async activateOsecPlugin() {

        const isEnabled = await this.isPluginActive();
        console.log({isEnabled})
        if (isEnabled) {
            await this.disablePluginAndCleanup();
            await this.waitToSeeWhatHappens(2000);
        }

        const url= this.settings.domain + '/wp-admin/plugins.php';
        await this.go_and_do_login(url);

        console.log('      activateOsecPlugin ENABLING PLUGIN');
        const isEnabled2 = await this.isPluginActive();
        console.log({isEnabled2})

        const enableButton = await this.getElement(By.id('activate-open-source-event-calendar'));
        console.log({enableButton});
        await enableButton.click();

        const isActivated = await this.getElement(By.id('message'));
        const message = await this.getElement(By.css('#message>p'));
        const messageText = await message.getText();

        if (messageText !== 'Plugin activated.') {
            throw new Error('Can not find `Plugin activated` message');
        }

        try{
            await this.driver.manage().setTimeouts({ implicit: 1000 });
            const configureMessage = await this.driver.findElement(By.css('.message a[href="' + this.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings"]'));
            await this.driver.manage().setTimeouts({ implicit: 60000 })
            const yyy = await configureMessage.getText();
            if (yyy === 'Click here to set it up now »') {
                return true
            }
        }
        catch(exception){
            return false;
        }
        return false;
    }


    async isPluginActive(){
        const url= this.settings.domain + '/wp-admin/plugins.php';
        await this.go_and_do_login(url);
        const isActive = await this.driver.executeScript("return document.getElementById('deactivate-open-source-event-calendar') !== null;");
        console.log('      isPluginActive: ' + isActive);
        return isActive
    }

    async disablePluginAndCleanup() {
        const url= this.settings.domain + '/wp-admin/plugins.php';
        await this.go_and_do_login(url);
        const disableButton = await this.getElement(By.id('deactivate-open-source-event-calendar'));

        console.log('      disablePluginAndCleanup: DISABLING PLUGIN', {disableButton});

        await disableButton.click()

        // Delete Plugin Page
        console.log('      disablePluginAndCleanup: DELETE PAGE(s)');
        await this.deletePluginPage();

        await this.go_and_do_login(url);
        console.log('      activateOsecPlugin CHECK IF DISABLED SUCCESSFULLY');
        const isEnabled2 = await this.isPluginActive();
        console.log({isEnabled2})
        console.log('      disablePluginAndCleanup: ALL DONE');
    }

    async deletePluginPage() {
        const url= this.settings.domain + '/wp-admin/edit.php?post_type=page';
        await this.go_and_do_login(url);
        await this.driver.manage().setTimeouts({ implicit: 1000 });
        const trashButtons = await this.driver.findElements(By.css('a[aria-label="Move “Calendar” to the Trash"]'));

        if (trashButtons.length) {
            const deletes = [];
            for(let link of trashButtons) {
                const href = await link.getAttribute('href');
                deletes.push(href);
            }
            for(let delLink of deletes) {
                await this.go_to_url(delLink);
            }
        }
        await this.driver.manage().setTimeouts({ implicit: 60000 });
    }

    async setupOsecPlugin(){

        // wait for some form element
        await this.getElement(By.id('calendar_page_id'));

        // Set day
        await this.setWeekDay(By.id('week_start_day'));

        // Set timezone to Europe/Berlin
        const timezoneSelect = await this.getElement(By.id('timezone_string'))
        await this.setTimezone(timezoneSelect);

        // Submit/Save
        const saveButtonElement = await this.getElement(By.id('osec_save_settings'));
        await saveButtonElement.click();

        // Waiting releoad.
        await this.waitToSeeWhatHappens(300, true);
        await this.getElement(By.id('calendar_page_id'));

        // Check Weekstart Day.
        const weekStart = await this.getElement(
            By.id('week_start_day')
        );
        const weekStartSelect = new Select(weekStart);
        const option = await weekStartSelect.getFirstSelectedOption();
        const isMondaySelected = option.isSelected();
        this.assert.ok(isMondaySelected);

        // Check settings pages calendar page id is saved
        // By default Select is "Create new page" after saving it should be "Calendar" and a Link is visible.
        // calenderPage value would be int WP Page ID.
        const calenderPageSelect = await this.getElement(By.id('calendar_page_id'));
        const calenderPageSelectedText = await this.getSelectSingleValue(calenderPageSelect, true);
        this.assert.ok(
            calenderPageSelectedText === 'Calendar'
        )

        // Check timezone saved.
        const timeZoneSelect = await this.getElement(By.id('timezone_string'));
        const timeZoneSelectedValue = await this.getSelectSingleValue(timeZoneSelect);
        this.assert.ok(
            timeZoneSelectedValue === this.settings.AdminPageSettings.timeZone
        );

        // New view-link below select#calendar_page_id
        const calendarLink = await this.getElement(By.css('#calendar_page_id~p>a'));
        const calendarPageLinkText = await calendarLink.getText();
        this.assert.ok(
            calendarPageLinkText === 'View "Calendar"'
        )
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

    /**
     * Open Views Selector.
     *
     * Dependend on the theme Views-selector will open by hover or by click.
     *
     * @returns {Promise<void>}
     * @param timezone
     */
    async openViewSelectorMenue(theme){
        if (theme === 'plana') {
            const popupTrigger = await this.getElement(By.className('plana-flyout-menu--button'));
            const actions = this.driver.actions();
            actions.move({origin: popupTrigger}).perform();
        }
        else {
            let viewsDropdownLink = await this.getElement(By.css('a[data-toggle="ai1ec-dropdown"]'));
            await viewsDropdownLink.click();
        }
    }

    doFailTest(isReadyToRun) {
        if (!isReadyToRun) {
            throw 'OSEC_UNINSTALL_PLUGIN_DATA must be TRUE for testing';
        }
    }

}
module.exports = ActivatePluginAndSettings;
