module.exports = {
    domain: 'open-source-event-calendar.test',
    screenshotsDir: '/tmp/integration_test_results', // No trailing slash
    headless: true,
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
