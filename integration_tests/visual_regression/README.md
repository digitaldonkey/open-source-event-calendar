# Visual regression suite (Playwright)

Screenshots of the calendar compared with a baseline set, run locally in DDEV. One run generates the test content
(`wp osec content generate`), shoots the plugin's wp-admin screens, then every calendar theme in turn: agenda, week,
day and month at 320, 480 and 1280 px, the DST-end day, two single events, the block and shortcode pages, and print
(month, week, day) in Chromium and Firefox with the PDFs attached. About 1.5 minutes, 57 tests.

```
cd integration_tests/visual_regression          # inside the DDEV web container (ddev claude, ddev ssh)
WP_SANDBOX_DB=try_v1116 npm run test        # compare with the baseline set
WP_SANDBOX_DB=try_v1116 npm run review      # the same, plus a full-page screenshot of every test in the report
WP_SANDBOX_DB=try_v1116 npm run update      # shoot the baseline set (accept the current look)
```

From the host, with the DDEV command `osec-review` (review mode, prints the report URL):

```
ddev osec-review try_v1116                                   # everything, report with every screenshot
ddev osec-review try_v1116 --set=master --update             # shoot a named baseline set
ddev osec-review try_v1116 --set=master                      # compare with it
ddev osec-review try_v1116 --project=chromium -g "Month view"   # other arguments go to playwright test
```

