@.claude/shared/global/CLAUDE.md

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

The dev site's database `db` stays as it is unless the maintainer asks to add, change or remove
something there. Before changing `db`, ask once per session: "Can I drop (or alter) the database?"
After a yes, no further asking in that session. Read-only queries on `db` are always fine.

**For evaluating, reproducing and experimenting, use a sandbox database instead - no asking needed.**
One DDEV MySQL holds them next to `db` (set up 2026-10-06):

| Database | Use |
|---|---|
| `db` | the dev site; only on request |
| `claude` | Claude's permanent sandbox, a copy of `db` |
| `try_<name>` | throwaway copies, create and drop freely |
| `phpunit` | PHPUnit's own (`wp-tests-config.php`), unrelated |

- **Select it**: browser `https://<name>.ddev-wordpress.ddev.site` with `-` for `_` (`try-x.ddev-wordpress.ddev.site`
  is database `try_x`: the DDEV router answers "404: No Route Found" for underscores, while `curl` from the container
  reaches the web server directly and hides that), WP-CLI `WP_SANDBOX_DB=<name> /usr/local/bin/wp ...` with the
  database name. **Set the variable on every call** - without it, WP-CLI uses `db`.
  A name other than `claude` / `try_*` stops with "Unknown sandbox database", so a typo never reaches `db`.
- **Create / reset / drop** (as `root`/`root`, which may create databases; `db` has grants on `claude` and `try\_%`):
  ```bash
  mysql -uroot -proot -h db -e "DROP DATABASE IF EXISTS try_x; CREATE DATABASE try_x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
  mysqldump -uroot -proot -h db --single-transaction db | mysql -uroot -proot -h db try_x
  mkdir -p /var/www/html/wp-content/uploads/osec-sandbox/try_x
  ```
- **What is separate and what is shared**: database, `WP_HOME` and the OSEC file cache
  (`OSEC_FILE_CACHE_DEFAULT_PATH` = `uploads/osec-sandbox/<name>/`, so a sandbox compile never overwrites the dev site's
  CSS) are per sandbox; APCu follows the URL. **Shared**: plugin code (a sandbox only matches the checked-out code),
  uploads/media, everything else on disk.
- **Where it lives** (not versioned - this section is the only record): a block in `/var/www/html/wp-config.php` before
  the DDEV include sets `DB_NAME`, `WP_HOME`, `OSEC_FILE_CACHE_DEFAULT_PATH`; the wildcard hostname
  `additional_hostnames: ["*.ddev-wordpress"]` in `.ddev/config.yaml`; the MySQL grants. WordPress here takes its URL
  from `WP_HOME` (`wp-config-ddev.php`), so a copied database needs no search-replace.
- **The block only survives without the `#ddev-generated` marker** in `wp-config.php` (removed 2026-10-06). With the
  marker, DDEV regenerates the file and drops the block, and then `WP_SANDBOX_DB` is silently ignored - WP-CLI writes to
  `db` (happened 2026-10-06). Before a write, check `WP_SANDBOX_DB=claude /usr/local/bin/wp option get home` answers
  `https://claude.ddev-wordpress.ddev.site`.
- The destructive Mocha/Selenium suite (see Testing) belongs in a sandbox, not on the dev site: `BASE_URL` +
  `OSEC_TEST_HOST_RULES`, see `TESTING.md` "Running against a sandbox".

When a DB change is the straightforward route, make it in a sandbox instead of building workarounds to avoid the
write (temporary mu-plugins, option filters, cookie or query-param overrides).

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
- **Document generated code before calling it ready**: every new file gets a header (what it is, usage, options),
  every new function/class a docblock (PHPDoc / JSDoc: purpose, params, return), a tool folder a `README.md`
  (flow, files, data format). Inline comments stay minimal; documentation is not optional

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
| `integration_tests/` | Browser tests, npm workspaces root: `mocha/` (Mocha/Selenium), `visual_regression/` (Playwright) |
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
- **Charset**: tables from older releases can still be utf8mb3 (`dbDelta()` never converts them);
  `DatabaseCharset` converts on demand. Details: `.claude/rules/database-charset.md`

## Where the details live

This file is the core, loaded every session. Topic details load on demand:

- **Path-scoped rules** in `.claude/rules/` load when working on matching files. Read one directly when the topic
  comes up before any such file is open:
  - `recurrence-feeds.md` - data model, recurrence, EXDATE/RDATE, ICS import/export, rule gates
  - `css.md` - frontend CSS delivery, CSS compile caching, base font size, LESS unit traps
  - `twig-js.md` - the twig→JS transform for agenda/oneday/month
  - `testing.md` - PHPUnit/SQLite/Selenium details, test DB traps
  - `admin-notifications.md` - the NotificationAdmin mechanism in full
  - `database-charset.md` - utf8mb4 conversion
