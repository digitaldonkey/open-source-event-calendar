---
paths:
  - "tests/**"
  - "integration_tests/**"
  - "phpunit.xml"
  - "grumphp.yml"
  - "bin/phpunit-sqlite.sh"
  - "bin/sqlite-dropin.sh"
  - "TESTING.md"
---

<!-- Loaded when working on tests. CLAUDE.md keeps the quick reference and the safety rules. -->

## Testing

See `TESTING.md` for the full checklist, one-time setup, integration-test prerequisites, and GrumPHP task inventory. Quick reference:

- **PHPUnit**: `ddev phpunit` (or `ddev phpunit --filter test_name ./tests/Unit/...`)
- **SQLite** (Playground / Live Preview): PHPUnit via `bin/phpunit-sqlite.sh` (CI `db_job_sqlite`; GrumPHP `all_tests`), the browser suites in CI `release_test_job_sqlite` (`release_test_job` with `db: sqlite`); both block the release. Driver pinned in `bin/sqlite-dropin.sh` (3.1.0, which no longer catches `IF()`, #44) - see `TESTING.md`
- **Integration (Mocha/Selenium)**: `cd integration_tests && npm run test`
- **Code quality**: `ddev composer run-script phpcs` or `ddev run-script phpcs`
- **WordPress plugin-check**: `bin/plugin-check.sh` (~13 s; ERROR fails, `--strict` also fails on WARNING). Deliberately **not** in `git_pre_commit` - it runs in `all_tests`, `prepare_release` and CI
- **GrumPHP**: `vendor/bin/grumphp run --testsuite=git_pre_commit` (what the pre-commit hook runs)
- **PHPUnit has its own file cache**: `tests/Utilities/bootstrap.php` sets `OSEC_FILE_CACHE_DEFAULT_PATH` to the test
  site's `wp-content/osec-phpunit-cache/` and empties it on start. Before 1.2.0 it emptied the dev site's plugin
  `cache/` (the test site shares the plugin folder) - on older branches, snapshot `cache/` around a run.
- **The compile lock commits the test transaction** (`ExecutionLimitController::acquire()`), like the feed import:
  CSS tests clean up after `parent::tear_down()` (`Tests\Utilities\CssEngineTrait::commit_css_cleanup()`), which
  also gives the engine switch (`use_css_engine()`), a web-request controller (PHPUnit is always CLI) and the next
  request's `init` (`css_next_request()`).
- **The Mocha/Selenium integration suite is destructive to the dev site.** It installs/uninstalls the plugin,
  exercises the `OSEC_UNINSTALL_PLUGIN_DATA` purge, creates its own `Calendar` page, and **trashes every
  calendar page on teardown** - including one you were using - leaving `calendar_page_id` pointing at a
  trashed post, i.e. the "no calendar page set" state described below. Ask before running it, and know the
  ways to trigger it *indirectly*:
  - `vendor/bin/grumphp run` with no `--testsuite` (the `integration_tests` task in `grumphp.yml`)
  - `vendor/bin/grumphp run --testsuite=all_tests`, and `ddev grumphp all`
  - Recovery: untrash the page, restore its slug and `publish` status, set `calendar_page_id` back to it, and
    reset the `CacheMemory` `calendar_base_page` - all four, or routing stays broken.
- When fixing bugs, add tests where appropriate
- **`phpcs.xml` excludes `/tests/`**, so GrumPHP and CI never lint test files. Passing a test file to
  `vendor/bin/phpcs` explicitly still checks it; worth doing for a new test, but a style slip there will not
  fail the build
- **Two DB traps in tests.** `$app->db->update()` does **not** add the table prefix (`insert()` and `delete()`
  do), so `update(OSEC_DB__EVENTS, …)` silently hits a missing table - pass `get_table_name()`.
  `ExecutionLimitController::acquire()` runs its own `COMMIT`, which ends the test framework's transaction, so
  anything a test writes before a feed import survives the rollback - clean up after `parent::tear_down()` and
  `COMMIT` (see `tests/Unit/App/Controller/FeedsControllerLockTest.php`)
- **No skipped tests**: `phpunit.xml` has `failOnSkipped`/`failOnRisky`/`failOnIncomplete` — "OK, but … skipped" exits 1 and fails CI. Check the exit code, don't use `markTestSkipped()` for multisite-only variants (CI is single site), and run CI's command `vendor/bin/phpunit tests` (see `TESTING.md`)
- **A calendar page must be set for any testing**: "no calendar page set" (`calendar_page_id` empty or pointing to a missing page) is a setup error, not a code bug. wp-admin shows the "installed, but has not been configured" notice (`EnvironmentCheck`). Don't fix or work around problems that only derive from that state; check the setup first when links, URLs or routing look wrong.
  - **PHPUnit**: `TestBase::set_up()` creates a published page per test and sets `calendar_page_id` plus the `CacheMemory` `calendar_base_page`. It can't be created once in the bootstrap, because the WP test lib's `_delete_all_data()` deletes all posts after each test class. Tests not extending `TestBase` must do the same.
  - **Dev site / Selenium**: verify `calendar_page_id` points to a published page before judging calendar output.

**Updating the PHPUnit version**: WordPress core's test framework only supports specific PHPUnit versions per WP release — before bumping the project's target WordPress or PHP version, check the [WP core PHPUnit compatibility chart](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/#supported-version-chart) and cross-check the candidate PHPUnit release on [Packagist](https://packagist.org/packages/phpunit/phpunit) against the project's PHP floor (8.2+). Update with `composer require --dev phpunit/phpunit:^<version>` and `composer require --dev yoast/phpunit-polyfills:^<version>` (polyfills bridge PHPUnit API differences so WP's test scaffolding keeps working across versions); `wp scaffold plugin-tests open-source-event-calendar` can regenerate the test bootstrap if it drifts. The currently pinned versions are always whatever's in `composer.json`/`composer.lock` — check there rather than assuming a version from memory.
