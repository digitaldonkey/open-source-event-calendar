/**
 * Checks the month view's multi-day continuation arrows, on screen and in print.
 *
 * Both are built the same way - two half-height gradient triangles in a box stretched with
 * top/bottom - so the only thing worth asserting is that the triangle always spans the box it
 * belongs to. The bar's height is changed *live* here, with no reload and no interaction, so a
 * fix that only lands at page load fails this.
 *
 * On screen the arrows are the .ai1ec-multiday-arrow* divs, masking the bar's ends in the page
 * background colour. In print the bar is a white box, so nothing is masked out: the shape is
 * redrawn as a solid triangle in the category colour, on .ai1ec-event's ::before/::after (only
 * that element carries --osec-event-color). Hence the two measurement paths below.
 *
 * Usage: node arrow-check.js [outDir]
 * Env: BASE_URL, DATE, SELENIUM_CHROME_URL
 *      BASE_FONT  override .timely's font size, e.g. BASE_FONT=13px
 *
 * The arrows' depth is @multiday-arrow-depth (.5em), so it follows the "Base font size" theme
 * setting - and each theme ships a different default for it (vortex 13px, plana 1rem, umbra
 * 0.8rem), which is also why switching theme changes the whole compiled CSS. BASE_FONT fakes
 * another theme's base size live, so the arrows can be checked against it without switching
 * theme in the database.
 */
const path = require('path');
const fs = require('fs');
const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

const outDir = process.argv[2];
const base = (process.env.BASE_URL || 'https://ddev-wordpress.ddev.site') + '/calendar/';
const date = process.env.DATE || '15-9-2026';

// Height is what is under test, so drive it from the event text rather than the theme's own
// base font size - the latter is clamped by a fixed height on .ai1ec-month-view .ai1ec-event.
const HEIGHTS = [
    ['default', ''],
    ['grown', 'font-size: 30px !important; line-height: 1.6 !important; height: auto !important;'],
    ['huge', 'font-size: 52px !important; line-height: 1.8 !important; height: auto !important;'],
];

function style(driver, id, css) {
    return driver.executeScript(`
        var el = document.getElementById(${JSON.stringify(id)});
        if (!el) {
            el = document.createElement('style');
            el.id = ${JSON.stringify(id)};
            document.head.appendChild(el);
        }
        el.textContent = ${JSON.stringify(css)};`);
}

async function shoot(driver, clip, file) {
    const res = await driver.sendAndGetDevToolsCommand('Page.captureScreenshot', {
        format: 'png', captureBeyondViewport: true, clip: { ...clip, scale: 4 },
    });
    fs.writeFileSync(file, Buffer.from(res.data, 'base64'));
}

(async () => {
    const driver = await new Builder().forBrowser('chrome')
        .setChromeOptions(new chrome.Options()
            .addArguments('--ignore-certificate-errors', '--hide-scrollbars', '--window-size=1600,1200'))
        .usingServer(process.env.SELENIUM_CHROME_URL || 'http://selenium-chrome:4444/wd/hub')
        .build();
    let failures = 0;
    try {
        await driver.get(base + 'action~month/exact_date~' + date + '/');
        await driver.sendAndGetDevToolsCommand('Emulation.setDeviceMetricsOverride', {
            width: 1440, height: 1400, deviceScaleFactor: 1, mobile: false,
        });
        await driver.wait(until.elementLocated(By.css('.ai1ec-month-view')), 45000);
        await driver.sleep(1500);
        if (outDir) { fs.mkdirSync(outDir, { recursive: true }); }
        if (process.env.BASE_FONT) {
            await style(driver, 'arrow-check-base',
                '.timely { font-size: ' + process.env.BASE_FONT + ' !important; }');
            console.log('base font size forced to ' + process.env.BASE_FONT);
        }

        for (const media of ['screen', 'print']) {
            await driver.sendAndGetDevToolsCommand('Emulation.setEmulatedMedia', { media });
            if (media === 'print') {
                // Sample events carry no category, so --osec-event-color is unset and the #999
                // fallback would be all that is exercised. Force a colour in as well.
                await style(driver, 'arrow-check-color',
                    '.ai1ec-month-view .ai1ec-multiday .ai1ec-event { --osec-event-color: #b5342a; }');
            }
            for (const [label, css] of HEIGHTS) {
                await style(driver, 'arrow-check-height',
                    css ? '.ai1ec-month-view .ai1ec-multiday .ai1ec-event {' + css + '}' : '');
                await driver.sleep(500);

                const found = await driver.executeScript(`
                    var print = ${JSON.stringify(media === 'print')};
                    var out = [];
                    document.querySelectorAll('.ai1ec-month-view .ai1ec-multiday').forEach(function (bar) {
                        var arrow = bar.querySelector('[class*=ai1ec-multiday-arrow]');
                        if (!arrow) { return; }
                        var right = arrow.className.indexOf('arrow1') !== -1;
                        var ev = bar.querySelector('.ai1ec-event');
                        // In print the shape hangs off .ai1ec-event and is pulled out over
                        // that element's border, so it has to span the full border box - the
                        // bounding rect as-is. On screen it spans the bar.
                        var host = print ? ev : bar;
                        var r = host.getBoundingClientRect();
                        var box = r.height;
                        var got = print
                            ? parseFloat(getComputedStyle(ev, right ? ':after' : ':before').height)
                            : arrow.getBoundingClientRect().height;
                        out.push({
                            end: right ? 'right' : 'left',
                            box: +box.toFixed(2), got: +got.toFixed(2),
                            x: r.left + window.scrollX, y: r.top + window.scrollY,
                            w: r.width, h: r.height,
                        });
                    });
                    return out;`);

                if (!found.length) { throw new Error('no multi-day bar with an arrow found'); }
                for (const d of found) {
                    const ok = Math.abs(d.box - d.got) < 0.5;
                    if (!ok) { failures++; }
                    console.log([media, label, d.end].join('/') + ': box ' + d.box
                        + ', triangle ' + d.got + ' -> ' + (ok ? 'match' : 'MISMATCH'));
                    if (!outDir) { continue; }
                    const pad = 16;
                    await shoot(driver, {
                        x: d.end === 'right' ? Math.max(0, d.x + d.w - 220) : Math.max(0, d.x - pad),
                        y: Math.max(0, d.y - pad), width: 220, height: d.h + pad * 2,
                    }, path.join(outDir, [media, label, d.end].join('-') + '.png'));
                }
            }
        }
    } finally {
        await driver.quit();
    }
    if (failures) {
        console.error(failures + ' mismatch(es)');
        process.exit(1);
    }
    console.log('all match');
})().catch((e) => { console.error(e.message); process.exit(1); });
