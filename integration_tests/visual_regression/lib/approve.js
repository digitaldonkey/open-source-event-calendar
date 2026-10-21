/**
 * Approves screenshots from a review page: copies the reviewed current image over the baseline, without a new run.
 *
 *   npm run approve -- [--set=<set>] <ref> [<ref> ...]          (in integration_tests/visual_regression/)
 *   ddev osec-approve [--set=<set>] <ref> [<ref> ...]           (from the host)
 *
 * <ref> is the reference ID the review page shows per row: the baseline's path inside its set,
 * e.g. chromium/plana/month-1280.png. --set defaults to OSEC_BASELINE_SET, then "local".
 *
 * Reads review/<set>/data.js (written by lib/review-reporter.js) under the uploads folder of the site in
 * WP_SANDBOX_DB (lib/wp.js); the previous baseline is copied to backups/approved/<date_time>/<set>/<ref> first. The
 * row is marked approved in data.js (approvedAt), so the review page shows it after a reload.
 * Exit code 1 when a reference is unknown or its row has no current image.
 */
const fs = require('fs');
const path = require('path');
const { site } = require('./wp');

const args = process.argv.slice(2);
const set = ((args.find((a) => a.startsWith('--set=')) || '').slice(6) || process.env.OSEC_BASELINE_SET || 'local')
    .replace(/[^a-z0-9._-]/gi, '').replace(/^\.+/, '');
const refs = args.filter((a) => !a.startsWith('--'));
const root = path.join(site().uploadsDir, 'osec-visual-regression');
const reviewDir = path.join(root, 'review', set);
const dataFile = path.join(reviewDir, 'data.js');

if (!refs.length) {
    console.error('Usage: npm run approve -- [--set=<set>] <ref> [<ref> ...]   (refs: see the review page)');
    process.exit(1);
}
if (!fs.existsSync(dataFile)) {
    console.error(`No review page for set "${set}" (${dataFile}). Run the comparison first.`);
    process.exit(1);
}
const data = JSON.parse(fs.readFileSync(dataFile, 'utf8').replace(/^window\.OSEC_REVIEW = /, '').replace(/;\s*$/, ''));
const stamp = new Date().toISOString().slice(0, 16).replace('T', '_').replace(':', '');
let failed = 0;
for (const ref of refs) {
    const row = data.rows.find((r) => r.ref === ref);
    if (!row || !row.current) {
        const reason = row
            ? 'no current image in the review (e.g. the test did not get to its shot)'
            : 'unknown reference';
        console.error(`${ref}: ${reason}`);
        failed++;
        continue;
    }
    const baseline = path.join(root, 'baselines', set, ref);
    if (fs.existsSync(baseline)) {
        const backup = path.join(root, 'backups', 'approved', stamp, set, ref);
        fs.mkdirSync(path.dirname(backup), { recursive: true });
        fs.copyFileSync(baseline, backup);
    }
    fs.mkdirSync(path.dirname(baseline), { recursive: true });
    fs.copyFileSync(path.join(reviewDir, row.current), baseline);
    // Mark it on the review page: after a reload the row shows "approved" with previous and new baseline.
    row.approvedAt = new Date().toISOString();
    console.log(`approved ${ref} (set ${set}, review of ${data.date})`);
}
fs.writeFileSync(dataFile, `window.OSEC_REVIEW = ${JSON.stringify(data, null, 1)};\n`);
process.exit(failed ? 1 : 0);
