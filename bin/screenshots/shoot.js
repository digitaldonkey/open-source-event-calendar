/**
 * Takes the WordPress.org screenshots listed in shots.json (assets/screenshot-*.png), with Playwright, inside the DDEV
 * web container. See README.md.
 *
 *   node bin/screenshots/shoot.js [--out=<dir>] [screenshot-N.png ...]
 *
 * Env: WP_SANDBOX_DB (site; unset: the DDEV dev site), PLAYWRIGHT_WS.
 *
 * First generates the showcase content (`wp osec content generate --set=showcase --site-settings`; it refuses a site
 * with calendar data it did not create), then shoots every listed shot, or only the named files, into --out
 * (default: the plugin's assets/). A failing shot is reported and the run goes on; exit code 1 if any failed. Manual
 * shots are listed at the end. Shot keys: README.md.
 *
 * Browser connection, WP-CLI and login come from the visual regression suite (integration_tests/visual_regression/lib/).
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const {chromium} = require('@playwright/test');
const {PLUGIN_DIR, wp, site, connectOptions} = require('../../integration_tests/visual_regression/lib/wp');
const {settle} = require('../../integration_tests/visual_regression/lib/pages');

const DATASET = 'tests/data/content/dataset.json';
const AUTH_COOKIES = path.join(PLUGIN_DIR, 'integration_tests/visual_regression/lib/auth-cookies.php');

const args = process.argv.slice(2);
const outDir = path.resolve((args.find((a) => a.startsWith('--out=')) || `--out=${path.join(PLUGIN_DIR, 'assets')}`).slice(6));
const only = args.filter((a) => !a.startsWith('--'));
const config = JSON.parse(fs.readFileSync(path.join(__dirname, 'shots.json'), 'utf8'));

/**
 * Generates the showcase content and returns its manifest.
 *
 * @returns {object} Manifest (anchor, pages, events by key).
 */
function generate() {
    const file = path.join(os.tmpdir(), `osec-screenshots-${process.pid}.json`);
    try {
        wp(['osec', 'content', 'generate', `--dataset=${DATASET}`, '--set=showcase', '--site-settings', `--manifest=${file}`]);
        return JSON.parse(fs.readFileSync(file, 'utf8'));
    } finally {
        fs.rmSync(file, {force: true});
    }
}

/**
 * Warns when the site's WordPress theme is not the dataset's: the images would show another theme around the calendar.
 */
function checkWordPressTheme() {
    const wanted = JSON.parse(fs.readFileSync(path.join(PLUGIN_DIR, DATASET), 'utf8')).site.theme;
    const active = wp(['theme', 'list', '--status=active', '--field=name']);
    if (wanted && active !== wanted) {
        console.warn(`warning: the WordPress theme is ${active}, the dataset expects ${wanted} (wp theme activate ${wanted}).`);
    }
}

/**
 * The shot's URL path: its path with {calendar} and {anchor} filled in, or the edit screen of its event.
 *
 * @param {object} m Manifest.
 * @param {object} shot
 * @returns {string}
 */
function shotPath(m, shot) {
    if (shot.edit_event) {
        if (!m.events[shot.edit_event]) {
            throw new Error(`event "${shot.edit_event}" is not in the manifest`);
        }
        return `/wp-admin/post.php?post=${m.events[shot.edit_event].id}&action=edit`;
    }
    return shot.path.replace('{calendar}', m.pages.calendar).replace('{anchor}', m.anchor);
}

/**
 * Opens a page in the block editor, closes the welcome guide and selects the calendar block, so its toolbar and
 * settings show. Returns the block element (inside the editor canvas iframe).
 *
 * @param {import('@playwright/test').Page} page
 * @param {object} m Manifest.
 * @param {string} pageKey Key under manifest.pages, e.g. "block".
 */