Install it once from the plugin root on the host (DDEV reads commands only from the project's `.ddev/`):
`cp bin/ddev/commands/web/osec-review bin/ddev/commands/web/osec-approve bin/ddev/commands/web/osec-screenshots ../../../.ddev/commands/web/`. Shared
code stays in `bin/ddev/osec-lib.sh`. `npm install` in `integration_tests/` (npm workspaces root) runs on first use.

| Env | Meaning |
|---|---|
| `WP_SANDBOX_DB` | Sandbox database (plugin `CLAUDE.md`, "Database Safety"); unset: the DDEV dev site. WP-CLI and the browser use the same site. |
| `OSEC_BASELINE_SET` | Baseline set, default `local`. Shoot one per state to compare: `OSEC_BASELINE_SET=master npm run update`, switch the checkout, `OSEC_BASELINE_SET=master npm run test`. |
| `OSEC_THEMES` | Calendar themes, default `plana,vortex`: the default theme and the base theme the others build on. |
| `OSEC_ANCHOR` | Anchor date of the generated content (default: the generator's, the week with 15 May of next year). Pin it when comparing with a set shot earlier. |
| `OSEC_REMOTE_URL`, `OSEC_REMOTE_DIR` | Remote mode, see "Compare with an older release". |
| `OSEC_MAX_DIFF_RATIO` | Share of pixels that may differ before a comparison fails, default `0.001` (0.1 %: single-pixel noise from map tiles and font rendering passes, a moved or recolored element does not). `0` for exact. |
| `PLAYWRIGHT_WS` | Browser server, default `ws://playwright:3000/`. |

Every run shoots everything: a failed comparison fails only its own test, the rest still runs, and the report lists
every difference (`npm run review` also attaches a screenshot of every passing test). A pipeline that should stop at
the first difference adds `--max-failures=1`.

### Run part of it

The content is generated first in every case; npm passes arguments after `--` on to Playwright. `-g` matches the
test titles joined by spaces (`Calendar theme plana 320 px wide Month view`; `--list` shows them with `›`); every test also carries a description,
shown under its title in the report.

```
WP_SANDBOX_DB=try_v1116 npm run test -- --project=chromium -g "320 px wide Month view"     # one view, both themes
WP_SANDBOX_DB=try_v1116 OSEC_THEMES=vortex npm run test -- print.spec.js       # one theme, one spec, both browsers
WP_SANDBOX_DB=try_v1116 npm run test -- --project=admin                        # the wp-admin screens
WP_SANDBOX_DB=try_v1116 npm run update -- -g "Single event"                    # re-shoot only these baselines
npx playwright test --list --reporter=list                                  # all test names (a plain --list overwrites the report)
```

**Which site**: the current side runs on the sandbox `try_v1116` - a fresh WordPress (Twenty Twenty-Five, only this
plugin, default options), the same install whose database the 1.1.16 instance imported. A copy of a used site (the
dev site, `try_pw`) carries extra plugins and per-user settings that show up as differences (2026-10-20: the event
editor and categories differed only for that reason).

**The site must hold no calendar data the generator did not create**: it replaces only its own earlier content
(see `tests/data/content/README.md`). Use a sandbox when the site has own events.

## Compare with an older release

A release that has no `wp osec content generate` (1.1.x) runs in another DDEV project with an imported content
database; the suite shoots it in **remote mode** (no WP-CLI there: login with a password, themes switched through the
Calendar Themes page). Done for 1.1.16 on 2026-10-19:

1. A content database from a fresh WordPress (`/%postname%/` permalinks, Twenty Twenty-Five, only this plugin):
   `wp osec content generate --site-settings --anchor=2027-05-10` in a `try_*` sandbox, then the database dump, the
   `uploads/osec-content/<db>/` folder, the manifest and the admin password into one folder
   (`uploads/osec-visual-regression/v1116/`).
2. In the other project (running the old release): `ddev import-db`, unpack the uploads, `ddev wp search-replace
   <sandbox URL> <its URL> --all-tables`, `ddev wp rewrite flush --hard`.
3. Shoot it into its own set:

   ```
   WP_SANDBOX_DB=try_v1116 OSEC_REMOTE_URL=https://ddev-wordpress-integration-test.ddev.site \
     OSEC_REMOTE_DIR=/var/www/html/wp-content/uploads/osec-visual-regression/v1116 \
     OSEC_BASELINE_SET=1.1.16 npm run update
   ```

4. Compare the current code with it, **same anchor**:

   ```
   ddev osec-review try_v1116 --set=1.1.16 --anchor=2027-05-10                       # from the host
   WP_SANDBOX_DB=try_v1116 OSEC_ANCHOR=2027-05-10 OSEC_BASELINE_SET=1.1.16 npm run review   # inside the container
   ```

   Every failed test is a difference; its page in the report shows expected (1.1.16), actual and diff, with a slider.

`OSEC_REMOTE_URL` / `OSEC_REMOTE_DIR`: see `lib/wp.js`. The other project must run behind the same `ddev-router`.

### Review page and approving single screenshots

Every run also writes `uploads/osec-visual-regression/review/<set>/index.html` (`lib/review-reporter.js`,
`lib/review-page.html`): one row per screenshot with Baseline | Diff | Current (unchanged rows: Baseline | Current),
then description, reference and PDF links below; filters (failed / passed / approved / all, project, theme, width,
title search). Click an image
for full size: ← → switch Baseline / Diff / Current, `s` toggles the slider, ↑ ↓ go to the previous/next row the filters show, the
slider follows a click or drag on the image, Esc closes. Images are copied, so a review keeps showing its run.

Each row shows its **reference ID** - the baseline's path inside the set, e.g. `chromium/plana/month-1280.png`, the
same in every run - with copy buttons for the approve command. Approving copies the reviewed current image over the
baseline, without a new run (the previous one goes to `backups/approved/<date_time>/`):

```
ddev osec-approve --set=local chromium/plana/month-1280.png      # from the host
npm run approve -- --set=local chromium/plana/month-1280.png     # inside the container
```

Several references at once are fine. **On the page**, the "approve" checkbox of a differing row marks it "approval
pending" (kept for this browser window and run, in sessionStorage); the bar at the top then offers the ddev and npm
command for all checked rows. The command also marks the rows in the review's `data.js`: after a reload they show
"approved <time>" with the previous baseline and the approved image, under the "Approved" filter.

To re-shoot instead (a fresh run of only that test):
`npm run update -- --project=chromium -g "plana 1280 px wide Month view"`.

### Backups of baseline sets

`--update` overwrites a set. Archives of the sets lie in `uploads/osec-visual-regression/backups/`
(`baselines-<set>-<date>[_<time>].tar.gz`, plus the import folder; the ones from 2026-10-19 without a time still hold
gamma instead of vortex). Restore one:

```
cd /var/www/html/wp-content/uploads/osec-visual-regression/baselines
rm -rf 1.1.16 && tar xzf ../backups/baselines-1.1.16-2026-10-19_1528.tar.gz
```

Make a new one before re-shooting a set you want to keep:
`tar czf ../backups/baselines-<set>-$(date +%Y-%m-%d).tar.gz <set>` (from `baselines/`).

## Flow

Projects run in this order (`workers: 1`):

1. `content` (`content.setup.js`): `wp osec content generate --site-settings`, manifest to `.auth/manifest.json`, login
   cookies from WordPress (`lib/auth-cookies.php`, no password) saved as `.auth/admin.json`, then the events list's
   Screen Options through WordPress's panel: all columns, 5 per page, extended view (stored per user, so a used site
   and a fresh one would otherwise differ).
2. `admin` (`admin.spec.js`, Chromium, logged in): events list, edit event, feeds, settings, themes, categories (full
   page) and the calendar block's edit form in the block editor. Only decision N1's areas are hidden: notices and
   the admin footer (WordPress version) removed, dates of the run removed or blanked. Nothing else is hidden.
3. `chromium` (`visual.spec.js`, `print.spec.js`, logged out): per calendar theme, element shots of
   the page's `<main>` (the plugin's output with page title and theme margins, no theme header/footer; Twenty
   Twenty-Five has exactly one, no fallback for other themes), print views full page exactly as printed (the header carries the site URL); the PDF of
   each print view (`page.pdf()`) is attached to the report.
