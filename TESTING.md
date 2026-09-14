# Testing

Everyday local dev-loop testing for this plugin. All commands run inside the DDEV web container (`ddev ssh` first, or run directly if already inside via `ddev claude`).

This doc covers the checks a developer runs while working on a change. The full CI matrix (PHP 8.2–8.5 × MariaDB/MySQL version matrix, generated-file freshness checks for `README.txt`/`hooks-and-filters.md`/the Twig frontend build) only runs on `master`/`release-*` branches — see `.circleci/config.yml`.

## Checklist

Run before pushing:

```
[ ] composer install                                                        # sync dev deps
[ ] vendor/bin/phpcs --standard=phpcs.xml --runtime-set testVersion 8.2-    # coding standards
[ ] vendor/bin/phpunit                                                      # unit + integration tests
[ ] cd integration_tests && SELENIUM_REMOTE_URL=http://selenium-chrome:4444/wd/hub npm run test  # Mocha/Selenium (~5 min)
[ ] vendor/bin/grumphp run --testsuite=all_tests                           # everything above, in one command
```

(The pre-commit hook runs `composer` + `phpcs` + `phpunit` via the `git_pre_commit` testsuite; see [GrumPHP](#grumphp-grumphpyml) below.)

One-time setup (first clone, or if the WP test DB was never initialized) — see below.

## First-time setup

```
ddev composer install
```

`bin/install-wp-tests.sh` (used below) needs a subversion client to fetch the WP core test scaffolding. DDEV already has this via `.ddev/config.yaml`:

```yaml
webimage_extra_packages: [subversion]
```

## One-time WP test-DB init

```
bin/install-wp-tests.sh phpunit root root db:3306
```

Run this once per environment. If you skip it, `vendor/bin/phpunit` fails immediately with a message pointing back at this script (`tests/Utilities/bootstrap.php` checks for `{WP_TESTS_DIR}/includes/functions.php` and aborts if it's missing).

**Note:** the test scaffolding (`$WP_TESTS_DIR`/`$WP_CORE_DIR`) is downloaded under `/tmp` by default, which does **not** persist across a container restart — but the `phpunit` MySQL database does. After a restart you'll have a `phpunit` DB with leftover plugin tables but no test scaffolding on disk. Re-running the command above regenerates the scaffolding; if it also prompts to recreate the DB (because it already exists), answering yes is safe — it's a disposable test fixture, not your dev data. To only refetch the scaffolding without touching an existing DB, pass `skip-database-creation=true` as the 6th arg:

```
bin/install-wp-tests.sh phpunit root root db:3306 latest true
```

**Older code only:** before the `DatabaseSchema::apply_delta()` fix, running `phpunit` against a DB whose plugin tables already match the current schema but whose `osec_db_version` option is missing/reset (e.g. after the scenario above) fails bootstrap with an uncaught `ErrorException` from `verifySqlSchema()`. If you hit that on an older checkout (e.g. a PR branch based on an older `master`), drop and recreate the `phpunit` DB so tables and option are created together from scratch.

## PHPUnit

```
ddev phpunit
# or, inside the container:
vendor/bin/phpunit

# single test:
ddev phpunit --filter test_get_cache_object ./tests/Unit/Cache/CachePathTest.php
```

Test suites (`phpunit.xml`):

| Suite | Path | Notes |
|---|---|---|
| `osec` | `./tests` | everything |
| `unit` | `tests/Unit` | bulk of coverage — `App/Model` (events, dates, ICS import/export), `Cache`, `View` |
| `integration` | `tests/Integration` | currently just `CachePerformanceTest.php` |

**No skipped tests.** `phpunit.xml` sets `failOnSkipped`, `failOnRisky`, `failOnIncomplete` and `failOnWarning`, so a run ending in "OK, but incomplete, skipped, or risky tests!" exits with code 1 and fails CI. Judge a run by its exit code, not by the summary line. Consequences:

- Don't use `markTestSkipped()` (or WP's `skipWithoutMultisite()` / `skipWithMultisite()`) for single-site vs. multisite variants. CI runs single site only, so a multisite-only test fails CI. Write tests that run in both modes instead, e.g. fake network values with filters like `pre_site_option_admin_email` (applied on single site too).
- Before pushing, run CI's exact command, which includes `tests/Integration`, and check both modes:
  ```
  vendor/bin/phpunit tests; echo "exit $?"
  WP_MULTISITE=1 vendor/bin/phpunit tests; echo "exit $?"
  ```

`tests/Utilities/bootstrap.php` reads `WP_TESTS_DIR` (falls back to `sys_get_temp_dir()/wordpress-tests-lib`), loads the plugin via WP's `muplugins_loaded` hook, and cleans the file-cache directories before handing off to WP core's own test bootstrap — this guards against leftover state if a previous run's `tearDown()` didn't fire.

## Code quality (phpcs)

```
composer run phpcs             # vendor/bin/phpcs --standard=phpcs.xml --runtime-set testVersion 8.2-
composer run phpcs-warnings    # non-blocking sniffs
composer run phpcbf            # auto-fix
```

`testVersion 8.2-` overrides the WP-version floor that `plugin-check.ruleset.xml` (from `WordPress/plugin-check`, refreshed via `bin/get-latest-plugin-review-phpcs-rulesets.sh`) would otherwise enforce.

## GrumPHP (`grumphp.yml`)

```
vendor/bin/grumphp run                              # every configured task (no --testsuite)
vendor/bin/grumphp run --testsuite=git_pre_commit   # what the pre-commit hook runs: composer + phpcs + phpunit
vendor/bin/grumphp run --testsuite=all_tests        # the full TESTING.md checklist in one command
vendor/bin/grumphp run --testsuite=prepare_release  # release-readiness checks, run after all_tests
```

### `ddev grumphp` shortcut

`bin/ddev/commands/web/grumphp` is a DDEV custom command that runs these from the host without the long `ddev exec -d ...` prefix (with colored output):

```
ddev grumphp                    # git_pre_commit testsuite
ddev grumphp all                # all_tests testsuite
ddev grumphp release            # prepare_release testsuite
ddev grumphp run --tasks=phpcs  # anything else is passed to grumphp as-is
```

DDEV only reads custom commands from the project's `.ddev/` directory, so install it once from the plugin root (on the host), assuming the DDEV project root is the WordPress root:

```
cp bin/ddev/commands/web/grumphp ../../../.ddev/commands/web/
```

The command expects the plugin at `/var/www/html/wp-content/plugins/open-source-event-calendar` inside the container.

Unlike the git hook, which only checks staged files, these commands check the whole project.

Task inventory as currently configured:

- **Active**: `composer` (validates `composer.json`/`composer.lock`), `phpcs` (standard `./phpcs.xml`), `phpunit` (config file `./phpunit.xml`, `always_execute: true` so it runs regardless of which files changed), `integration_tests` (a named `shell` task instance — `metadata.task: shell` — running `cd integration_tests && SELENIUM_REMOTE_URL=http://selenium-chrome:4444/wd/hub npm run test`; needs the `ddev-selenium-standalone-chrome` add-on and `constants-local.php`, see [Integration tests](#integration-tests-mochaselenium) above)
- **Present but commented out** (not run): `gherkin`, `git_commit_message`, `phpcpd` (would exclude `lib`/`tests`/`vendor`), `phplint`, `phpmd` (ruleset `codesize, design, naming, unusedcode`, would exclude `tests`/`vendor`)

Testsuites:
- `git_pre_commit` — matched by name to GrumPHP's git hook (`PreCommitCommand` looks up a testsuite literally named `git_pre_commit`), so this is what actually runs on every commit: `composer`, `phpcs`, `phpunit`. Without this testsuite the hook would run every configured task, including `integration_tests` and the release checks. CI's "Run code quality tests" step deliberately does not use this testsuite (it runs `--tasks=composer,phpcs`, as that job has no database for `phpunit`).
- `all_tests` — not hook-bound, for running everything on demand: `composer`, `phpcs`, `phpunit`, `integration_tests`.
- `prepare_release` — `release_check` (`wp osec prepare_release`), `make_readme` (`wp osec make_readme --check`), `hooks_and_filters` (`hookster_markdown` check). Run after `all_tests`.

The git pre-commit hook already runs inside DDEV — `git_hook_variables.EXEC_GRUMPHP_COMMAND` wraps it in `ddev exec -d "/var/www/html/wp-content/plugins/open-source-event-calendar"`. No extra setup needed; only reinit (`ddev exec grumphp git:init`) if the hook itself isn't installed.

## Integration tests (Mocha/Selenium)

```
cd integration_tests
npm install
cp settings.local.js.example settings.local.js   # if not already present
SELENIUM_REMOTE_URL=http://selenium-chrome:4444/wd/hub npm run test   # see Prerequisites below — plain `npm run test` tries to spawn a local chromedriver and fails
```

Edit `settings.local.js` to point `domain` at your local DDEV URL (e.g. `https://ddev-wordpress.ddev.site`) — it's gitignored, for your personal local values.

**Not a per-key overlay** — `page_objects/BasePage.js`'s `build()` picks one file or the other, not both:
```js
if (fs.existsSync(__dirname + '/../settings.local.js')) {
    settings = require('../settings.local.js');
} else {
    settings = require('../settings.js');
}
```
If `settings.local.js` exists, it **wholesale replaces** `settings.js` — every key must be present in it, not just the ones you're changing. `settings.local.js.example` already carries a full copy of every key for this reason; keep it that way when editing rather than trimming it down to just the values you want to change, or the missing keys become `undefined` (not inherited from `settings.js`).

Prerequisites:
- A Chrome browser + matching chromedriver. Two ways to get one:
  - **Inside DDEV (recommended)**: install the [`ddev/ddev-selenium-standalone-chrome`](https://github.com/ddev/ddev-selenium-standalone-chrome) add-on (`ddev get ddev/ddev-selenium-standalone-chrome && ddev restart`). This adds a `selenium-chrome` service reachable at `http://selenium-chrome:4444/wd/hub` from other DDEV containers, including the web container. `page_objects/BasePage.js` builds its WebDriver with plain `new Builder().forBrowser(Browser.CHROME)...build()` (no `.usingServer()`), so `selenium-webdriver` picks up the `SELENIUM_REMOTE_URL` env var automatically — no code changes needed:
    ```
    cd integration_tests
    SELENIUM_REMOTE_URL=http://selenium-chrome:4444/wd/hub npm run test
    ```
    This lets the whole suite run from inside the web container, e.g. under `ddev claude`.
  - **From the host**: a local Chrome + matching chromedriver, running the suite from outside the container against the DDEV site (the original approach, still works if you don't want the add-on).
- `constants-local.php` must define `OSEC_UNINSTALL_PLUGIN_DATA` as `true`, and `FS_METHOD` should be `direct` — specs repeatedly deactivate/reactivate the plugin and assert on a clean, purged install each time. This file is gitignored and **not present by default** — copy `constants-local.php.example` and add both overrides (the example only ships `OSEC_UNINSTALL_PLUGIN_DATA` set to `FALSE`).
- Node version per `integration_tests/.nvmrc`.

This suite runs directly against your working repo checkout — no need to build a release zip first. It's the same suite CI runs (against a packaged release build) as its release test.

A full run takes ~5 minutes (21+ specs across 4 themes: `gamma`, `plana`, `umbra`, `vortex`). Run it in the background rather than blocking on it — e.g. `npm run test &`, or when driving it from an agent/non-interactive shell, backgrounded with output captured to a file and polled/tailed rather than awaited synchronously.

**Running against one theme only**: `settings.local.js`'s `currentThemeOnly: true` (default is `false`, i.e. all 4 themes) makes `test/03_OsecPluginFrontend.spec.js`'s `before` hook read whichever theme is *currently active* in wp-admin's OSEC theme settings page (`page_objects/ActivatePluginAndSettings.js`'s `getActiveTheme()`) and skip the other three theme blocks (`this.skip()`). Only the theme-looped Frontend suite is affected — "Plugin install & Setup" and "Backend tests" aren't per-theme and always run once. Set your active theme in wp-admin first, then flip the setting; this cuts the Frontend portion to ~1/4 its normal length.

**Running a single test**: append `--grep "<test or describe title>"` to a direct `npx mocha` invocation, e.g.:
```
SELENIUM_REMOTE_URL=http://selenium-chrome:4444/wd/hub npx mocha test/02_OsecPluginBackend.spec.js --grep "View osec cache settings" --timeout 60000
```
Confirmed working (2026-09-11): 1 passing in 3s vs. the full suite's ~5 minutes. `--grep` matches against the full nested title (parent `describe` blocks joined with the `it` title), so for a per-theme Frontend test combine it with `currentThemeOnly` (or a distinctive enough substring) to avoid matching the same test name across multiple themes.

**Don't drive this suite through PhpStorm's `execute_run_configuration` MCP tool** — tested repeatedly on 2026-09-11; root cause now confirmed: the call returns early/asynchronously (sometimes immediately with a timeout, sometimes with truncated in-progress output) while the actual npm/mocha process keeps running **detached** on the host afterward. There's no way to block on or be notified of completion through that tool — the only way to see the real outcome is to separately poll the `fullOutputPath` log file it returns, which itself requires host filesystem access outside the project (`read_file` refuses it as outside project/library/SDK roots). A plain shell invocation (as above), run in the foreground or backgrounded and awaited/polled directly, is the only reliable way to get pass/fail results back.

**Known flakiness**: the `afterEach` hook in `test/01_OsecPluginInstall.spec.js` (`doLogout()` in `page_objects/WpLogin.js`, which hovers the admin bar then clicks Log Out) can intermittently hit a `TimeoutError: Waiting until element is visible` right after the very first plugin activation in a run — most likely first-page-load timing rather than a real regression. It hasn't reproduced on a re-run in practice; if it fails, re-run just that spec (`npx mocha test/01_OsecPluginInstall.spec.js --timeout 60000`) before assuming a real break.

## Manual/UI feed testing

`tests/Unit/App/Model/ical_feeds/*.ics` doubles as sample feed data — subscribe to it from a real calendar client (Google Calendar, Apple Calendar, etc.) to eyeball feed output end-to-end:

```
https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/google_cycling_halifax.ics
```

The same fixtures back `tests/Unit/App/Model/IcsImportExportParserTest.php` and `IcsImportExportParserFullyFledgedEventTest.php`, which exercise `IcsImportExportParser->add_vcalendar_events_to_db(Vcalendar $v, array $args)`.
