/**
 * Visual checks of one calendar theme, run once per theme project (metadata.theme). See README.md.
 *
 * Proof of concept for the theme chain: the theme the project expects is the active one, and the month view of the
 * anchor month matches the baseline set.
 */
const fs = require('fs');
const {test, expect} = require('@playwright/test');
const {wp, MANIFEST} = require('./lib/wp');

const manifest = () => JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));

test('the project theme is active', async ({}, testInfo) => {
    const rows = JSON.parse(wp(['osec', 'theme', 'list', '--fields=name,status', '--format=json']));
    expect(rows.find((row) => 'active' === row.status)?.name).toBe(testInfo.project.metadata.theme);
});

test('month view', async ({page}) => {
    const m = manifest();
    await page.goto(`${m.pages.calendar}action~month/exact_date~${m.anchor}/`);
    const view = page.locator('#osec-calendar-view');
    await expect(view).toBeVisible();
    await expect(view).toHaveScreenshot('month.png');
});
