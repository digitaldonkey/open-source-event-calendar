//wp-admin/plugins.php
const WpPlugin = require('../page_objects/ActivatePluginAndSettings');

const {
    until,
    By, Select,
} = require('selenium-webdriver');
let pageObject = null;

describe('Plugin install & Setup', function(){
    this.timeout(50000);

    before (async function() {
        pageObject = await WpPlugin.build();
    })

    afterEach(async function(){
        //Enter actions to be performed after test
        await pageObject.doLogout();
    });

    after(async () => {
        // suite after all emission
        await pageObject.driver.quit();
    });


    it('WordPress activate Osec plugin', async function () {
        const url= pageObject.settings.domain + '/wp-admin/plugins.php';
        await pageObject.go_and_do_login(url);

        const isActivatedClean = await pageObject.activateOsecPlugin();
        await pageObject.takeScreenshot(this);

        pageObject.assert.ok(
            isActivatedClean,
            'The install is new at plugin activate.'
        );
    });

    it('Update osec settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_and_do_login(url);
        await pageObject.setupOsecPlugin(this);
    });

    it('Check for OSEC_UNINSTALL_PLUGIN_DATA to be TRUE by re-enabling.', async function () {
        // Re-enable the plugin
        const url= pageObject.settings.domain + '/wp-admin/plugins.php';
        await pageObject.go_and_do_login(url);

        const isActivatedClean = await pageObject.activateOsecPlugin();
        pageObject.assert.ok(
            isActivatedClean,
            'All data hase been purged. If OSEC_UNINSTALL_PLUGIN_DATA=TRUE data will be purged on reinstall.'
        );
    });
})
