/**
 * Playwright reporter writing the review page: one row per screenshot with baseline, diff and current shot side by
 * side (lib/review-page.html), as a static folder the site serves.
 *
 *   reporter: [['./lib/review-reporter.js', {outputFolder, baselinesFolder, set, site}]]   (playwright.config.js)
 *
 * Options:
 *   outputFolder     Where the page goes (emptied first): index.html, data.js, images/, pdf/.
 *   baselinesFolder  The baseline set's folder (<baselines>/<set>), laid out like snapshotPathTemplate:
 *                    <project>/<name segments>.
 *   set, site        Shown in the page header.
 *
 * Reads what lib/pages.js compareShot() attaches: `osec-review` (the snapshot name) and, for a passing comparison,
 * `osec-current` (the current shot); for a failed one Playwright's own `*-expected.png`, `*-actual.png`, `*-diff.png`.
 * PDFs attached by specs/print.spec.js are linked. All images are copied, so a page keeps showing its run when the
 * baselines change later.
 */
const fs = require('fs');
const path = require('path');

class ReviewReporter {
    /**
     * @param {{outputFolder: string, baselinesFolder: string, set: string, site: string}} options
     */
    constructor(options) {
        this.options = options;
        this.rows = [];
    }

    /**
     * Collects one test that compared a screenshot (others - setup, theme checks - are left out).
     *
     * @param {import('@playwright/test/reporter').TestCase} test
     * @param {import('@playwright/test/reporter').TestResult} result
     */
    onTestEnd(test, result) {
        const review = result.attachments.find((a) => 'osec-review' === a.name);
        if (!review) {
            return;
        }
        const { name } = JSON.parse(review.body.toString());
        const project = test.parent.project().name;
        const find = (suffix) => result.attachments.find((a) => a.name.endsWith(suffix) && a.path);
        const titles = test.titlePath().slice(3);
        const error = result.errors.map((e) => e.message || '').join('\n').replace(/\u001b\[[0-9;]*m/g, '');
        const pixels = error.match(/(\d+) pixels \(ratio ([\d.]+)/);
        const size = error.match(/Expected an image (\d+)px by (\d+)px, received (\d+)px by (\d+)px/);
        this.rows.push({
            // Reference ID: the baseline's path inside its set (stable across runs; lib/approve.js takes it).
            ref: [
                project,
                ...name,
            ].join('/'),
            project,
            titles,
            theme: (titles.find((t) => t.startsWith('Calendar theme ')) || '').replace('Calendar theme ', ''),
            width: (titles.map((t) => t.match(/^(\d+) px wide$/)).find(Boolean) || [])[1] || '',
            description: (test.annotations.find((a) => 'description' === a.type) || {}).description || '',
            status: result.status,
            pixels: pixels ? Number(pixels[1]) : 0,
            ratio: pixels ? Number(pixels[2]) : 0,
            size: size ? `${size[1]}×${size[2]} → ${size[3]}×${size[4]}` : '',
            error: 'passed' === result.status ? '' : error.split('\n').find((l) => l.trim()) || result.status,
            sources: {
                old: (find('-expected.png') || {}).path || path.join(this.options.baselinesFolder, project, ...name),
                diff: (find('-diff.png') || {}).path || null,
                current: (find('-actual.png') || {}).path || null,
                currentBody: (result.attachments.find((a) => 'osec-current' === a.name) || {}).body || null,
            },
            pdfs: result.attachments.filter((a) => 'application/pdf' === a.contentType),
        });
    }

    /**
     * Copies the images and PDFs, writes data.js and index.html.
     */
    onEnd() {
        const out = this.options.outputFolder;
        fs.rmSync(out, {
            recursive: true,
            force: true,
        });
        fs.mkdirSync(path.join(out, 'images'), { recursive: true });
        fs.mkdirSync(path.join(out, 'pdf'), { recursive: true });
        const rows = this.rows.map((row) => {
            // The row's UID: its reference without the extension (e.g. chromium/plana/month-1280), unique and the
            // same in every run, unlike a row number, which shifts when screens are added. Its files go below it.
            const id = row.ref.replace(/\.png$/, '');
            const write = (file, store) => {
                fs.mkdirSync(path.dirname(path.join(out, file)), { recursive: true });
                store(path.join(out, file));
                return file;
            };
            const copy = (source, kind) => (source && fs.existsSync(source)
                ? write(`images/${id}/${kind}.png`, (to) => fs.copyFileSync(source, to))
                : null);
            let current = copy(row.sources.current, 'current');
            if (!current && row.sources.currentBody) {
                current = write(`images/${id}/current.png`, (to) => fs.writeFileSync(to, row.sources.currentBody));
            }
            const pdfs = row.pdfs.map((a, n) => {
                const file = write(`pdf/${id}/${n + 1}-${a.name.replace(/[^a-z0-9._-]/gi, '_')}`, (to) => (a.path
                    ? fs.copyFileSync(a.path, to)
                    : fs.writeFileSync(to, a.body)));
                return {
                    name: a.name,
                    file,
                };
            });
            const { sources, ...data } = row;
            return {
                ...data,
                id,
                old: copy(sources.old, 'old'),
                diff: copy(sources.diff, 'diff'),
                current,
                pdfs,
            };
        });
        const data = {
            set: this.options.set,
            site: this.options.site,
            date: new Date().toISOString(),
            rows,
        };
        fs.writeFileSync(path.join(out, 'data.js'), `window.OSEC_REVIEW = ${JSON.stringify(data, null, 1)};\n`);
        fs.copyFileSync(path.join(__dirname, 'review-page.html'), path.join(out, 'index.html'));
    }

    /**
     * Prints nothing of its own; the list reporter does.
     *
     * @returns {boolean}
     */
    printsToStdio() {
        return false;
    }
}

module.exports = ReviewReporter;
