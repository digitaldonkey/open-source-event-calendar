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

**Always call WP-CLI by its absolute path `/usr/local/bin/wp`** (DDEV's stable WP-CLI, the same as
`ddev wp`). A bare `wp` resolves to the plugin's composer-installed `vendor/bin/wp`, currently an
unreleased WP-CLI 3.0 dev build (Composer resolves `wp-cli/wp-cli-bundle` to `dev-main`), which also
disappears whenever `vendor/` is swapped.

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
- **Global classes need a leading backslash or an import inside `src/`.** `catch (Throwable ...)` in a
  namespaced file silently catches `Osec\…\Throwable`, i.e. nothing, and phpcs does not flag it. This repo
  also ships its **own** `Osec\Exception\InvalidArgumentException`, so a file importing that one must catch
  the library's with `\InvalidArgumentException` — the names collide and the wrong one compiles fine
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
- **Recurrence is expanded in the event's own timezone**, not UTC, so occurrences keep their wall clock time
  across DST transitions (`EventInstance::process_rrule_freq()` hands php-rrule a DTSTART carrying a real
  timezone and uses its occurrences as-is). Beware `new DateTime('@' . $ts, new DateTimeZone($tz))`: PHP
  **silently ignores the timezone argument** whenever the time string starts with `@`, always yielding
  `+00:00`. That one line made every recurrence drift an hour per DST change from 1.1.5 to 1.1.14.
- **Instances are only (re)built by `Event::save()` → `EventInstance::recreate()`.** Nothing regenerates them
  on read, so a fix to the generator does **not** repair events already in the database - they stay wrong
  until each is re-saved or its ICS feed re-imported. Expect correct and broken events side by side on the
  same install, and re-save before judging whether a recurrence fix worked. `wp osec event regenerate` rebuilds them
  in bulk (keyset batches, flat memory, resumable with `--start-after`) and removes rows of deleted posts.
- **Near-midnight start times are the edge case that matters.** An hour of drift is only *visible* when it
  crosses a calendar-day boundary, so bugs here hide unless the start time is within an hour of midnight:
  a summer-created event at `[00:00, 01:00)` doubles the autumn transition day; a winter-created one at
  `[23:00, 24:00)` loses the spring transition day. Integration tests that create events at "now" therefore
  pass or fail depending on what time the pipeline ran - see
  `.claude/plans/13-one-hour-before-midnight-test-fails.md`.
- **Two limits bound how many instances one event may generate**, and they do different jobs:
  `OSEC_REOCCURRENCE_TIMEFRAME` (`+ 3years`) is applied **only to a rule that names no end of its own** - it is
  what keeps an open-ended `FREQ=DAILY` finite. An explicit `UNTIL` is honoured in full, however far out, and
  `OSEC_REOCCURRENCE_MAX_INSTANCES` (2000) is the ceiling that actually bounds the table. Do **not** clamp
  `UNTIL` to the timeframe: it costs a yearly series to 2099 70 of its 74 occurrences (monthly: 843 of 888)
  while saving nothing the ceiling does not already bound (daily to 2099 is 27023 occurrences either way
  capped at 2000).
- **`UNTIL` and `COUNT` in one rule are repaired, not rejected.** RFC 5545 forbids the combination and
  php-rrule throws on it, but real exporters emit it: `process_rrule_freq()` lifts `UNTIL` out before
  `new RRule()` and applies it while iterating, so whichever part ends the series first wins.
- **A rule the generator cannot use is never stored.** `Event::save()` asks
  `EventInstance::get_rule_error()` before writing the row and drops the rule (reporting it) if php-rrule
  would refuse it, so the editor, the feed import, the clone and "edit this instance" all get the same gate.
  Storing such a rule instead forces *every* reader to guard against it - the generator, the ICS export and
  the repeat text each produced their own bug that way - and leaves the event claiming a recurrence it does
  not have. The downstream guards stay as a net for rules stored by older versions: the generator drops and
  reports (`createCollection()` has already cached the single occurrence), the export leaves the rule out,
  and `RepeatRuleToText` skips months it cannot name.