4. `firefox` (`print.spec.js`, logged out): the print views, each with its PDF from Firefox's own print: silent
   print-to-file in the playwright container (prefs in `lib/wp.js`), fetched as a download (`firefoxPdf()` in
   `lib/pages.js`). Print button only, browser default paper size.

The last three depend only on `content`, so a failed shot does not skip the others. Only one calendar theme can be
active, so the specs run per theme in blocks that activate their theme first (`lib/theme.js`). A theme switch deletes
saved Theme Options, and the last theme of the run stays active.

The browser reaches the sites through `ddev-router`, as a real browser does (TLS, `X-Forwarded-Proto`; wp-admin loops
without it): Chromium with `--host-resolver-rules` for the DDEV hostnames, Firefox with `network.dns.forceResolve` set
to the router (every hostname, so Firefox only shoots pages that load nothing from the internet). Both need the
service's `run-server --unsafe`. See `lib/wp.js`.

Views are shot logged out (no admin bar). Map tiles come from OpenStreetMap and are compared as they load; if they
change and make a single event fail, raise `OSEC_MAX_DIFF_RATIO` rather than hiding the map.

**What the suite changes before a shot, and nothing else** (maintainer 2026-10-19: no unasked hides): wp-admin notices,
footer and run dates (decision N1); the events list sorted by title, with its Screen Options set (all columns, 5 per
page, extended view); views logged out; phone emulation below 800 px;
Playwright's frozen animations and hidden text cursor; the print button's `window.print()` stubbed; the DST day's
"show full day" and the block's settings toggle clicked.

## Output (outside the repository)

| Path | Content |
|---|---|
| `wp-content/uploads/osec-visual-regression/baselines/<set>/<project>/[<theme>/]` | Baseline screenshots (`admin/`, `chromium/plana/`, `firefox/vortex/`, ...). |
| `wp-content/uploads/osec-visual-regression/report/<set>/` | HTML report of the last run against that set: `<site>/wp-content/uploads/osec-visual-regression/report/<set>/index.html` (e.g. `report/1.1.16/`). One per set, so a local run does not replace a comparison with a release. |
| `wp-content/uploads/osec-visual-regression/test-results/<set>/` | Actual and diff images of failed comparisons. |

Uploads are shared by the dev site and the sandboxes, so the report and the sets are too.

**Baselines come only from the DDEV `playwright` service** (`mcr.microsoft.com/playwright:v1.63.0-noble`, must match
`@playwright/test` in `package.json` exactly): another browser renders fonts differently, and every shot would differ.

## Files

| File | Role |
|---|---|
| `playwright.config.js` | Projects, connection, baseline and report paths. |
| `eslint.config.js` | Code style of the suite and `bin/screenshots/` (`npm run lint`, `npm run lint:fix`). |
| `content.setup.js` | Setup project `content` (see Flow). |
| `admin.spec.js`, `visual.spec.js`, `print.spec.js` | The checks. |
| `lib/wp.js` | WP-CLI against the site under test, site URL and uploads folder, browser connection through ddev-router. |
| `lib/theme.js` | Themes of the run, activating one. |
| `lib/pages.js` | Manifest, calendar URLs, waiting until a page is stable, printing through the print button. |
| `lib/auth-cookies.php` | Login cookies of the first administrator (WP-CLI `eval-file`). |
| `lib/review-reporter.js`, `lib/review-page.html` | The review page (reporter and page template). |
| `lib/approve.js` | Approves screenshots from a review page (`npm run approve`, `ddev osec-approve`). |

## Code style

Defined in `eslint.config.js` (ESLint + `@stylistic`), also used by `bin/screenshots/`. `npm run lint` checks,
`npm run lint:fix` repairs what it can:

- 4 spaces, single quotes, semicolons, lines up to 120 characters;
- `{ a, b }` with inner spaces (WordPress style);
- object and array literals with two or more entries: one entry per line, trailing comma;
- template literals instead of `+` on strings.

Long test descriptions may span lines inside their template literal: `about()` collapses the whitespace for the
report. The rules cover only these two folders; the plugin's other JS keeps its own style.
