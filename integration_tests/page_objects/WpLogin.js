const BasePage = require ('./BasePage');
const {Key, By, until} = require('selenium-webdriver');

class WpLogin extends BasePage {

    inputUser = By.id("user_login");
    inputPass = By.id("user_pass");

    async doLogin(auth){
        if (!auth) {
            auth = this.settings.wpLogin.admin;
        }
        await this.enterText(this.inputUser, auth.user);
        await this.enterText(this.inputPass, auth.pass);
        await this.enterText(this.inputUser, Key.RETURN);

        const revealed = await this.driver.findElement(By.css("body.wp-admin"), 6000);
        return await this.driver.wait(until.elementIsVisible(revealed));
    }

}
module.exports = WpLogin;
