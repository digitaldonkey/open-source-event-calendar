module.exports = {
    domain: 'https://ddev-wordpress.ddev.site',
    screenshotsDir: 'screen_shots', // No trailing slash
    wpLogin: {
        admin: {
            user: 'admin',
            pass: 'password',
        }
        // admin: {
        //     user: 'tho',
        //     pass: 'resox232',
        // }
    },
    AdminPageSettings: {
        weekDay: "1", // Monday.
        timeZone: 'Europe/Berlin'
    }
};
