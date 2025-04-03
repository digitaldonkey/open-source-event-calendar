module.exports = {
    domain: 'https://ddev-wordpress.ddev.site',
    screenshotsDir: 'screen_shots', // No trailing slash
    headless: false,
    screen: {
        width: 1260,
        height: 2520
    },
    wpLogin: {
        admin: {
            user: 'admin',
            pass: 'password',
        }
    },
    AdminPageSettings: {
        weekDay: "1", // Monday.
        timeZone: 'Europe/Berlin'
    }
};