async function editorBlock(page, m, pageKey) {
    await page.goto(`/wp-admin/post.php?post=${m.pages[pageKey].id}&action=edit`);
    await page.waitForFunction(() => window.wp?.data?.select('core/block-editor').getBlocks().length);
    await page.evaluate(() => {
        wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
        const block = wp.data.select('core/block-editor').getBlocks().find((b) => b.name.endsWith('osec-calendar-classic'));
        wp.data.dispatch('core/block-editor').selectBlock(block.clientId);
    });
    const block = page.frameLocator('iframe[name="editor-canvas"]').locator('[data-type$="osec-calendar-classic"]');
    await block.waitFor();
    await block.scrollIntoViewIfNeeded();
    await settle(page, {networkIdle: false});
    return block;
}

/**
 * Takes one shot into outDir: opens it, scrolls, clicks, hovers (or parks the pointer in the bottom right corner, so
 * no popover or admin menu opens under it), waits, saves.
 *
 * @param {import('@playwright/test').Browser} browser
 * @param {object} m Manifest.
 * @param {object} shot Entry of shots.json.
 * @param {object[]} cookies Login cookies for admin shots.
 */
async function shoot(browser, m, shot, cookies) {
    const context = await browser.newContext({
        baseURL: site().baseURL,
        viewport: {width: shot.width, height: shot.height},
        deviceScaleFactor: config.scale || 2,
        isMobile: !!shot.mobile,
        hasTouch: !!shot.mobile,
        ignoreHTTPSErrors: true,
    });
    if (shot.admin || shot.editor_block) {
        await context.addCookies(cookies);
    }
    const page = await context.newPage();
    try {
        let element = null;
        if (shot.editor_block) {
            element = await editorBlock(page, m, shot.editor_block);
        } else {
            await page.goto(shotPath(m, shot));
            await settle(page);
        }
        if (shot.scroll_to) {
            await page.locator(shot.scroll_to).first().evaluate((el) => {
                el.scrollIntoView({block: 'start'});
                window.scrollBy(0, -50);
            });
        }
        if (shot.click) {
            await page.locator(shot.click).first().click();
        }
        if (shot.hover) {
            if (shot.hover_event && !m.events[shot.hover_event]) {
                throw new Error(`event "${shot.hover_event}" is not in the manifest`);
            }
            const title = shot.hover_event ? m.events[shot.hover_event].title : null;
            const target = title ? page.locator(shot.hover).filter({hasText: title}).first() : page.locator(shot.hover).first();
            await target.hover();
        } else {
            await page.mouse.move(shot.width - 2, shot.height - 2);
        }
        if (shot.wait_for) {
            await page.locator(shot.wait_for).first().waitFor({state: 'visible'});
        }
        await page.waitForTimeout(800);
        const file = path.join(outDir, shot.file);
        await (element ? element.screenshot({path: file}) : page.screenshot({path: file}));
    } finally {
        await context.close();
    }
}

(async () => {
    const m = generate();
    checkWordPressTheme();
    const cookies = JSON.parse(wp(['eval-file', AUTH_COOKIES]));
    fs.mkdirSync(outDir, {recursive: true});
    const {wsEndpoint, headers} = connectOptions('chromium');
    const browser = await chromium.connect(wsEndpoint, {headers});
    const manual = [];
    let failed = 0;
    try {
        for (const shot of config.shots) {
            if (only.length && !only.includes(shot.file)) {
                continue;
            }
            if (shot.manual) {
                manual.push(`${shot.file}  ${shot.caption}: ${shot.manual}`);
                continue;
            }
            try {
                await shoot(browser, m, shot, cookies);
                console.log(`${shot.file}  ${shot.caption}`);
            } catch (e) {
                failed++;
                console.error(`${shot.file}  FAILED: ${e.message.split('\n')[0]}`);
            }
        }
    } finally {
        await browser.close();
    }
    manual.forEach((line) => console.log(`manual: ${line}`));
    const inRepository = !path.relative(PLUGIN_DIR, outDir).startsWith('..');
    console.log(`Written to ${outDir}${inRepository ? `; review with: git diff --stat ${path.relative(PLUGIN_DIR, outDir)}` : ''}`);
    process.exit(failed ? 1 : 0);
})().catch((e) => {
    console.error(e.message);
    process.exit(1);
});
