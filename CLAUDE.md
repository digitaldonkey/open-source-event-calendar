# Development Environment

This project runs inside DDEV.

## Important

If Claude is not inside the DDEV container suggest to install
`ddev addon https://addons.ddev.com/addons/vanWittlaer/ddev-claude-code` and use `ddev claude`.

Always use the DDEV environment for application commands.

When Claude is running via `ddev claude`, Claude is already inside
the DDEV web container. Therefore, run application commands directly
inside the container rather than prefixing them with `ddev exec`.

Do NOT use the host's PHP, Composer, Node, npm, MySQL, or other
development tools when an equivalent tool is available inside DDEV.

Examples when running inside `ddev claude`:

- PHP: `php`
- Composer: `composer`
- Node: `node`
- npm: `npm`
- PHPUnit: `vendor/bin/phpunit`
- MySQL: `mysql`
- WP-CLI: **`/usr/local/bin/wp`, not a bare `wp`** - see below

**Always call WP-CLI by its absolute path `/usr/local/bin/wp`.** From inside the plugin
directory a bare `wp` resolves to the plugin's *own* composer-installed WP-CLI
(`vendor/bin/wp`), and under that binary **the plugin never bootstraps**: its entry point only
loads `vendor/autoload.php` and registers `BootstrapController::createApp()` on `init` when
`\Osec\App\Controller\BootstrapController` is *not* already declared
(`open-source-event-calendar.php`), but that vendor WP-CLI has already pulled in the plugin's
composer autoloader, so the class exists, the guard skips the whole block, and the `init` hook
is never added. WordPress loads, the plugin file is included, `wp plugin list` still says
*active* - yet `OSEC_VERSION` is undefined and `global $osec_app` is `null`, so any
`Something::factory($osec_app)` fatals with "Argument #1 ($app) must be of type
Osec\Bootstrap\App, null given". With `/usr/local/bin/wp` (DDEV's own) `$osec_app` is a proper
`Osec\Bootstrap\App` regardless of the current directory. `--path=/var/www/html` is unrelated
and does *not* fix it.

If you need to execute a command from the host, use the appropriate
DDEV command, for example:

- `ddev exec <command>`
- `ddev composer <command>`
- `ddev npm <command>`

Before changing DDEV configuration or infrastructure, inspect the
existing `.ddev` configuration first.

Never switch to the host PHP/Node/Composer environment simply because
a command is unavailable or fails inside DDEV. Investigate the DDEV
environment first.

## Database Safety

The DDEV database is a development database and usually contains
nothing important.

Any change to the dev database is allowed, including destructive ones
(altering settings/data, dropping, truncating, resetting). Ask once
per session, the first time a change is needed: "Can I drop (or
alter) the database?" After a yes, no further asking in that session.

When a DB change is the straightforward route, ask for it instead of
building workarounds to avoid the write (temporary mu-plugins, option
filters, cookie or query-param overrides).

Prefer read-only queries when investigating problems.

Do not access production databases.

## Project Context

