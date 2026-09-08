# Development Environment

This project runs inside DDEV.

## Important

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

The DDEV database is a development database.

Do not perform destructive database operations unless explicitly
requested.

Never drop, truncate, or reset the database without asking first.

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

## Testing

- **PHPUnit**: `ddev phpunit` (or `ddev phpunit --filter test_name ./tests/Unit/...`)
- **Integration (Mocha/Selenium)**: `cd integration_tests && npm run test`
- **Code quality**: `ddev composer run-script phpcs` or `ddev run-script phpcs`
- **GrumPHP**: `vendor/bin/grumphp run` (pre-commit hooks)
- Run tests in DDEV environment; initialize once with `bin/install-wp-tests.sh`
- When fixing bugs, add tests where appropriate
- First-time setup after clone: `ddev composer install`
- WP test scaffolding needs a subversion client; in DDEV add `webimage_extra_packages: [subversion]` to `.ddev/config.yaml`
- Full test-DB init invocation: `bin/install-wp-tests.sh phpunit root root db:3306` (one-time)
- Non-blocking sniffs: `ddev composer run phpcs-warnings`
- Explicit phpcs invocation: `./vendor/bin/phpcs --standard=phpcs.xml --runtime-set testVersion 8.2-` (the `testVersion` override is needed because `plugin-check.ruleset.xml` otherwise enforces WP's default minimum PHP version)
- Mocha/integration tests require the plugin to be disabled first and all tables clean (`OSEC_UNINSTALL_PLUGIN_DATA`)

**Updating the PHPUnit version**: WordPress core's test framework only supports specific PHPUnit versions per WP release — before bumping the project's target WordPress or PHP version, check the [WP core PHPUnit compatibility chart](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/#supported-version-chart) and cross-check the candidate PHPUnit release on [Packagist](https://packagist.org/packages/phpunit/phpunit) against the project's PHP floor (8.2+). Update with `composer require --dev phpunit/phpunit:^<version>` and `composer require --dev yoast/phpunit-polyfills:^<version>` (polyfills bridge PHPUnit API differences so WP's test scaffolding keeps working across versions); `wp scaffold plugin-tests open-source-event-calendar` can regenerate the test bootstrap if it drifts. The currently pinned versions are always whatever's in `composer.json`/`composer.lock` — check there rather than assuming a version from memory.

## Developer Tooling

- `bin/` - Developer scripts:
  - `compile-less-production.sh` - Compiles LESS for production
  - `get-latest-plugin-review-phpcs-rulesets.sh` - Fetch latest PHPCS rulesets
  - `install-wp-tests.sh` - WordPress test environment setup
- `twig_to_js_transform/` - Converts Twig templates for frontend use

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
