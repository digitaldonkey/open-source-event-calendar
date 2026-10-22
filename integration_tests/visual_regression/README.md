# Visual regression suite (Playwright)

Screenshots of the calendar, compared with a saved baseline set. A run generates its own test content, then shoots the
plugin's wp-admin screens and every calendar view in each calendar theme (320, 480 and 1280 px), plus print in
Chromium and Firefox. 58 tests, about 1.5 minutes. Runs only inside DDEV.

## Quick start

Inside the DDEV web container (`ddev claude`, `ddev ssh`):

```
cd integration_tests/visual_regression
WP_SANDBOX_DB=try_v1116 npm run test       # compare with the baseline set "local"
```

Then open the review page: `<site>/wp-content/uploads/osec-visual-regression/review/local/index.html`.

From the host, the DDEV commands do the same and print the URL: `ddev osec-review try_v1116`.

## Everyday tasks

### Run everything or a part

```
WP_SANDBOX_DB=try_v1116 npm run test                                       # everything
WP_SANDBOX_DB=try_v1116 npm run test -- --project=admin                    # only the wp-admin screens
WP_SANDBOX_DB=try_v1116 npm run test -- -g "320 px wide Month view"        # one view, both themes
WP_SANDBOX_DB=try_v1116 OSEC_THEMES=vortex npm run test -- print.spec.js   # one theme, one spec
npx playwright test --list --reporter=list                                 # all test names
```

- Arguments after `--` go to Playwright. `-g` matches the test titles joined by spaces.
- The content is generated first in every case.
- A failed comparison fails only its own test; the rest still runs.
- `npm run review` does the same and also adds a full-page screenshot of every test to the HTML report.

### Review and approve differences

The review page shows one row per screenshot: Baseline | Diff | Current, with the test's description below.

- Filters: failed, passed, approved, all; project, theme, width, search.
- Click an image for full size. ← → switch images, `s` toggles a slider, ↑ ↓ go to the previous/next row, Esc closes.
- Tick "approve" on the rows to accept. The bar at the top shows the command for all ticked rows:

```
ddev osec-approve --set=local chromium/plana/month-1280.png      # from the host
npm run approve -- --set=local chromium/plana/month-1280.png     # inside the container
```

Approving copies the reviewed image over the baseline without a new run. The old baseline goes to
`backups/approved/<date_time>/`.

### Accept the current look

```
WP_SANDBOX_DB=try_v1116 npm run update                       # re-shoot the whole set
WP_SANDBOX_DB=try_v1116 npm run update -- -g "Single event"  # re-shoot some baselines
```

`update` overwrites the set. Back it up first if you want to keep it (see "Backups").

## Which site

Use the sandbox `try_v1116`: a fresh WordPress with Twenty Twenty-Five and only this plugin. A copy of a used site
(e.g. the dev site) carries extra plugins and per-user settings, which show up as differences.

The site must hold no calendar data the generator did not create. The suite runs the generator with `--only-generated`,
which replaces only its own earlier content and refuses otherwise (`tests/data/content/README.md`).

## Settings

Environment variables:

- `WP_SANDBOX_DB`: the sandbox database. Unset: the DDEV dev site.
- `OSEC_BASELINE_SET`: the baseline set, default `local`. Use one set per state you want to compare.
- `OSEC_THEMES`: calendar themes, default `plana,vortex` (the default theme and the base theme).
- `OSEC_ANCHOR`: anchor date of the content. Pin it when comparing with a set shot on another day.
- `OSEC_MAX_DIFF_PIXELS`: pixels per shot that may differ, default `20`. `0` for exact. Measured 2026-10-20: three
  runs at `0` against an unchanged state differed by 0 px; the smallest real change seen was 186 px (icons). Measure
  again the same way (three runs with `0`) before changing it.
- `OSEC_REMOTE_URL`, `OSEC_REMOTE_DIR`: remote mode, see the next section.
- `PLAYWRIGHT_WS`: the browser server, default `ws://playwright:3000/`.

The DDEV commands take the same as options: `ddev osec-review try_v1116 --set=master --update`,
`--anchor=2027-05-10`; other arguments go to Playwright.

## Compare with an older release

Releases without `wp osec content generate` (1.1.x) run in another DDEV project with an imported content database.
The suite shoots that site in **remote mode**: login with a password, themes switched in the browser.