- **`exception_dates` (EXDATE) stores the date in the series' own timezone; the time part is ignored.**
  `EventInstance::process_rrule_datelist()` reads only `Ymd` and applies the series' start time, so every
  writer must convert first - `EventParent::add_exception_date()` does, the feed import does since `4b34a211`
  (`IcsImportExportParser::exclusion_date()`, also used for RECURRENCE-ID overrides). A UTC date there names
  the wrong day whenever the local start falls on another UTC date: after midnight east of UTC, in the
  evening west of it (New York from ~19:00), a far wider window than the "hour before midnight" above.
  `recurrence_dates` (RDATE) is still stored as the feed wrote it - same trap if a feed writes RDATEs in UTC.
- Further reading: [wiki: Understanding data model](https://github.com/digitaldonkey/open-source-event-calendar/wiki/Understanding-data-modell)

## Feeds (iCalendar)

- RFC 5545 iCal feed support via `kigkonsult/icalcreator` + `rlanvin/php-rrule`
- **Manual/UI testing**: `tests/Unit/App/Model/ical_feeds/*.ics` doubles as sample feed data you can subscribe to from any real calendar client (Google Calendar, Apple Calendar, etc.) to eyeball feed output end-to-end, e.g. `https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/google_cycling_halifax.ics`
- **Automated/unit testing**: the same fixture files back `tests/Unit/App/Model/IcsImportExportParserTest.php`, which exercises `IcsImportExportParser->add_vcalendar_events_to_db(Vcalendar $v, array $args)` — the core ICS-import parsing logic
- **The two libraries reject different rules, in three different places** — know which gate you are looking at
  before debugging a "broken" feed:

  | Rule | iCalcreator (`Vcalendar::parse()`, `setRrule()`) | php-rrule (the generator) |
  |---|---|---|
  | `FREQ=WEEKLY;BYMONTHDAY=15`, `FREQ=DAILY;BYWEEKNO=10` (structural) | rejects | rejects |
  | `BYMONTH=13`, `BYMONTHDAY=32` (out of range), `INTERVAL=0` | accepts | rejects |
  | `UNTIL` + `COUNT` together | accepts | rejects (we repair it, see above) |

  `Vcalendar::parse()` validates the **whole calendar**, so a structural violation anywhere rejects the entire
  feed before any event is processed - that gate sits in front of the per-event handling and no amount of
  per-event recovery helps. It is caught in `IcsImportExportParser::import()` and rethrown as
  `ImportExportParseException` carrying the library's reason.
- **The export is the second place a stored bad rule surfaces.** Since an invalid rule is stored on the event,
  `setRrule()` would throw and break the feed for every other event in it; `export_recurrence_rule()` leaves
  the rule out instead, and the event exports as a single occurrence, matching what the calendar shows.

## Hooks & Extensions

- Use hooks (actions and filters) exclusively - never modify core/plugin files
- Hookster markdown documentation auto-generated from PHPdoc comments via `hookster_markdown/`
- Document hooks in PHPdoc for documentation generation

## Admin Notifications

**Never hand-roll an `admin_notices` callback.** The plugin has one notification mechanism and everything
that needs to tell an admin something goes through it:

- `NotificationAdmin::factory($app)->store($message, $class, $importance, $recipients, $persistent)` queues a
  message; `BootstrapController` already registers the `admin_notices` **and** `network_admin_notices`
  dispatch (`->send()`), so a new notice needs **no new hook registration**.
- `are_notices_available($importance)` does the screen gating for you: importance `0` shows only on the
  calendar's own screens (`AccessControl::is_all_events_page()`, `are_we_editing_our_post()` — i.e. the event
  edit screen), `1` adds Plugins/Updates, `2` adds the Dashboard.
- `$persistent = true` renders the dismiss button wired to `wp_ajax_osec_dismiss_notice`
  (`NotificationAdmin::dismiss_notice()`). A non-persistent message is deleted the first time it is shown.
- Messages are keyed by `sha1` of the entity, so storing the same message twice does not stack it.
- **`notification/admin.twig` prints `{{ message | raw }}`** — escape every interpolated value with
  `esc_html()` *before* calling `store()`, never at render time.
