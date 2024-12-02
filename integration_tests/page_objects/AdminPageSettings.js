const WpLogin = require ('./WpLogin');
const {Key, By, until} = require('selenium-webdriver');

class AdminPageSettings extends WpLogin {

    /*  @var int weekday [0->Sun,1->Mon...6->Sat]*/
    async setWeekDayTo(weekDay = null){
        if (!weekDay) {
            weekDay = settings.AdminPageSettings.weekDay;
        }
        const weekStartSelect = By.id("week_start_day");
        this.selectByValue(weekStartSelect, weekDay);
        // await this.waitToSeeWhatHappens();
    }
}
module.exports = AdminPageSettings;