- Fork of [all-in-one-event-calendar](https://github.com/wp-plugins/all-in-one-event-calendar)
- Some original JavaScript sources unavailable; legacy code and inconsistencies exist
- Updated to WordPress Plugin Standards, passes `plugin-check`
- Uses Composer for dependencies (vendor managed)
- Based on All-in-One Event Calendar **2.3.4**; all 236 classes rewritten using PHP namespaces + Composer for dependency management
- Repo: https://github.com/digitaldonkey/open-source-event-calendar
- CI: https://app.circleci.com/pipelines/github/digitaldonkey/open-source-event-calendar

## Code Style

- PHP 8.2+ (no strict typing - legacy code incompatible with strict_types)
- WordPress PHP Coding Standards (custom WordPress-PSR-12, see `phpcs.xml`)
- OOP preferred where it improves modularity (see `src/` structure)
- Minimal comments—only for non-obvious logic

## Project Architecture

| Directory | Purpose |
|-----------|---------|
| `open-source-event-calendar.php` | Plugin entry point |
| `src/` | Core OOP PHP code |
| `src/Bootstrap/` | Base classes with custom class loader, registers to central `App` instance |
| `public/` | Frontend assets and Twig templates |
| `public/osec_themes/` | Plugin-specific themes (based on `vortex/`) |
| `calendar_block/` | WordPress Gutenberg blocks |
| `tests/` | PHPUnit unit tests |
| `integration_tests/` | Mocha/Selenium automated browser tests |
| `languages/` | WordPress localization files |
| `vendor/` | Composer dependencies |
| `assets/` | WordPress.org plugin assets (screenshots, banners) |
| `bin/` | Developer scripts |
| `hookster_markdown/` | Subproject generating `hooks-and-filters.md` from PHPdoc |
| `twig_to_js_transform/` | Developer tool to convert Twig templates for frontend |
| `evaluate-history/` | Historical evaluation documents |
| `evaluate-react-big-calendar/` | React Big Calendar evaluation |

## Key Files

| File | Purpose |
|------|---------|
| `constants.php` | Plugin configuration constants |
| `composer.json` / `composer.lock` | PHP dependencies |
| `phpunit.xml` | PHPUnit configuration |
| `phpcs.xml` | PHP CodeSniffer rules (WordPress-PSR-12) |
| `hooks-and-filters.md` | Auto-generated hooks documentation |
| `CHANGELOG.md` | Version history |
| `.circleci/` | CI/CD pipeline configuration |
| `plugin-check.ruleset.xml` | WordPress plugin-check ruleset (refresh via `bin/get-latest-plugin-review-phpcs-rulesets.sh`) |

## Configuration

- Override `constants.php` locally by copying `constants-local.php.example` to `constants-local.php` (gitignored, auto-loaded if present)
- Debug mode: `define('OSEC_DEBUG', true)` in `constants-local.php` enables template-recompilation via the `?osec_recompile_templates=TRUE` query param

## PHP/WordPress Principles

- Write concise, technical responses with accurate PHP examples
- Use PHP 8.2+ features when appropriate (typed properties, readonly classes, match expressions, anonymous classes)
- Follow WordPress PHP Coding Standards from `phpcs.xml`
- Avoid `declare(strict_types=1);` - legacy code incompatible
- Utilize WordPress core functions and APIs when available
- Implement proper error handling:
  - Use WordPress debug logging (`WP_DEBUG_LOG`)
  - Try-catch blocks for expected exceptions
- Use WordPress's built-in functions for data validation and sanitization
- Implement proper nonce verification for all form submissions

## Database

- Use `$wpdb` abstraction layer exclusively
- Always use `$wpdb->prepare()` for dynamic queries
- Use `dbDelta()` for schema changes
- Use Transients API for caching

## Data Model: Events & Recurrence

- Events are posts (post type `osec_event`), ID-linked to the `wp_osec_events` table
- Recurring events follow the [RFC-5545 recurrence concept](https://devguide.calconnect.org/iCalendar-Topics/Recurrences/); instances live in `wp_osec_event_instances` (start/end as Unix timestamps + ID only)
- "Edit this instance" can fork a child event/post — see the "Base recurrence event" / "Modified recurrence events" editor tabs
- Debug helper — human-readable instance dates via SQL view:
  ```sql
  CREATE VIEW wp_osec_event_instances_readable_date AS
  SELECT id, post_id, `start`, DATE_FORMAT(FROM_UNIXTIME(`start`), '%Y-%m-%d %H:%i') AS 'start_formatted',
         `end`, DATE_FORMAT(FROM_UNIXTIME(`end`), '%Y-%m-%d %H:%i') AS 'end_formatted' FROM wp_osec_event_instances;
  ```
- Further reading: [wiki: Understanding data model](https://github.com/digitaldonkey/open-source-event-calendar/wiki/Understanding-data-modell)

## Feeds (iCalendar)

- RFC 5545 iCal feed support via `kigkonsult/icalcreator` + `rlanvin/php-rrule`
- **Manual/UI testing**: `tests/Unit/App/Model/ical_feeds/*.ics` doubles as sample feed data you can subscribe to from any real calendar client (Google Calendar, Apple Calendar, etc.) to eyeball feed output end-to-end, e.g. `https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/google_cycling_halifax.ics`
- **Automated/unit testing**: the same fixture files back `tests/Unit/App/Model/IcsImportExportParserTest.php`, which exercises `IcsImportExportParser->add_vcalendar_events_to_db(Vcalendar $v, array $args)` — the core ICS-import parsing logic

## Hooks & Extensions

- Use hooks (actions and filters) exclusively - never modify core/plugin files
- Hookster markdown documentation auto-generated from PHPdoc comments via `hookster_markdown/`
- Document hooks in PHPdoc for documentation generation

## Assets & JavaScript

- Use `wp_enqueue_script()` / `wp_enqueue_style()` exclusively
- REST API preferred over `admin-ajax.php`
- Gutenberg blocks in `calendar_block/` use WordPress scripts
- Legacy code may exist from original all-in-one-event-calendar

## Frontend CSS Delivery

The compiled theme CSS reaches the page in one of **four** ways, decided per request by
`FrontendCssController::get_css_url()` (`src/App/Controller/FrontendCssController.php`). Which one is active
changes *where in the DOM the stylesheet lives*, so never assume it is a `<link>` in `<head>`:

| Condition | Result | Where |
|---|---|---|
| `OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST` (debug constant, usually in `constants-local.php`) | `echo_css()` on `wp_head` | inline `<style id="osec-frontend-css-inline-css">` **in `<body>`** |
| Option `osec_compiled.css` (`COMPILED_CSS_KEY`) is a string | that URL | `<link>` in `<head>` |
| Option is numeric **and** setting `render_css_as_link` is on (default) | site URL with `?osec-css-cache=<timestamp>` | `<link>` in `<head>` |
| Option is numeric and `render_css_as_link` is off | `echo_css()` on `wp_head` | inline `<style>` **in `<body>`** |
| Option is `null` (new install) | `<theme_url>/css/osec_parsed.css` | `<link>` in `<head>`, and the file is usually absent - see `.claude/plans/less-sha1-map-and-precompiled-css.md` |

**Why the inline variant lands in the body:** `echo_css()` does not echo. It is hooked to `wp_head` but calls
`wp_register_style()` + `wp_add_inline_style()` + `wp_enqueue_style()`, and by then `wp_print_styles` has already
run for the head, so WordPress prints the handle with the footer styles - as a direct child of `<body>`, carrying
the whole compiled stylesheet (~390 KB).

**Consequences to keep in mind:**

- **Any JS that clears, replaces or detaches the body can destroy the calendar's styling.** This is what made the
  print button produce an unstyled page (`handle_click_on_print_button` in `public/js/pages/calendar.js`; fixed by
  detaching everything *except* `style, link, script, noscript, template`). Exclude non-rendered elements, or work
  on a container instead of `<body>`.
- **A local `OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST` flips the dev site to the inline variant**, so behaviour
  differs from a default production site. When a CSS-related bug reproduces in one place and not the other, check
  this first: `curl -s <url> | grep -c 'id="osec-frontend-css-inline-css"'` (1 = inline in body, 0 = link in head;
  grep without the `id=` also matches the comment WordPress appends after the style, so it counts 2).
- That constant also makes `tests/Unit/ConstantsTest.php::test_is_less_debug_disabled` fail locally. Expected;
  `constants-local.php` is gitignored, CI is unaffected.

## CSS Compile Caching (Dev Staleness)

**If a LESS/theme-CSS edit doesn't seem to take effect, it is essentially never PHP opcache** -
`.less` files are read as plain text (`file_get_contents()`), not compiled PHP, so opcache
cannot cache them. Two different caches actually sit between a LESS edit and the browser:

1. **`FrontendCssController::get_compiled_css()` has `static $recompiledCss = null;`.** A PHP
   `static` local variable is scoped to the **process**, not the request - a php-fpm worker is
   reused across many requests, so once one worker computes this, that worker returns the same
   value forever (ignoring source changes and cache-busting) until it recycles.
2. **`CacheFactory::createCache()` tries three engines in order** (`src/Cache/CacheFactory.php`):
   `CacheApcu` → `CacheFile` → `CacheDb` (DB-option-backed), first one available/enabled wins.
   With `OSEC_ENABLE_CACHE_APCU` at its default `true`, `CacheApcu` always wins first, so
   compiled CSS lives in APCu's shared memory - which every worker in the pool shares, and
   which (like the static above) only clears on a full php-fpm restart, not per-request or via
   the `?osec-css-cache=` cache-bust.

**For local dev/debugging, disable APCu via `constants-local.php`** (gitignored - copy from
`constants-local.php.example`, never edit the tracked `constants.php` for this):
```php
if (!defined('OSEC_ENABLE_CACHE_APCU')) {
    define('OSEC_ENABLE_CACHE_APCU', FALSE);
}
```
With APCu out of the way, `CacheFactory` falls through to `CacheFile` (`OSEC_ENABLE_CACHE_FILE`
is already `true` by default), so `FrontendCssController::update_persistence_layer($css)` - the
save step run once right after a successful compile, inside `get_compiled_css()`'s cache-miss
branch - takes the `CacheFile` path instead of the generic one:

- **File cache** (`$this->cache->is_file_cache()` true): `CacheFile::setWithFileInfo()` writes
  the CSS to `cache/css/<prefix>_osec_compiled.css`. The `<prefix>` is
  `substr(md5(site_url()), 0, 8)` - constant per site, **not a content hash** - so the same file
  is overwritten in place on every recompile; diff it directly to see what actually compiled,
  no request round-trip or cache-timing guesswork. The returned file URL (a string) is written
  into the plain `osec_compiled.css` WP option via `store_css_cache()`. From then on
  `get_css_url()` (a separate code path, consulted on every page `<head>`) sees a string and
  links straight to that static file - nginx serves it, no PHP involved, no second request
  needed once it exists.
- **Any other engine (APCu/DB)**: `$this->cache->set()` stores the CSS *inside that engine*,
  but `store_css_cache(time())` writes a **timestamp**, not the CSS, into the same
  `osec_compiled.css` option. `get_css_url()` then sees a number and can only embed
  `<link href="?osec-css-cache=<timestamp>">` - a URL that routes back through WordPress
  (`render_css()` → `get_compiled_css()` again) rather than ever containing CSS inline. This is
  *why* a second request is structurally required for non-file caches, not a bug: the page HTML
  can never carry the actual CSS in this branch, only a pointer back to WordPress.
- If the file write itself fails (`CacheWriteException` from a permissions/disk issue),
  `update_persistence_layer()` doesn't catch it - it propagates to `get_compiled_css()`'s outer
  catch, which notifies an admin (unless already in per-request-recompile debug mode) but still
  returns the freshly-compiled CSS for the current request. Degraded (recompiles every request
  until fixed) but never broken.

**Reliable verification loop after any LESS/PHP change affecting compiled CSS:**
1. `supervisorctl restart php-fpm` (inside the container) - clears both the per-worker `static`
   and APCu's shared memory in one step; a `wp eval 'opcache_reset();'` does **not** do this,
   since that runs in its own throwaway CLI process, not the FPM pool serving real requests.
2. **Delete `cache/css/*_osec_compiled.css`, then** hit `?osec-css-cache=<timestamp>`. Deleting
   the file is the part that actually forces the recompile: in file-cache mode the route still
   goes through `get_compiled_css()`, whose cache lookup finds that file and returns it, so a
   LESS edit can sit unreflected through any number of cache-bust requests *and* a php-fpm
   restart. Pair it with `wp option update osec_compiled.css "$(date +%s)"` - the option holds
   the file's URL once written, and `null` is worse than a number here, since that is the
   "new install" branch (see the table above), which links a static `osec_parsed.css` that
   usually does not exist and never triggers a compile at all.
3. A LESS→CSS compile can need a second request to be fully reflected (per maintainer note) -
   don't conclude a change "didn't take" from a single request's response.
4. With APCu disabled per above, read the actual file in `cache/css/` rather than re-parsing
   HTML output.

## Base Font Size (Theme Setting)

The "Base font size" option (Calendar Theme Options) compiles to `@font-size-base`
(`@baseFontSize`, set in `public/osec_themes/vortex/less/user_variables.php` /
`user-variable-map.less`) and is applied as a literal, LESS-compiled px value on the
plugin's own wrapper element (`.timely`, via `bootstrap/scaffolding.less` imported inside
`.timely { }` in `style.less`) - not on `<html>`.

**Each theme ships its own default for it** (`user_variables.php`: vortex `13px`, plana `1rem`,
umbra `0.8rem`), so *switching theme changes the base font size* unless a value is saved in
`osec_less_variables`. Rendered box heights then shift - don't read that as a LESS edit having
broken something. **Check the base font size after every theme switch**, and when comparing two
themes, pin them to the same value so only one variable moves
(`integration_tests/responsive_compare/responsive-compare.sh` takes `BASE_FONT=13px` for this
and prints the effective value after each switch).

Note that switching theme *deletes* `osec_less_variables`, discarding any tuned theme options -
capture and restore them around anything that switches themes.

**All font sizes in the plugin's LESS/CSS must be relative to that wrapper, using `em` or
`%` - never `rem`, and never a hardcoded `px` value.** `rem` is always relative to the root
`<html>` element's font-size, which belongs to the surrounding theme/site, not to
`.timely`. A hardcoded `px` value doesn't move with the setting at all. Either one silently
breaks "Base font size" for that element.

Note: `@font-size-base * <factor>` is a LESS-side exception, not a third unit - it still
compiles down to a `px` value in the output CSS, but that value is derived from the setting
at compile time, so it moves when the setting changes. Use it in LESS source for a
size that should track the base but not equal it; the rule above is about hand-written
`px`/`rem` literals in the CSS.

**Exception: print styles** (`@media print`, `.osec-print-calendar()`, `.ai1ec-print` in
`calendar.less`) may use fixed `px`/`pt` sizes - physical paper output isn't meant to track
a screen font-size setting.

When adding or reviewing CSS/LESS outside print styles, check every `font-size:` for a bare
`px`/`rem` value.

**Never add two LESS values of different units.** `less.php` keeps the *first* operand's unit
and silently discards the second's, with no warning: `@a: .5em; @b: 1px; (@a + @b)` compiles to
`1.5em`, not `calc(.5em + 1px)`. Mixing `em` with `px` is easy to hit here precisely because the
rule above pushes everything towards `em` while borders and hairlines stay `px`. Use
`calc( ~"@{a} + @{b}" )` (the `~""` escape keeps LESS from evaluating it) and check the compiled
output in `cache/css/*_osec_compiled.css` - a plausible-looking wrong number is the normal
failure mode, not a compile error.

## Twig → JS Frontend Templates

- Three Twig templates double as the **frontend-rendering** (client-side JS) templates: `public/osec_themes/vortex/twig/{agenda,oneday,month}.twig`. Frontend rendering only applies when the OSEC Settings option `use_frontend_rendering` is enabled — otherwise only their backend (PHP) rendering matters.
- **If you edit `agenda.twig`, `oneday.twig`, or `month.twig`, you must re-run the transform**, or the JS-side templates silently go stale:
  ```bash
  cd twig_to_js_transform
  nvm use
  npm install
  npm run build-twig-frontend
  ```
  (Note: `twig_to_js_transform/readme.md` says `npm run transform` — that script doesn't exist in `package.json`; only `build-twig-frontend` does. Use the command above.)
- What it does: compiles each `.twig` file to twig-js, then splices the compiled output into `public/js/{agenda,oneday,month}.js` **and** `public/js/pages/calendar.js` (which carries its own bundled copy inherited from the original vendor's unknown build tooling) — replacing only the content between matching `/*REPLACE:<template>.twig*/` marker comments in each destination file.
- **Never upgrade the `twig` npm package past `^0.7.2`** — pinned to stay compatible with legacy ai1ec-derived code.
- No original build tooling exists for these templates (this script is a workaround). If you'd rather not deal with it after a Twig edit, `use_frontend_rendering` can be turned off in OSEC Settings so only backend rendering is used.

## Security

- Escape all output: `esc_html()`, `esc_attr()`, `wp_kses_post()`, etc.
- Sanitize all input: `sanitize_text_field()`, `sanitize_email()`, etc.
- Nonce verification on all form submissions: `check_admin_referer()`, `verify_nonce()`
- Use WordPress's built-in user roles and capabilities system

## Internationalization

- Implement i18n using WordPress functions: `__()`, `_e()`, `esc_html__()`, etc.
- Translation files in `languages/` directory
- All user-facing strings must be translatable

## Commits

- **The maintainer commits and pushes.** Default workflow: prepare the changes, run the checks, report what changed and let the maintainer commit. Commit only when asked to in that session (as during the print work), and never push.
- **Commit identity is `digitaldonkey <tho@donkeymedia.eu>`**, set repo-locally in `.git/config`. The container's `~/.gitconfig` says `DDEV User <nobody@example.com>`, which is what commits get if the local setting is missing. Before committing, check `git var GIT_AUTHOR_IDENT`; if it isn't that identity, stop and tell the maintainer instead of committing.
- **GrumPHP hooks do not run inside the container.** `.git/hooks/pre-commit` and `commit-msg` call `ddev exec`, and `/usr/local/bin/ddev` in the web container is a stub that prints a hint and exits 0. So commits made from `ddev claude` silently skip phpcs and the other GrumPHP tasks — the hook is there for the maintainer on the host.
- **Therefore run the checks manually**, before committing and before handing work back:
  - `vendor/bin/phpunit tests` and check the exit code (see the no-skipped-tests rule below)
  - `vendor/bin/phpcs --standard=phpcs.xml <changed paths>`
  - after editing `agenda.twig`, `oneday.twig` or `month.twig`: re-run the twig→JS transform
  - or `vendor/bin/grumphp run` for everything at once

## Testing

See `TESTING.md` for the full checklist, one-time setup, integration-test prerequisites, and GrumPHP task inventory. Quick reference:

- **PHPUnit**: `ddev phpunit` (or `ddev phpunit --filter test_name ./tests/Unit/...`)
- **Integration (Mocha/Selenium)**: `cd integration_tests && npm run test`
- **Code quality**: `ddev composer run-script phpcs` or `ddev run-script phpcs`
- **GrumPHP**: `vendor/bin/grumphp run` (pre-commit hooks)
- When fixing bugs, add tests where appropriate
- **No skipped tests**: `phpunit.xml` has `failOnSkipped`/`failOnRisky`/`failOnIncomplete` — "OK, but … skipped" exits 1 and fails CI. Check the exit code, don't use `markTestSkipped()` for multisite-only variants (CI is single site), and run CI's command `vendor/bin/phpunit tests` (see `TESTING.md`)
- **A calendar page must be set for any testing**: "no calendar page set" (`calendar_page_id` empty or pointing to a missing page) is a setup error, not a code bug. wp-admin shows the "installed, but has not been configured" notice (`EnvironmentCheck`). Don't fix or work around problems that only derive from that state; check the setup first when links, URLs or routing look wrong.
  - **PHPUnit**: `TestBase::set_up()` creates a published page per test and sets `calendar_page_id` plus the `CacheMemory` `calendar_base_page`. It can't be created once in the bootstrap, because the WP test lib's `_delete_all_data()` deletes all posts after each test class. Tests not extending `TestBase` must do the same.
  - **Dev site / Selenium**: verify `calendar_page_id` points to a published page before judging calendar output.

**Updating the PHPUnit version**: WordPress core's test framework only supports specific PHPUnit versions per WP release — before bumping the project's target WordPress or PHP version, check the [WP core PHPUnit compatibility chart](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/#supported-version-chart) and cross-check the candidate PHPUnit release on [Packagist](https://packagist.org/packages/phpunit/phpunit) against the project's PHP floor (8.2+). Update with `composer require --dev phpunit/phpunit:^<version>` and `composer require --dev yoast/phpunit-polyfills:^<version>` (polyfills bridge PHPUnit API differences so WP's test scaffolding keeps working across versions); `wp scaffold plugin-tests open-source-event-calendar` can regenerate the test bootstrap if it drifts. The currently pinned versions are always whatever's in `composer.json`/`composer.lock` — check there rather than assuming a version from memory.

## Developer Tooling

- `bin/` - Developer scripts:
  - `compile-less-production.sh` - Compiles LESS for production
  - `get-latest-plugin-review-phpcs-rulesets.sh` - Fetch latest PHPCS rulesets
  - `install-wp-tests.sh` - WordPress test environment setup
- `twig_to_js_transform/` - Converts Twig templates for frontend use

### PhpStorm MCP (JetBrains IDE integration)

- When running via `ddev claude`, the PhpStorm MCP server (started by the IDE on the host) is reached from inside the container at `http://host.docker.internal:<port>/stream`. Its built-in DNS-rebinding protection rejects the `Host: host.docker.internal:<port>` header the container sends by default — add a header override `Host: localhost:<port>` on that MCP server entry, or requests get a bare `403 Forbidden`.
- PhpStorm identifies the open project only by its **host** filesystem path (e.g. the Mac path where the repo actually lives), never the container path (`/var/www/html/...`). Always pass `projectPath` as that host root, and give any file/directory arguments relative to it — a container path is rejected with "doesn't correspond to any open project."
- **`execute_terminal_command` runs on the host Mac, not the DDEV container** — confirmed via `pwd`/`uname -a` returning host paths and `Darwin`. It has no path restriction of its own (unlike `read_file`/`list_directory_tree`, which do reject paths outside the project/library/SDK roots): tested 2026-09-11 with `ls -la /Users/tho`, well outside the project, and it returned the full listing. PhpStorm's own **"sandboxed" toggle for this tool did not confine it** — same test, same unrestricted result with the toggle enabled; treat that setting as non-functional. **"Manual approval" mode, however, verified as a real gate**: with it on, a call the user actively rejects in PhpStorm's UI returns `"User rejected command execution"` instead of executing. If this tool needs to stay enabled, manual-approval is the one setting confirmed to actually stop an unwanted command — don't rely on "sandboxed" instead, and don't rely on Claude Code's own auto-mode classifier as a backstop either (separately observed to be inconsistent — auto-denied a call once, then let an identical retry through to a manual prompt with no code change in between).
  - **The approval prompt does not steal focus or otherwise notify** — PhpStorm doesn't switch windows or alert on it, so the user has to already be looking at the IDE to see and act on it. When about to call `execute_terminal_command` (or invoking anything that may route through it, e.g. `execute_run_configuration`), explicitly tell the user beforehand to switch to PhpStorm and watch for the prompt — don't assume they'll notice it on their own.
- When the PhpStorm MCP tool (`mcp__phpstorm__execute_tool`) is available, prefer it over generic CLI equivalents for the tasks below — it uses PhpStorm's real PHP/WordPress index and live debugger rather than text-matching or manual instrumentation. Fall back to Bash/grep-based tools when the MCP isn't connected, or for anything not listed here (e.g. running `ddev phpunit`, `composer`, `wp-cli`).
  - **Symbol search / find usages** — `search_symbol`, `get_symbol_info`, `analyze_calls` instead of `grep`/`Explore` when looking for a class/function/hook definition or all its call sites.
  - **Text/pattern search across the repo** — `search_text`, `search_regex`, `search_structural` instead of Bash `grep`/`find` for anything scoped to this project's indexed files.
  - **Renaming a symbol** — `rename_refactoring` instead of a manual multi-file `sed`/`Edit` pass, so references (including string-based ones PhpStorm tracks) get updated consistently.
  - **PHP inspections** — `get_inspections` / `get_file_problems` as a complement to `phpcs` (`phpcs.xml`); PhpStorm's inspections catch some things `phpcs` doesn't (e.g. type/dead-code issues).
  - **Debugging a live bug** — `xdebug_*` tools (`xdebug_start_debugger_session`, `xdebug_set_breakpoint`, `xdebug_get_stack`, `xdebug_get_frame_values`, `xdebug_evaluate_expression`, etc.) instead of adding temporary `var_dump`/`error_log` statements, when reproducing the bug through the running DDEV site is feasible.
    - `xdebug_start_debugger_session` does **not** attach to an incoming web request — it spins up a standalone "PHP Script configuration" and runs the target file as a CLI script. For a real HTTP-triggered breakpoint: (1) `xdebug_set_breakpoint --filePath <path-relative-to-projectPath> --line <N>`; (2) `invoke_ide_action --actionId PhpListenDebugAction` to toggle PhpStorm's "Listen for Debug Connections" — this has no queryable on/off state via MCP, so if unsure whether it's already listening, check from inside the container with `timeout 3 bash -c "cat < /dev/null > /dev/tcp/host.docker.internal/9003"` (port open = listening) rather than re-toggling blind; (3) fire the request as a **backgrounded** `curl ".../?XDEBUG_TRIGGER=1"` (it blocks for the whole pause — a synchronous `curl` or browser navigation will hang); (4) poll `xdebug_get_debugger_status` for `"state":"paused"` — the paused session is stable (not racy) across subsequent `xdebug_get_stack` / `xdebug_get_frame_values` / `xdebug_evaluate_expression` calls; (5) `xdebug_control_session --sessionId <id> --action RESUME` to let the request finish, then `xdebug_remove_breakpoint` to clean up.
  - **Ad-hoc read-only DB investigation** — `execute_sql_query` / `preview_table_data` / `list_database_schemas` as an alternative to raw `mysql`/`wp db query`, still subject to the read-only rule in "Database Safety" above.
  - **Editing files opened in the IDE** — `apply_patch` / `reformat_file` are fine for changes the user is actively watching in PhpStorm; for everything else the standard Edit/Write tools remain the default.

## Release & Build Tooling

- Anything shipped in a release **must be committed to git first**; the CI `static_release_job` verifies generated files are up to date
- `README.txt` is generated from `README.md` — never hand-edit it: `ddev wp osec make_readme`
- `hooks-and-filters.md` is generated from PHPDoc by the separate [`hookster_markdown`](https://github.com/digitaldonkey/hookster_markdown) tool and must be regenerated before each release:
  ```bash
  git clone git@github.com:digitaldonkey/hookster_markdown.git
  cd hookster_markdown && npm install && npm run build
  ```

## CircleCI Behavior by Branch

What the CircleCI pipeline (`.circleci/config.yml`) does differently depending on which branch triggers it:

- `master` — runs the full test matrix; creates a GitHub dev release; creates a GitHub tag release and a [WordPress.org plugin release](https://wordpress.org/plugins/open-source-event-calendar) if the commit is tagged; contains the latest bugfixes
- `release-*` — runs the full test matrix; does not create a release
- Next-release branch (e.g. `1.2.x-dev` if the current release is `1.1.x`) — a dev branch for the next semantic major version; should include all bugfixes from master; no release

## Key Conventions

1. Follow the existing OOP structure in `src/`
2. Use the Bootstrap pattern in `src/Bootstrap/`
3. Implement proper data sanitization and validation
4. Use `$wpdb` or `WP_Query` for database operations
5. Use WordPress's authentication and authorization functions
6. Implement proper AJAX handling via REST API
7. Use the hook system for modular and extensible code
8. Implement background processing for long-running tasks

## Plan Review for High-Risk Changes

Before executing a plan (e.g. in `/plan` mode) that touches something with real blast radius — a live PHP entry point loaded on every request, a CI gate, a public WP-CLI command, database schema, or anything else hard to unwind once shipped — run two extra review passes before implementing, not just a single design pass:

1. **Harsh review pass**: explicitly ask for (or perform) a critical review that skips praise/encouragement and only reports what's wrong, hidden risks, and unhandled edge cases. Don't settle for "looks good" — actively hunt for things like corruption risk from writing to executed files, fail-open logic that silently stops guaranteeing something it claims to guarantee, and regressions in existing commands/hooks/CI steps.
2. **One-by-one decision walkthrough**: after the harsh review, go through every discrete decision or assumption in the plan individually with the user — confirm, change, or remove each one — rather than a single "does this look good?" pass. Calibrate granularity to stakes: batch cosmetic/low-stakes decisions a few at a time, but give genuinely consequential ones (severity of a check, fail-open vs. fail-closed, a chosen mechanism) their own turn.

Skip this for low-stakes or easily-reversible changes — it's overkill there. Reserve it for plans where a mistake would be expensive to discover after the fact.

## Working With This Codebase

- Be aware of legacy code patterns from the original plugin
- When refactoring, maintain backward compatibility where possible
- Prefer updating to modern WordPress standards over maintaining legacy patterns
- When uncertain about original JavaScript behavior, check available sources in `assets/` or `public/`

## Documentation

- `README.md` - General plugin documentation
- `CONTRIBUTING.md` - Contribution guidelines
- `CHANGELOG.md` - Version history
- `hooks-and-filters.md` - Auto-generated hooks reference

## Output Expectations

- Working code, not explanations
- Production-ready security practices
- No boilerplate comments or placeholder text
- If something is unclear, ask—don't assume
