const BasePage = require ('./BasePage');
const {Key, By, until} = require('selenium-webdriver');

class WpLogin extends BasePage {

    inputUser = 'user_login';
    inputPass = 'user_pass';

    /**
     * As WP Cookies are 'httpOnly' and 'secure'
     * we need to perform logout on server side,
     * as driver.deleteAllCookies() does not work.
     *
     * @returns {Promise<void>}
     */
    async doLogout(){
        // Hover logout
        const popupTrigger = await this.getElement(By.id('wp-admin-bar-top-secondary'));
        const actions = this.driver.actions({async: true});
        await actions.move({origin: popupTrigger}).perform();
        const logoutLink = await this.getElement(By.css('#wp-admin-bar-logout a'));
        const logoutUrl = await logoutLink.getAttribute('href');
        this.waitToSeeWhatHappens(500, true)
        this.go_to_url(logoutUrl);
    }

    async doLogin(auth){
        if (!auth) {
            auth = this.settings.wpLogin.admin;
        }
        // Wait a little
        await new Promise(resolve => setTimeout(resolve, 500));

        const userInput = By.id(this.inputUser);
        const passInput = By.id(this.inputPass);

        const userInputElement = await this.driver.findElement(userInput);
        await this.driver.wait(until.elementIsVisible(userInputElement));
        const passInputElement = await this.driver.findElement(userInput);
        await this.driver.wait(until.elementIsVisible(passInputElement));

        await this.enterText(userInput, auth.user);
        await this.enterText(passInput, auth.pass);
        await this.enterText(passInput, Key.RETURN);

        const revealed = await this.driver.findElement(By.css("body.wp-admin"), 6000);
        return this.driver.wait(until.elementIsVisible(revealed));
    }

}
module.exports = WpLogin;
