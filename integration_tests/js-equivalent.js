/*
 * Proves that a hand edit of a JS bundle only renamed local names.
 *
 * Minifies each file at a git revision and in the working tree with terser
 * (mangle on, compress off, no comments). Mangling gives every local binding a
 * deterministic short name, so the two outputs are byte-identical exactly when
 * nothing but local names, formatting and comments changed.
 *
 * From the plugin root:
 *   node integration_tests/js-equivalent.js public/js/pages/calendar.js   # vs HEAD
 *   node integration_tests/js-equivalent.js --rev=master public/js/pages/*.js
 *
 * Paths are relative to the current directory (for `npm run js:equivalent`, the
 * directory npm was called from). Exit code 1 if any file differs.
 */
const {execFileSync} = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const {minify} = require('terser');

const root = path.resolve(__dirname, '..');
const cwd = process.env.INIT_CWD || process.cwd();
const args = process.argv.slice(2);
const rev = (args.find(a => a.startsWith('--rev=')) || '--rev=HEAD').slice(6);
const files = args.filter(a => !a.startsWith('--rev='));
const options = {compress: false, mangle: true, format: {comments: false}};

if (!files.length) {
    console.error('usage: node integration_tests/js-equivalent.js [--rev=<git rev>] <file>...');
    process.exit(2);
}

(async () => {
    for (const file of files) {
        const relative = path.relative(root, path.resolve(cwd, file));
        const before = execFileSync('git', ['show', `${rev}:${relative}`], {cwd: root, encoding: 'utf8', maxBuffer: 64 << 20});
        const after = fs.readFileSync(path.join(root, relative), 'utf8');
        const [a, b] = await Promise.all([minify(before, options), minify(after, options)]).then(r => r.map(x => x.code));
        if (a === b) {
            console.log(`identical   ${relative}`);
            continue;
        }
        let i = 0;
        while (a[i] === b[i]) i++;
        console.log(`DIFFERENT   ${relative} (at minified offset ${i})\n  ${rev}: ${a.slice(Math.max(0, i - 60), i + 60)}\n  now: ${b.slice(Math.max(0, i - 60), i + 60)}`);
        process.exitCode = 1;
    }
})();
