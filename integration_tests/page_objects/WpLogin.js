const BasePage = require ('./BasePage');
const settings = require("../settings");
const {Key, By, until} = require('selenium-webdriver');

class WpLogin extends BasePage {

    inputUser = By.id("user_login");
    inputPass = By.id("user_pass");

    async doLogin(auth){
        if (!auth) {
            auth = settings.wpLogin.admin;
        }
        await this.enterText(this.inputUser, auth.user);
        await this.enterText(this.inputPass, auth.pass);
        await this.enterText(this.inputUser, Key.RETURN);

        const revealed = await driver.findElement(By.css("body.wp-admin"));
        return await driver.wait(until.elementIsVisible(revealed));
    }

}
module.exports = WpLogin;
