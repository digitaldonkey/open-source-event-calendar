const BasePage = require ('./BasePage');
const {Key, By, until} = require('selenium-webdriver');

class WpLogin extends BasePage {

    inputUser = 'user_login';
    inputPass = 'user_pass';

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
        await this.driver.wait(until.elementIsVisible(revealed));
        return true;
    }

}
module.exports = WpLogin;
