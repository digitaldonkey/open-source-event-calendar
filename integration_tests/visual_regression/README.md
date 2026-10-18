# Visual regression suite (Playwright)

Screenshots of the calendar compared with a baseline set, run locally in DDEV. One run generates the test content
(`wp osec content generate`), shoots the plugin's wp-admin screens, then every calendar theme in turn: agenda, week,
day and month at 320, 480 and 1280 px, the DST-end day, two single events, the block and shortcode pages, and print
(month, week, day) in Chromium and Firefox. About 1.5 minutes, 57 tests.

```
cd integration_tests/visual_regression          # inside the DDEV web container (ddev claude, ddev ssh)
WP_SANDBOX_DB=try_pw npm run test        # compare with the baseline set
WP_SANDBOX_DB=try_pw npm run review      # the same, plus a full-page screenshot of every test in the report
WP_SANDBOX_DB=try_pw npm run update      # shoot the baseline set (accept the current look)
```

From the host, with the DDEV command `osec-review` (review mode, prints the report URL):

```
ddev osec-review try_pw                                   # everything, report with every screenshot
ddev osec-review try_pw --set=master --update             # shoot a named baseline set
ddev osec-review try_pw --set=master                      # compare with it
ddev osec-review try_pw --project=chromium -g "month"     # other arguments go to playwright test
```

Install it once from the plugin root on the host (DDEV reads commands only from the project's `.ddev/`):
`cp bin/ddev/commands/web/osec-review bin/ddev/commands/web/osec-screenshots ../../../.ddev/commands/web/`. Shared
code stays in `bin/ddev/osec-lib.sh`. `npm install` in `integration_tests/` (npm workspaces root) runs on first use.

| Env | Meaning |
|---|---|
| `WP_SANDBOX_DB` | Sandbox database (plugin `CLAUDE.md`, "Database Safety"); unset: the DDEV dev site. WP-CLI and the browser use the same site. |
| `OSEC_BASELINE_SET` | Baseline set, default `local`. Shoot one per state to compare: `OSEC_BASELINE_SET=master npm run update`, switch the checkout, `OSEC_BASELINE_SET=master npm run test`. |
| `OSEC_THEMES` | Calendar themes, default `plana,gamma`. |
| `PLAYWRIGHT_WS` | Browser server, default `ws://playwright:3000/`. |

Every run shoots everything: a failed comparison fails only its own test, the rest still runs, and the report lists
every difference (`npm run review` also attaches a screenshot of every passing test). A pipeline that should stop at
the first difference adds `--max-failures=1`.

### Run part of it

The content is generated first in every case; npm passes arguments after `--` on to Playwright.

```
WP_SANDBOX_DB=try_pw npm run test -- --project=chromium -g "320 px month"   # one view, both themes
WP_SANDBOX_DB=try_pw OSEC_THEMES=gamma npm run test -- print.spec.js        # one theme, one spec, both browsers
WP_SANDBOX_DB=try_pw npm run test -- --project=admin                        # the wp-admin screens
WP_SANDBOX_DB=try_pw npm run update -- -g "single event"                    # re-shoot only these baselines
npx playwright test --list                                                  # all test names
```

**The site must hold no calendar data the generator did not create**: it replaces only its own earlier content
(see `tests/data/content/README.md`). Use a sandbox when the site has own events.

## Flow

Projects run in this order (`workers: 1`):

1. `content` (`content.setup.js`): `wp osec content generate --site-settings`, manifest to `.auth/manifest.json`, login
   cookies from WordPress (`lib/auth-cookies.php`, no password) saved as `.auth/admin.json`.
2. `admin` (`admin.spec.js`, Chromium, logged in): events list, edit event, feeds, settings, themes, categories (full
   page) and the calendar block's edit form in the block editor. Notices, update counters and the admin footer are
   removed; dates of the run and the permalink are blanked in place.
3. `chromium` (`visual.spec.js`, `print.spec.js`, logged out): per calendar theme, element shots of
   `#osec-calendar-view` (or `.ai1ec-single-event`), print views full page with the printed URL masked; the PDF of
   each print view is attached to the report.
4. `firefox` (`print.spec.js`, logged out): the print views.

The last three depend only on `content`, so a failed shot does not skip the others. Only one calendar theme can be
active, so the specs run per theme in blocks that activate their theme first (`lib/theme.js`). A theme switch deletes
saved Theme Options, and the last theme of the run stays active.

The browser reaches the sites through `ddev-router`, as a real browser does (TLS, `X-Forwarded-Proto`; wp-admin loops
without it): Chromium with `--host-resolver-rules` for the DDEV hostnames, Firefox with `network.dns.forceResolve` set
to the router (every hostname, so Firefox only shoots pages that load nothing from the internet). Both need the
service's `run-server --unsafe`. See `lib/wp.js`.

Views are shot logged out (no admin bar). Map tiles come from OpenStreetMap and are compared as they load; if they
change and make a single event fail, mask the map.

## Output (outside the repository)

| Path | Content |
|---|---|
| `wp-content/uploads/osec-visual-regression/baselines/<set>/<project>/[<theme>/]` | Baseline screenshots (`admin/`, `chromium/plana/`, `firefox/gamma/`, ...). |
| `wp-content/uploads/osec-visual-regression/report/` | HTML report of the last run: `<site>/wp-content/uploads/osec-visual-regression/report/index.html`. |
| `wp-content/uploads/osec-visual-regression/test-results/` | Actual and diff images of failed comparisons. |

Uploads are shared by the dev site and the sandboxes, so the report and the sets are too.

**Baselines come only from the DDEV `playwright` service** (`mcr.microsoft.com/playwright:v1.63.0-noble`, must match
`@playwright/test` in `package.json` exactly): another browser renders fonts differently, and every shot would differ.

## Files

| File | Role |
|---|---|
| `playwright.config.js` | Projects, connection, baseline and report paths. |
| `content.setup.js` | Setup project `content` (see Flow). |
| `admin.spec.js`, `visual.spec.js`, `print.spec.js` | The checks. |
| `lib/wp.js` | WP-CLI against the site under test, site URL and uploads folder, browser connection through ddev-router. |
| `lib/theme.js` | Themes of the run, activating one. |
| `lib/pages.js` | Manifest, calendar URLs, waiting until a page is stable, printing through the print button. |
| `lib/auth-cookies.php` | Login cookies of the first administrator (WP-CLI `eval-file`). |
