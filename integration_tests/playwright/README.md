# Visual suite (Playwright)

Screenshots of the calendar compared with a baseline set, run locally in DDEV. One run generates the test content
(`wp osec content generate`), then shoots every calendar theme in turn.

```
cd integration_tests/playwright          # inside the DDEV web container (ddev claude, ddev ssh)
WP_SANDBOX_DB=try_pw npm run test        # compare with the baseline set
WP_SANDBOX_DB=try_pw npm run review      # the same, plus a full-page screenshot of every test in the report
WP_SANDBOX_DB=try_pw npm run update      # shoot the baseline set (accept the current look)
```

From the host: `ddev exec -d /var/www/html/wp-content/plugins/open-source-event-calendar/integration_tests/playwright env WP_SANDBOX_DB=try_pw npm run test`.
`npm install` once in `integration_tests/` (npm workspaces root).

| Env | Meaning |
|---|---|
| `WP_SANDBOX_DB` | Sandbox database (plugin `CLAUDE.md`, "Database Safety"); unset: the DDEV dev site. WP-CLI and the browser use the same site. |
| `OSEC_BASELINE_SET` | Baseline set, default `local`. Shoot one per state to compare: `OSEC_BASELINE_SET=master npm run update`, switch the checkout, `OSEC_BASELINE_SET=master npm run test`. |
| `OSEC_THEMES` | Calendar themes, default `plana,gamma`. |
| `PLAYWRIGHT_WS` | Browser server, default `ws://playwright:3000/`. |

**The site must hold no calendar data the generator did not create**: it replaces only its own earlier content
(see `tests/data/content/README.md`). Use a sandbox when the site has own events.

## Flow

Projects run one after the other (`workers: 1`), chained by dependencies, since only one calendar theme can be active:

1. `content` (`content.setup.js`): `wp osec content generate --site-settings`, manifest to `.auth/manifest.json`, login
   cookies from WordPress (`lib/auth-cookies.php`, no password) saved as `.auth/admin.json`.
2. per theme `setup-<theme>` (`theme.setup.js`): `wp osec theme activate <theme>`, then one request compiles its CSS.
3. per theme `<theme>` (`visual.spec.js`): the screenshots, **logged out** (no admin bar), as visitors see the calendar.

A theme switch deletes saved Theme Options, and the last theme of the run stays active.

## Output (outside the repository)

| Path | Content |
|---|---|
| `wp-content/uploads/osec-playwright/baselines/<set>/<theme>/` | Baseline screenshots. |
| `wp-content/uploads/osec-playwright/report/` | HTML report of the last run: `<site>/wp-content/uploads/osec-playwright/report/index.html`. |
| `wp-content/uploads/osec-playwright/test-results/` | Actual and diff images of failed comparisons. |

Uploads are shared by the dev site and the sandboxes, so the report and the sets are too.

**Baselines come only from the DDEV `playwright` service** (`mcr.microsoft.com/playwright:v1.63.0-noble`, must match
`@playwright/test` in `package.json` exactly): another browser renders fonts differently, and every shot would differ.

## Files

| File | Role |
|---|---|
| `playwright.config.js` | Connection, theme chain, baseline and report paths. |
| `content.setup.js`, `theme.setup.js` | Setup projects (see Flow). |
| `visual.spec.js` | The checks per theme. |
| `lib/wp.js` | WP-CLI against the site under test, site URL and uploads folder, Chromium host rules from `DDEV_HOSTNAME`. |
| `lib/auth-cookies.php` | Login cookies of the first administrator (WP-CLI `eval-file`). |