- **Skills** in `.claude/skills/`: `circleci-results` (reading CI runs, token), `release-pipeline` (CI release gates,
  branch behaviour, tagging), `prepare-release`, `phpstorm-mcp`, `compare-old-release`, `process-tickets`

## Data Model: Events & Recurrence

- Events are posts (post type `osec_event`), ID-linked to the `wp_osec_events` table; instances live in
  `wp_osec_event_instances` (start/end Unix timestamps)
- **Instances are only (re)built by `Event::save()`**: a generator fix does not repair stored events until they are
  re-saved (`wp osec event regenerate` in bulk)
- Recurrence is expanded in the event's own timezone; near-midnight start times are the edge case that matters
- Full rules (limits, UNTIL/COUNT, EXDATE/RDATE timezone convention, which library rejects what):
  `.claude/rules/recurrence-feeds.md`
- [wiki: Understanding data model](https://github.com/digitaldonkey/open-source-event-calendar/wiki/Understanding-data-modell)

## Hooks & Extensions

- Use hooks (actions and filters) exclusively - never modify core/plugin files
- Hookster markdown documentation auto-generated from PHPdoc comments via `hookster_markdown/`
- Document hooks in PHPdoc for documentation generation

## Admin Notifications

**Never hand-roll an `admin_notices` callback.** Everything that tells an admin something goes through
`NotificationAdmin::factory($app)->store(...)` (dispatch is already registered). Escape interpolated values with
`esc_html()` before `store()` (the template prints `|raw`). Anything that runs without a browser (cron feed imports,
the ICS export) must report through it. Degrade and report, do not throw. Details:
`.claude/rules/admin-notifications.md`

## Assets & JavaScript

- Use `wp_enqueue_script()` / `wp_enqueue_style()` exclusively
- REST API preferred over `admin-ajax.php`
- Gutenberg blocks in `calendar_block/` use WordPress scripts
- Legacy code may exist from original all-in-one-event-calendar
- **No Internet Explorer support** (dropped 2026-10-14, from 1.2.0 on), frontend and wp-admin alike. Remove IE
  hacks (`\9`, IE-only `-ms-`/`-khtml-` prefixes, `.eot` fonts, `filter:` gradients, `window.ActiveXObject`
  branches) when touching that code, and use current CSS and JS freely (custom properties, CSS masks, `calc()`,
  `scale`, `pagehide`). A rebuild that only drops IE-only output needs no porting back into the sources.

## Frontend CSS and Theme LESS

- Every site compiles its own LESS into one stylesheet; `FrontendCssController` owns compile, store, link and route
- **Font sizes in LESS/CSS use `em` or `%`, never `rem` or a hardcoded `px`** (print styles excepted)
- **Never add two LESS values of different units** - `less.php` silently keeps the first unit; use `calc( ~"..." )`
- **Loading WordPress (WP-CLI too) can compile the CSS** when the invalidate flag is set - read cache state with `mysql`
- Engines, compile triggers, lock/backoff, debugging a LESS edit that "doesn't take", escape-hatch constants:
  `.claude/rules/css.md`

## Twig → JS Frontend Templates

**If you edit `agenda.twig`, `oneday.twig` or `month.twig`, re-run the transform**, or the JS-side templates in
`public/js/pages/calendar.js` silently go stale:
```bash
cd twig_to_js_transform && nvm use && npm install && npm run build-twig-frontend
```
Details (twig.js 3 autoescape, markers, escaping test): `.claude/rules/twig-js.md`

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
- **The git hooks belong to the maintainer; leave them as they are and don't suggest changing them.**
  `.git/hooks/pre-commit` and `commit-msg` call `ddev exec`, a stub inside the web container that prints a
  hint and exits 0, so they do nothing for commits made from `ddev claude`. Ignore that hint.
- **Claude's own pre-commit check is a Claude Code hook**: `.claude/hooks/pre-commit.sh`, registered as
  `PreToolUse` in `.claude/settings.json`. Before every `git commit` inside the container it pipes the
  staged diff into `vendor/bin/grumphp git:pre-commit`, as the git hook does (testsuite `git_pre_commit`:
  composer, phpcs on the staged files, editorconfig on all tracked files, phpunit), and blocks the commit on failure. Stage with `git add` in
  a **separate** command and don't use `commit -a`: the hook runs before the command, so it would check
  the old index (it blocks both).
- **Before handing work back** (and for anything the hook doesn't cover):
  - `vendor/bin/phpunit tests` and check the exit code (see the no-skipped-tests rule below)
  - `vendor/bin/phpcs --standard=phpcs.xml <changed paths>`
  - after editing `agenda.twig`, `oneday.twig` or `month.twig`: re-run the twig→JS transform
  - or `vendor/bin/grumphp run --testsuite=git_pre_commit` for composer + phpcs + editorconfig + phpunit at once
  - **Never bare `vendor/bin/grumphp run` from an agent session** - with no `--testsuite` it runs *every*
    configured task, including `integration_tests`, which drives the Selenium suite against the dev site
    and trashes the calendar page (see the warning under Testing)

## Testing

See `TESTING.md` and `.claude/rules/testing.md`. Quick reference:

- **PHPUnit**: `vendor/bin/phpunit tests` (CI's command) - **check the exit code**: `phpunit.xml` fails on skipped,
  risky and incomplete tests, so "OK, but … skipped" fails CI. No `markTestSkipped()` for multisite variants
- **SQLite**: `bin/phpunit-sqlite.sh`
- **Code quality**: `vendor/bin/phpcs --standard=phpcs.xml <paths>`; WordPress plugin-check: `bin/plugin-check.sh`
- **GrumPHP**: `vendor/bin/grumphp run --testsuite=git_pre_commit`
- **The Mocha/Selenium suite is destructive to the dev site** (reinstalls the plugin, trashes every calendar page on
  teardown). Ask before running it, also indirectly: bare `grumphp run`, `--testsuite=all_tests`, `ddev grumphp all`.
  Run it in a fresh `try_*` sandbox (`BASE_URL`, purge constant scoped to that `DB_NAME`; `TESTING.md`)
- **A calendar page must be set for any testing**: "no calendar page set" is a setup error, not a code bug. Check
  `calendar_page_id` points to a published page before judging calendar output
- When fixing bugs, add tests where appropriate

## Developer Tooling

- `bin/` - developer scripts (`compile-less-production.sh`, `get-latest-plugin-review-phpcs-rulesets.sh`,
  `install-wp-tests.sh`)
- `wp osec content generate` - test content around May of next year (dataset `tests/data/content/`); replaces only
  its own earlier content and refuses foreign calendar data, so use a `try_*` sandbox on a used site
- `integration_tests/visual_regression/` (`ddev osec-review`, `ddev osec-approve`) - Playwright screenshots compared
  with a baseline set, review page with approval. See its `README.md`
- `bin/screenshots/` (`ddev osec-screenshots`) - the WordPress.org `assets/screenshot-*.png`
- `bin/js-rename-check/` - proves a hand edit of a minified bundle changed only names/formatting
- `twig_to_js_transform/` - converts Twig templates for frontend use
- **PhpStorm MCP**: when connected, prefer it for symbol search, renames, inspections and Xdebug. Its
  `execute_terminal_command` runs **on the host Mac** - load the `phpstorm-mcp` skill before using it

## Release, CI and Generated Files

- `README.txt` is generated from `README.md` - never hand-edit it: `/usr/local/bin/wp osec make_readme`
- `hooks-and-filters.md` is generated from PHPDoc by `hookster_markdown`; regenerate it in the same commit as a hook
  docblock change, or `static_release_job` fails
- Anything shipped must be committed first, or the release jobs' `git status` checks fail
- Gates, probe branches, branch/tag behaviour and tagging a WordPress.org release: skill `release-pipeline`;
  checklist: skill `prepare-release`
- Reading CI results (`circleci` CLI, triage order, **read source at the run's revision**, token renewal ≈ 2026-12-23):
  skill `circleci-results`. `run trigger/cancel`, `workflow rerun/cancel` and project-settings commands are denied

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

## Bug Fixing & Error Handling Rules

* **Root Cause First:** Never rush into workarounds. Identify, explain, and fix the exact root cause of an error before writing any new code.
* **No Workarounds:** Do not introduce wrappers, hacks, or complex boilerplate to fix problems introduced in previous steps. Keep fixes minimal and elegant.
* **Trace & Verify:** Step back and trace the original data flow before assuming a new architectural change is needed.
* **History First:** On unexpected behavior, check the git history of the code involved before designing a fix: `git log --follow -p -- <file>`, `git log -L :<function>:<file>`, `git log -S '<code>'`. Find the commit that changed it and compare with the version before. The root cause is often a small regression, and the older version often shows the intended behavior (B17: one line from `95e2c642` dropped a lookup that was still in the file). The January 2026 Plugin Check / nonce / "TWIGify" commits (2026-01-06..26) caused several such regressions (B1, B17).
* **Self-Correction:** If a previous solution broke the build or introduced a new bug, immediately revert the logic to the last stable state and try a completely different approach.

## Working With This Codebase

- Be aware of legacy code patterns from the original plugin
- When refactoring, maintain backward compatibility where possible
- Prefer updating to modern WordPress standards over maintaining legacy patterns
- When uncertain about original JavaScript behavior, check available sources in `assets/` or `public/`
- "Did this work in <tag>?": never a `git worktree` with symlinked `vendor/` (it loads `src/` from this checkout);
  see skill `compare-old-release`

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
