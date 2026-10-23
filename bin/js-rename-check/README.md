# js-rename-check

Proves that a hand edit of a JS bundle (`public/js/pages/*.js`, whose original build tooling is lost) only renamed
local names, changed formatting or comments.

```
cd bin/js-rename-check && npm install && cd ../..                            # once
node bin/js-rename-check/rename-check.js public/js/pages/calendar.js         # working tree vs HEAD
node bin/js-rename-check/rename-check.js --rev=master public/js/pages/*.js   # vs another revision
```

Or `npm run check -- <files>` from this folder (paths relative to where npm was called).

## How it works

Each file is minified twice with terser - at the git revision and in the working tree - with mangling on, compression
off and no comments. Mangling gives every local binding a deterministic short name, so the two outputs are
byte-identical exactly when nothing but local names, formatting and comments changed. Prints `identical` or
`DIFFERENT` with both outputs around the first difference; exit code 1 if any file differs.

## Files

| File | Role |
|---|---|
| `rename-check.js` | The check (was `integration_tests/js-equivalent.js`, then `bin/js-equivalent/`). |
| `package.json` | terser, pinned. |
