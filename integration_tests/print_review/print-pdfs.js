/**
 * Print calendar views to PDF for manual review of the print stylesheet.
 *
 * Chrome goes through CDP (Page.printToPDF with preferCSSPageSize), Firefox through the
 * WebDriver "Print Page" command; neither fires `beforeprint` on its own, so the Ctrl+P
 * path dispatches the event by hand and the button path stubs `window.print`.
 *
 * Usage: node print-pdfs.js <browser> <label> <outDir> [views...]
 * Env: BASE_URL, DATE, MODES (button,ctrlp), LONG_TEXT, REVEAL,
 *      SELENIUM_CHROME_URL, SELENIUM_FIREFOX_URL
 */
const path = require('path');
const fs = require('fs');
const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');
const firefox = require('selenium-webdriver/firefox');

const [browser = 'chrome', label = 'run', outDir = '/tmp'] = process.argv.slice(2);
const views = process.argv.length > 5 ? process.argv.slice(5) : ['month', 'week', 'oneday', 'agenda'];
const base = (process.env.BASE_URL || 'https://ddev-wordpress.ddev.site') + '/calendar/';
const date = process.env.DATE || '15-9-2026';
const modes = (process.env.MODES || 'ctrlp,button').split(',');

const now = new Date();
const pad = (n) => String(n).padStart(2, '0');
const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}`;

function build() {
    if (browser === 'firefox') {
        return new Builder().forBrowser('firefox')
            .setFirefoxOptions(new firefox.Options().setAcceptInsecureCerts(true)
                .addArguments('--width=1400', '--height=1000'))
            .usingServer(process.env.SELENIUM_FIREFOX_URL || 'http://selenium-firefox:4444/wd/hub')
            .build();
    }
    return new Builder().forBrowser('chrome')
        .setChromeOptions(new chrome.Options()
            .addArguments('--ignore-certificate-errors', '--window-size=1400,1000'))
        .usingServer(process.env.SELENIUM_CHROME_URL || 'http://selenium-chrome:4444/wd/hub')
        .build();
}

// The named @page in the print CSS decides the orientation in both browsers,
// so no orientation is passed here.
async function toPdf(driver) {
    if (browser === 'firefox') {
        return driver.printPage({ background: false, shrinkToFit: false });
    }
    const res = await driver.sendAndGetDevToolsCommand('Page.printToPDF', {
        preferCSSPageSize: true,
        printBackground: false,
    });
    return res.data;
}

(async () => {
    fs.mkdirSync(outDir, { recursive: true });
    const driver = await build();
    try {
        for (const view of views) {
            for (const mode of modes) {
                await driver.get(`${base}action~${view}/exact_date~${date}/`);
                await driver.wait(until.elementLocated(By.css('#osec-calendar-view .osec-print-header')), 45000);
                await driver.sleep(2000);
                if (process.env.LONG_TEXT) {
                    // Long descriptions, to see where agenda events break across pages.
                    await driver.executeScript("document.querySelectorAll('.ai1ec-agenda-view .ai1ec-event-description').forEach(function (d, i) { if (i % 3 === 1) { d.innerHTML = new Array(i + 4).join(d.innerHTML); } });");
                }
                if (process.env.REVEAL) {
                    await driver.executeScript("var b = document.querySelector('.ai1ec-reveal-full-day button'); b && b.click();");
                    await driver.sleep(1500);
                }
                if (mode === 'button') {
                    // Throwing keeps the page in its print state for the PDF.
                    await driver.executeScript("window.print = function () { throw new Error('print stub'); };");
                    await driver.findElement(By.id('ai1ec-print-button')).click();
                    await driver.sleep(600);
                } else {
                    await driver.executeScript("window.dispatchEvent(new Event('beforeprint'));");
                    await driver.sleep(400);
                }
                const file = path.join(outDir, `${label}-${view}-${mode}-${stamp}.pdf`);
                fs.writeFileSync(file, Buffer.from(await toPdf(driver), 'base64'));
                console.log(file);
            }
        }
    } finally {
        await driver.quit();
    }
})().catch((e) => {
    console.error(`${browser}/${label}: ${e.message}`);
    process.exit(1);
});
