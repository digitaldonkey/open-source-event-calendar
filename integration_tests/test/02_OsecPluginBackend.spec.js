//wp-admin/plugins.php
const WpPlugin = require('../page_objects/ActivatePluginAndSettings');

const {
    By,
} = require('selenium-webdriver');
let pageObject = null;



describe('Backend tests', function(){
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


    it('View osec cache settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings';
        await pageObject.go_and_do_login(url);

        // Wait.
        // TODO For now just a screenshot to see...
        await pageObject.getElement(By.id('osec_event_cache_settings'))
        await pageObject.takeScreenshot(this);
    })

    it('View and save osec theme settings', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-edit-css';
        await pageObject.go_and_do_login(url);

        await pageObject.getElement(By.id( 'agendaTodayBackground'))

        // Submit/Save
        const saveOptionsButton = await pageObject.getElement(By.id( 'osec_save_themes_options'))
        await saveOptionsButton.click();

        await pageObject.waitToSeeWhatHappens(700, true);

        // Required to wait again.
        await pageObject.getElement(By.id( 'agendaTodayBackground'))

        // "Theme options were updated successfully. Visit site" should be visible.
        const expectedText = 'Visit site';
        const optionsSaved = await pageObject.getElement(
            By.xpath("//a[text()='" + expectedText + "']")
        )
        const visibleText = await optionsSaved.getText();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
    });

    it('Add ICS calendar feed', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds';
        await pageObject.go_and_do_login(url);

        // Metabox link should be visible.
        const mainElementID = 'osec_event_feeds';
        await pageObject.getElement(By.id(mainElementID) );

        // Submit/Save
        const feedUrl = 'https://ics.calendarlabs.com/641/64bc8358/FIFA_Womens_World_Cup.ics'
        const feedUrlInput = await pageObject.getElement(By.id( 'osec_feed_url'));
        await feedUrlInput.sendKeys(feedUrl);

        // osec_ics_add_new
        const feedUrlAddButton = await pageObject.getElement(By.id( 'osec_ics_add_new'));
        await feedUrlAddButton.click();

        // Required to call/wait again.
        await pageObject.getElement(By.id(mainElementID));

        // .ai1ec-alert-success should contain "Imported 53 events"
        const expectedText = 'Imported 53 events';

        // Get text without child Elements (aka: dismiss alert X)
        await pageObject.getElement(By.css('.ai1ec-alert'));
        const visibleText = await pageObject.getTextByClassName('ai1ec-alert');

        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
        // const optionsSaved = await pageObject.driver.findElement(By.xpath("//.ai1ec-alert-success[text()='" + expectedText + "']"));
        // const visibleText = await optionsSaved.getText();
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
    });

    it('Delete ICS calendar feed', async function () {
        const url = pageObject.settings.domain + '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds';
        await pageObject.go_and_do_login(url);

        // Metabox be visible.
        const mainElementID = 'osec_event_feeds';
        await pageObject.getElement(By.id(mainElementID));

        // Open ICS details.
        const panelLink = await pageObject.getElement(By.css('#osec-feeds-list details:first-of-type > summary'));
        await panelLink.click();

        // osec_delete_ics
        const deleteButton = await pageObject.getElement(By.css('.osec_delete_ics'));
        await deleteButton.click();

        // Confirm delete events dialogue
        // osec_delete_ics
        const deleteEventsButton = await pageObject.getElement(By.css('.ai1ec-btn.remove'));
        await deleteEventsButton.click();

        // Required to call/wait again.
        await pageObject.getElement(By.id(mainElementID));
        await pageObject.getElement(By.css('.ai1ec-alert'));

        // .ai1ec-alert should contain "Deleted 53 events"
        const expectedText = 'Deleted 53 events';

        // Get text without child Elements (aka: dismiss alert X)
        const visibleText = await pageObject.getTextByClassName('ai1ec-alert');
        //
        await pageObject.takeScreenshot(this);
        pageObject.assert.ok(
            expectedText === visibleText
        )
    });
})