- Dispatch is delayed by design: `store()` during a `save_post` or a cron run, and the notice appears on the
  next admin page load.
- The `osec_admin_notification_pre_store` filter short-circuits `store()`. The WP-CLI commands hook it for
  the length of a run (`Osec\WpCli\PrintsAdminNotices`) to print instead of store. Keep it scoped like that:
  `wp cron event run` is also WP-CLI, and a global redirect would send nightly feed failures to a cron log.

**Anything that runs without a browser to answer to must use it.** Scheduled feed imports call
`FeedsController::update_ics($feed_id)` with `$ajax === false`, and that return value — error message and all —
is simply discarded. Before this was noticed, a nightly feed could fail forever in silence. The same applies to
the ICS export, which runs unauthenticated: `ImportExportController::export_events()` subscribes to the export
problem hook and stores a notice rather than answering the fetcher.

**In tests**, notices live in the `osec_admin_notifications` option and therefore leak between tests in a
class (and the bootstrap leaves one behind). Clear it in `set_up()`:
`$osec_app->options->delete(NotificationAdmin::OPTION_KEY);` — see
`tests/Unit/App/Model/PostTypeEvent/RecurrenceNoticeTest.php`.

**Degrade and report, do not throw.** The pattern used for recurrence problems: the model fires a documented
action, the caller decides how to surface it (editor notice, feed result message, admin notice). Current
actions: `osec_recurrence_truncated`, `osec_recurrence_rule_invalid`, `osec_recurrence_rule_not_exportable`.
A listener registered around one operation must be removed afterwards (`EventEditing` does this around
`$event->save()`), or it outlives the request that wanted it.

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
  - or `vendor/bin/grumphp run --testsuite=git_pre_commit` for composer + phpcs + phpunit at once
  - **Never bare `vendor/bin/grumphp run` from an agent session** - with no `--testsuite` it runs *every*
    configured task, including `integration_tests`, which drives the Selenium suite against the dev site
    and trashes the calendar page (see the warning under Testing)

## Testing

See `TESTING.md` for the full checklist, one-time setup, integration-test prerequisites, and GrumPHP task inventory. Quick reference:

- **PHPUnit**: `ddev phpunit` (or `ddev phpunit --filter test_name ./tests/Unit/...`)
- **Integration (Mocha/Selenium)**: `cd integration_tests && npm run test`
- **Code quality**: `ddev composer run-script phpcs` or `ddev run-script phpcs`
- **GrumPHP**: `vendor/bin/grumphp run --testsuite=git_pre_commit` (what the pre-commit hook runs)
- **The Mocha/Selenium integration suite is destructive to the dev site.** It installs/uninstalls the plugin,
  exercises the `OSEC_UNINSTALL_PLUGIN_DATA` purge, creates its own `Calendar` page, and **trashes every
  calendar page on teardown** - including one you were using - leaving `calendar_page_id` pointing at a
  trashed post, i.e. the "no calendar page set" state described below. Ask before running it, and know the
  ways to trigger it *indirectly*:
  - `vendor/bin/grumphp run` with no `--testsuite` (the `integration_tests` task, `grumphp.yml:40-43`)
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

