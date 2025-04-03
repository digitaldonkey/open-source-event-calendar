module.exports = {
    domain: 'http://open-source-event-calendar.test',
    screenshotsDir: '/tmp/integration_test_results', // No trailing slash
    headless: true,
    screen: {
        width: 1280,
        height: 1280
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