1. Build the content in a fresh `try_*` sandbox (`--site-settings` also sets the dataset's `/%postname%/` permalinks):
   `wp osec content generate --site-settings --anchor=2027-05-10`. Put the database dump, `uploads/osec-content/<db>/`,
   the manifest and the admin password into one folder, e.g. `uploads/osec-visual-regression/v1116/`.
2. In the other project: `ddev import-db`, unpack the uploads, `ddev wp search-replace <sandbox URL> <its URL>
   --all-tables`, `ddev wp rewrite flush --hard`.
3. Shoot it into its own set:

   ```
   WP_SANDBOX_DB=try_v1116 OSEC_REMOTE_URL=https://ddev-wordpress-integration-test.ddev.site \
     OSEC_REMOTE_DIR=/var/www/html/wp-content/uploads/osec-visual-regression/v1116 \
     OSEC_BASELINE_SET=1.1.16 npm run update
   ```

4. Compare the current code with it, with the **same anchor**:

   ```
   WP_SANDBOX_DB=try_v1116 OSEC_ANCHOR=2027-05-10 OSEC_BASELINE_SET=1.1.16 npm run review
   ddev osec-review try_v1116 --set=1.1.16 --anchor=2027-05-10      # the same from the host
   ```

The other project must run behind the same `ddev-router`.

## How it works

Projects, in this order (one worker):

1. `content` (`specs/content.setup.js`): generates the content and writes its manifest, logs in with cookies from
   WordPress (no password), and sets the events list's Screen Options (all columns, 5 per page, extended view).
2. `admin` (`specs/admin.spec.js`, Chromium, logged in): the wp-admin screens full page, and the calendar block's form
   in the block editor.
3. `chromium` (`specs/visual.spec.js`, `specs/print.spec.js`, logged out): the page's `<main>` for every view, and the
   print views full page with their PDF attached.
4. `firefox` (`specs/print.spec.js`): the print views, with the PDF from Firefox's own print.

The last three depend only on `content`, so one failure does not skip the others. Only one calendar theme can be
active, so the specs switch theme per block. A switch deletes saved Theme Options; the last theme stays active.

The browsers reach the sites through `ddev-router`, like a real browser (`lib/wp.js`). Baselines come only from the
DDEV `playwright` service, whose image version must match `@playwright/test` in `package.json`: another browser renders
fonts differently.

### What the suite changes before a shot

Nothing is hidden unless listed here:

- wp-admin: notices and the admin footer removed, the run's dates removed or blanked;
- the events list sorted by title, with the Screen Options above;
- views logged out, phone emulation below 800 px;
- animations frozen, text cursor hidden;
- the print button's `window.print()` stubbed;
- the DST day's "show full day" button and the block's settings toggle clicked.

Map tiles are compared as they load. When OpenStreetMap changes its tiles, the shot fails: review and approve it, rather than raising `OSEC_MAX_DIFF_PIXELS` or hiding the map.

## Output

Everything goes to `wp-content/uploads/osec-visual-regression/`, outside the repository and shared by the dev site
and all sandboxes:

```
osec-visual-regression/
├── baselines/<set>/<project>/[<theme>/]   the baseline screenshots
├── review/<set>/index.html                the review page of the last run against that set
├── report/<set>/index.html                Playwright's HTML report
├── test-results/<set>/                    actual and diff images of failed comparisons
└── backups/                               archived sets, approved baselines
```

### Backups

```
cd /var/www/html/wp-content/uploads/osec-visual-regression/baselines
tar czf ../backups/baselines-local-$(date +%Y-%m-%d_%H%M).tar.gz local       # back up the set "local"
rm -rf local && tar xzf ../backups/baselines-local-<date>.tar.gz              # restore it
```

## Add a screen

Add an entry to `specs/screens.js`: a calendar view (shot at every width of `WIDTHS`, per theme), a printed view, a
single event or page (by its manifest key), or a wp-admin screen. The specs loop over these lists. A screen that needs
clicks or its own setup (like the DST day or the block form) gets its own test in its spec. A new screen has no
baseline yet: shoot it with `npm run update` (or approve it from the review page).

## Files

```
visual_regression/
├── playwright.config.js     projects, threshold, output paths
├── eslint.config.js         code style (also used by bin/screenshots/)
├── specs/
│   ├── screens.js           the screens to shoot: add a screen here
│   ├── content.setup.js     content, login, Screen Options
│   ├── admin.spec.js        wp-admin screens
│   ├── visual.spec.js       calendar views, single events, block and shortcode pages
│   └── print.spec.js        print views and PDFs
└── lib/
    ├── wp.js                WP-CLI, site URL, browser connection
    ├── theme.js             themes of the run, switching
    ├── pages.js             manifest, URLs, waiting, printing, compareShot()
    ├── auth-cookies.php     login cookies (WP-CLI eval-file)
    ├── review-reporter.js   writes the review page
    ├── review-page.html     the review page template
    └── approve.js           npm run approve
```

DDEV commands: `bin/ddev/commands/web/osec-review`, `osec-approve` and `osec-screenshots`. DDEV reads commands only
from the project's `.ddev/`, so copy them once from the plugin root on the host:
`cp bin/ddev/commands/web/osec-* ../../../.ddev/commands/web/`.

## Code style

`npm run lint` checks, `npm run lint:fix` repairs what it can (`eslint.config.js`):

- 4 spaces, single quotes, semicolons, lines up to 120 characters;
- `{ a, b }` with inner spaces;
- literals with two or more entries: one entry per line, trailing comma;
- template literals instead of `+` on strings.