- Anything shipped in a release **must be committed to git first**; `static_release_job` gates four generated files - see the inventory below for which, and for the three it does *not* cover
- `README.txt` is generated from `README.md` — never hand-edit it: `ddev wp osec make_readme`
- `hooks-and-filters.md` is generated from PHPDoc by the separate [`hookster_markdown`](https://github.com/digitaldonkey/hookster_markdown) tool and must be regenerated before each release:
  ```bash
  git clone git@github.com:digitaldonkey/hookster_markdown.git
  cd hookster_markdown && npm install && npm run build
  ```

### What `static_release_job` gates - and what it does not

Four steps in one job under `set -Eeuxo pipefail`, so **the job stops at the first failing gate** and
the later ones never run. Each was verified in both directions on 2026-09-24 - a passing run *and* a
deliberately broken commit that turned it red:

| Step | Regenerates | What actually gates |
|---|---|---|
| Verify WordPress version metadata | nothing - `osec prepare_release` is read-only by design | the command's own `WP_CLI::error()` exit 1; the `git status` wrapper around it is vestigial and can never fire |
| Verify Readme.txt | `README.txt` from `README.md` + plugin headers | `git status --porcelain` at the repo root |
| Verify Twig frontend templates | `public/js/{agenda,oneday,month}.js` and `public/js/pages/calendar.js` | `git status -s` from `twig_to_js_transform/` |
| Verify hooks-and-filters.md | `hooks-and-filters.md` | `git status --porcelain` at the repo root |

**The hooks gate was inert until `fcaab93d`**, and its failure mode is the one to watch for: it ran
`git status` *after* `cd hookster_markdown`, a separately **cloned repository**, so it inspected a tree
the build never touches and answered "No changes detected" unconditionally. The Twig step uses the same
`cd` + `git status` shape and is fine, because `twig_to_js_transform/` is a subdirectory of *this* repo
and `git status` from a subdirectory reports the whole repo with `../`-prefixed paths. **A nested clone
breaks such a check; a subdirectory does not.**

A hand-edit alone does not reproduce a readme failure: `make_readme` overwrites `README.txt`, so an
uncommitted edit is silently restored and the tree comes back clean. The stale content must be committed.

**Not gated, though shipped in `OSEC_RELEASE_WHITE_LIST`:**

- **`languages/`** - stale as of 2026-09-24. The committed `.pot` carries `POT-Creation-Date: 2025-05-20`
  and `Project-Id-Version: … 1.0.2` with 512 msgids; `wp i18n make-pot` yields 569. Strings added since
  (e.g. the recurrence-truncated notice) are absent, so they are untranslatable in all three locales, and
  the `.po` files sit at 512 too. `a58d837b` hand-edited 4 lines of the `.pot` rather than regenerating it.
- **`calendar_block/build/`** - tracked output of `wp-scripts build` from `calendar_block/src/`. In sync
  today (both last touched by `1d6c041f`), but nothing stops the next `src/` edit shipping a stale bundle.

**To probe a gate, name the branch `release-…`.** `static_release_job` is filtered to
`only: /^(master|release-.*)$/`, so a probe branch named anything else passes by never running the job -
the same false pass one level up. Use **one branch per gate** (`set -e` hides every gate after the first
failure), and trim the workflow to `build -> static_job -> static_release_job` to keep the probe off the
Selenium matrix. Nothing can publish from such a branch: all three deploy jobs are `only: /master/` *and*
carry a `circleci-agent step halt` guard.


## CircleCI Behavior by Branch

What the CircleCI pipeline (`.circleci/config.yml`) does differently depending on which branch triggers it:

- `master` — runs the full test matrix; creates a GitHub dev release; creates a GitHub tag release and a [WordPress.org plugin release](https://wordpress.org/plugins/open-source-event-calendar) if the commit is tagged; contains the latest bugfixes
- `release-*` — runs the full test matrix; does not create a release
- Next-release branch (e.g. `1.2.x-dev` if the current release is `1.1.x`) — a dev branch for the next semantic major version; should include all bugfixes from master; no release

## Reading CI Results

The `circleci` CLI is installed in the web container so pipeline results can be read directly
instead of being pasted in by hand. Set up 2026-09-24; verified working against real runs.

### The setup is NOT in version control

`/var/www/html/.ddev` sits **outside** this git repo (the repo is the plugin directory, the DDEV
config lives at the WordPress root), so none of the three files below are tracked anywhere. On a
fresh machine or a wiped DDEV config they must be recreated by hand — this section is the only
record of them.

| File | Contents |
|---|---|
| `.ddev/web-build/Dockerfile.circleci` | `RUN curl -1sLf 'https://packages.circleci.com/public/setup.deb.sh' \| bash && apt-get install -y --no-install-recommends circleci && rm -rf /var/lib/apt/lists/*` |
| `.ddev/.env.web` | `CIRCLE_NO_INTERACTIVE=1` (shared, no secret) |
| `.ddev/.env.web.local` | `CIRCLE_TOKEN=<the PAT>` — gitignored by DDEV ≥ 1.25.4 via `/.env.*.local` |
| `.ddev/config.circleci.yaml` | `post-start` hooks: `circleci setting set telemetry off \|\| true` and `… update-check off \|\| true` |

The `post-start` hooks are needed because the container home is not persisted across rebuilds, so
the CLI's own config is lost on every `ddev restart`. Both settings work without a token.

### The token

**The CLI cannot run without one** — with none set it exits 3 with `auth.token_missing`,
client-side, before any network call. The token is a **Read-scoped OAuth PAT**, the only CircleCI
credential that both drives the v2 API and is read-only at the API level:

| Token type | Works with the CLI (v2)? | Can change project settings/env vars/contexts? |
|---|---|---|
| Project token, *Read Only* scope | **No** — v1 API only | No |
| Personal API token (created manually) | Yes | **Yes** — full read *and* write |
| OAuth PAT, *Read* scope | Yes | **No** — enforced server-side |
| OAuth PAT, *Write* / *Admin* | Yes | Undocumented for Write; assume yes |

**It expires after 90 days with no refresh token** (issued 2026-09-24, so due ≈ 2026-12-23; the
symptom is 401 from every command). Renew on the **host** — the browser + `127.0.0.1` loopback +
keyring flow cannot run headless in the container:

```bash
brew install circleci
circleci auth login --insecure-storage   # choose "Read" on the consent screen
grep token ~/.config/circleci/config.yml # --insecure-storage writes it here instead of the keyring
ddev dotenv set .ddev/.env.web.local --circle-token="<PAT>"
ddev restart
```

`--insecure-storage` is documented only in `circleci auth login`'s *Details* prose, not its flag
table; if a build rejects it, the token goes to the keyring and `circleci setting list` prints it.

Check a token before wiring it in — `200` means it drives the CLI, `401` means it is a v1-only
project token:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -H "Circle-Token: $TOKEN" https://circleci.com/api/v2/me
```

Prove the Read scope is real (expect **403**; a `201` means the token is not read-only — delete the
var with `DELETE …/envvar/OSEC_SCOPE_PROBE` and re-issue the token):

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X POST \
  -H "Circle-Token: $CIRCLE_TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"OSEC_SCOPE_PROBE","value":"x"}' \
  "https://circleci.com/api/v2/project/gh/digitaldonkey/open-source-event-calendar/envvar"
```

### Commands

**Triage order for a red build** — `run get` (which job failed) → `testresult list` (which tests)
→ artifact screenshot if it is a frontend test → `job output list` only when the failure is
*outside* a test (apt, composer, install). Then check whether a later commit already fixed it.
Reaching for `job output list` first means grepping logs for something `testresult list` prints
as a table.

The project is auto-detected from the git remote, so `--project` is only needed for another repo.
Job arguments are **UUIDs**, not the job numbers in the web UI — passing a number fails with
`invalid UUID length: 4`. Get the UUIDs from `circleci run get`.

```bash
SLUG=gh/digitaldonkey/open-source-event-calendar

circleci run list --current-branch                  # recent runs, this branch
circleci run list --limit 60 --json                 # all branches
circleci run get <run-id>                           # workflows + job UUIDs
circleci job get <job-uuid>
circleci testresult list <job-uuid>                 # failing test names as a table — start here
circleci artifact <job-uuid>                        # screenshots etc. (NOT `artifact list`)
circleci config validate                            # before pushing a .circleci/config.yml edit

# why did it fail — output of every non-zero step
circleci job output list <job-uuid> --json \
  | jq '.steps[] | select(.exit_code != 0) | .output'

# most recent failure across all branches
circleci run list --limit 60 --json \
  | jq -r '.[] | select(.current_outcome != "succeeded")
           | "\(.created_at)  \(.current_outcome)  \(.branch)  \(.id)  \(.commit.subject)"'
```

`run list --json` returns a **top-level array** whose outcome field is `current_outcome`
(`succeeded` / `failed` / `not_run`) — not `.items[]` and not `.status`.

### Always read source at the run's revision, never the working tree

`circleci run get` prints the run's `Commit`. Read the failing file **at that revision** —
`git show <rev>:<path>` — before explaining a failure:

```bash
git show 3af4c8e:integration_tests/test/03_OsecPluginFrontend.spec.js | sed -n '305,322p'
```

A CI run is a snapshot of the past, and the fix is often already in the working tree, so quoting
`path:line` from disk can show code that **would have passed** — which is self-evidently not what
failed. This happened on 2026-09-24: the failed `release_test_job` on `fix/recurrence-dst-drift`
(`3af4c8e`) asserted `markerImgUrl.startsWith('https://unpkg.com/leaflet')`, but Leaflet had moved
to local delivery so the marker `src` was `<DOMAIN>/public/js/external_libs/leaflet/…` — a **stale
test**, not a plugin regression. The working-tree copy already carried the fix from `96a73aed`
(`startsWith(pageObject.settings.domain)`), which hid the entire cause.

Sanity check before reporting: *does the code I am quoting actually explain the failure?* If it
looks like it would pass, the revision is wrong.

**Then check whether it is already fixed before reporting anything.** A failed run is history, and
the maintainer may have fixed it hours later — reporting a fresh diagnosis of a solved problem
wastes their time. List what landed on the failing file since that revision:

```bash
P=integration_tests/test/03_OsecPluginFrontend.spec.js
git log --oneline 3af4c8e..HEAD -- "$P"     # run's commit is an ancestor of HEAD
git log --oneline --all --since='2026-09-23 08:59' -- "$P"   # fallback: unmerged branch
```

Use the second form when `git merge-base --is-ancestor <rev> HEAD` fails. Either would have
returned `96a73aed "Fix map integration test, remove unpkg.com references, as we moved to local
delivery."` in the case above, making the correct report "that build failed on a stale test,
already fixed in 96a73aed" rather than a diagnosis.

### Insights and artifacts (what the logs cannot tell you)

```bash
S=gh/digitaldonkey/open-source-event-calendar
H="Circle-Token: $CIRCLE_TOKEN"

# intermittent failures, with counts and the workflow timestamp of each flake
curl -s -H "$H" "https://circleci.com/api/v2/insights/$S/flaky-tests" \
  | jq -r '.flaky_tests[] | "\(.times_flaked)x \(.workflow_created_at) \(.job_name) \(.test_name)"'

# per-job success rate, p95 duration and credits over ~90 days
curl -s -H "$H" "https://circleci.com/api/v2/insights/$S/workflows/build_test_deploy/jobs?branch=master" \
  | jq -r '.items[] | "\(.name) \(.metrics.success_rate) \(.metrics.duration_metrics.p95)s \(.metrics.total_credits_used)"'
```

**Selenium screenshots are downloadable and can be looked at.** `circleci artifact <job-uuid> --json`
gives `{path, url}`; the URL **302s to a presigned host, so `curl` needs `-L`** — without it you get
a 0-byte file and `HTTP:302`. Reading the screenshot of a failing frontend test is often faster than
any log: on 2026-09-24 it showed the map and marker rendering correctly, proving the failure was a
stale assertion rather than a plugin regression.

Two things this surfaced on 2026-09-24, worth re-checking rather than re-deriving:

- **The `Add daily repeating event` flakes are the near-midnight bug.** All flakes (3× per theme)
  came from one workflow created `2026-09-22T22:23:06Z`. CI runs `TZ: "Europe/Berlin"`
  (`.circleci/config.yml:51`, matching `integration_tests/settings.js`), so that is **00:23 local** —
  inside the `[00:00, 01:00)` window described under *Data Model: Events & Recurrence*. One workflow
  is corroboration, not proof.
- **The Selenium matrix is ~69% of CI credits** (`release_test_job` ×4 ≈ 16,956 of 24,604 over ~42
  master runs), while the whole PHPUnit matrix is ~2,180. Test jobs sit at 90–92% success while
  `build` and `static_job` are at 100% — flakiness, not breakage.

### Guardrails: two independent gates, only one of which binds

`.claude/settings.json` gates what Claude may *type*; the **token scope** gates what CircleCI will
*do*. The token is the binding one, and it cannot be talked around.

`run trigger`, `run cancel`, `workflow rerun` and `workflow cancel` are **denied**. They were
briefly allowed on 2026-09-24 to test them, and the test showed the allow entries were inert: with
the Read-scoped PAT every one fails `403 Permission denied` at the API. They were reverted to deny,
so both gates now agree. Re-allowing them changes nothing unless the token is also re-minted with
**Write** scope — read the caveat below before doing that.

Also denied, because these change project state rather than pipeline runs: `envvar`, `context`,
`project`, `policy`, `runner`, `deploy`, `dlc`, `setting set/unset`, `orb publish`, `namespace`,
`auth login/logout`, plus `Read()` on `.env.web.local`. Keep deny patterns narrow — a blanket
`Bash(circleci setting:*)` also blocks the harmless read `circleci setting list`.

**To test what a scope really permits, hit the API endpoint directly — not the CLI.** The CLI
short-circuits client-side and never reaches the server: `circleci run cancel <finished-run>`
answers *"has no active workflows to cancel"* and exits 0, which says nothing about permissions.
The honest probes are:

```bash
# write to pipeline state (harmless on a finished workflow)
curl -s -w '%{http_code}\n' -X POST -H "Circle-Token: $CIRCLE_TOKEN" \
  https://circleci.com/api/v2/workflow/<finished-workflow-id>/cancel

# write to project settings — 403 is the answer that keeps the safety promise
curl -s -o /dev/null -w '%{http_code}\n' -X POST \
  -H "Circle-Token: $CIRCLE_TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"OSEC_SCOPE_PROBE","value":"x"}' \
  "https://circleci.com/api/v2/project/$SLUG/envvar"
```

**Before upgrading to a Write-scoped token, run the second probe.** CircleCI documents that OAuth
scopes are Read / Write / Admin but never says whether **Write** also permits changing env vars and
contexts. If it does, a Write token breaks the original requirement that CI access must not be able
to touch project settings. Mint it, probe it, and drop back to Read if the probe returns `201`
(deleting `OSEC_SCOPE_PROBE` afterwards).

### Fallback when the token is expired or revoked

The project is public, so the REST API answers these reads **unauthenticated**:
`/api/v2/project/{slug}/pipeline` → `/api/v2/pipeline/{id}/workflow` →
`/api/v2/workflow/{id}/job` covers status, and `/api/v2/project/{slug}/{job_number}/artifacts`
and `.../tests` work too. Step logs are the exception: they need the **deprecated v1.1** endpoint
`/api/v1.1/project/github/{org}/{repo}/{job_number}`, whose `output_url` values are presigned and
expire within minutes, so fetch them immediately.

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

### Comparing behaviour against an older release

To answer "did this work in <tag>?", **do not** `git worktree add` and symlink `vendor/` into it.
`vendor/composer/autoload_psr4.php` maps `Osec\` to `$baseDir . '/src'`, where `$baseDir` is resolved from the
autoloader's own location - so a symlinked `vendor/` silently loads `src/` from the **main checkout**, and the
old tag appears to behave like current `HEAD`. Verify with
`php -r 'require "vendor/autoload.php"; echo (new ReflectionClass(Osec\App\Model\PostTypeEvent\EventInstance::class))->getFileName();'`
before trusting any worktree result. A real `cp -a vendor` works, but the old tag's bootstrap may not survive a
current dev database (e.g. pre-77524dd5 `verifySqlSchema()` aborts the PHPUnit bootstrap).

What works reliably instead, on the live dev site:

1. `git checkout <tag> -- <the few files in the code path>` (or all of `src/`)
2. drive the real path with `/usr/local/bin/wp eval-file <script>` - each WP-CLI call is a fresh process, so
   there is no opcache/worker staleness to fight
3. `git checkout HEAD -- <same paths>` to restore

Two traps when restoring: `git checkout <tag> -- src/` **resurrects files deleted since** that tag (they come
back staged as added, and `git checkout HEAD -- src/` will not remove them - `git rm` them explicitly), and a
`git diff --stat <tag>..HEAD -- <path>` that prints nothing means the file is byte-identical, which is usually
a faster answer than any checkout.

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
