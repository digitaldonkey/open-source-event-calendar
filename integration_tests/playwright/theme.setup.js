/**
 * Setup project `setup-<theme>`: activates the calendar theme of the project (metadata.theme) and makes the first
 * request, which compiles its CSS (a theme switch leaves the compile to the next request).
 */
const {test: setup, expect} = require('@playwright/test');
const {wp} = require('./lib/wp');

setup('activate calendar theme', async ({request, baseURL}, testInfo) => {
    const theme = testInfo.project.metadata.theme;
    const current = wp(['osec', 'theme', 'list', '--fields=name,status', '--format=json']);
    if (!JSON.parse(current).some((row) => row.name === theme && 'active' === row.status)) {
        wp(['osec', 'theme', 'activate', theme]);
    }
    const response = await request.get(`${baseURL}/`);
    expect(response.ok()).toBeTruthy();
});
