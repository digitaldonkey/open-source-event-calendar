---
paths:
  - "**/*.less"
  - "public/osec_themes/**"
  - "src/App/Controller/FrontendCssController.php"
  - "src/App/Controller/ExecutionLimitController.php"
  - "src/Cache/**"
  - "public/js/pages/calendar.js"
  - "constants.php"
  - "constants-local.php*"
  - "tests/Utilities/CssEngineTrait.php"
---

<!-- Loaded when working on LESS, themes or the CSS cache. Moved from CLAUDE.md. -->

## Frontend CSS Delivery

No theme ships precompiled CSS (dropped in 1.0.8), so every site compiles its own LESS into one stylesheet
(~400 KB). `FrontendCssController` (`src/App/Controller/FrontendCssController.php`) owns all of it: compile, store,
link, route. Since 1.2.0 (`feature/css-cache-rework`, plan `.claude/plans/css-cache-rework.md`):

**Where the CSS is stored** - decided only when it is compiled (`CacheFactory::createCache('css')`), first that works:

| Engine | Location | How the page gets it |
|---|---|---|
| File (default) | `uploads[/sites/<id>]/osec_cache/css/osec-compiled-<blog_id>.css`, or `OSEC_FILE_CACHE_DEFAULT_PATH` + `css/` when that constant is set and writable | `<link>` to the static file, `?ver=<hash>`; no PHP involved |
| Site transient (no writable folder, **only with a persistent object cache**: Redis, Memcached) | the object cache, key `osec_cache_osec-compiled-<blog_id>.css` | `<link>` to `?osec-css-cache=<hash>`, served by PHP |
| APCu (no writable folder, no object cache) | APCu, keys prefixed with the site URL hash | `<link>` to `?osec-css-cache=<hash>`, served by PHP |
| DB (neither) | option `osec_cache_osec-compiled-<blog_id>.css`, **autoload off** | same route |

- **The option `osec_css`** (autoloaded) holds `[engine, root, file, ver]` of the last compile; `ver` is the first 7
  characters of the CSS md5, so the URL changes only when the CSS does. It replaced the 1.1.x option
  `osec_compiled.css` and the `osec_file_cache__*` index rows (deleted on upgrade; the 1.1.x files a week later,
  because pages cached with old HTML still link them).
- **The URL is built on every page view** from the stored root (`CachePath::path_to_url()`: uploads, `wp-content`, the
  WordPress folder, `DOCUMENT_ROOT`), never stored. A missing file, or a file outside all of them, links the route.
  Page views test no folders.
- **The route reads the engine named in `osec_css`**, not the one the factory would pick today.
- **Inline variant**: with `render_css_as_link` off (and no file URL) or `OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST`,
  the CSS is printed as `<style id="osec-frontend-css-inline-css">` instead of a link.

**Where on the page** - `add_link_to_html_for_frontend()`:

- **Early**, on `wp_enqueue_scripts`, for pages known to show a calendar (`page_shows_calendar()`: calendar page,
  single event, singular content with the shortcode or the `osec-calendar-classic` block): in `<head>`.
- **Late**, by every calendar while it renders: still in `<head>` before `wp_head` (calendar page route, block themes),
  otherwise **with the footer styles, as a direct child of `<body>`**. That is the case for calendars in widgets,
  template parts, patterns, reusable blocks and page builders on classic themes (H9: not detected early).
- Requested twice, the CSS is printed once. Before 1.2.0 the inline variant hooked onto `wp_head` after `wp_head`
  had run on classic themes and printed **nothing** (C3).

**Consequences to keep in mind:**

- **Any JS that clears, replaces or detaches the body can destroy the calendar's styling** when the CSS came late
  (footer styles are children of `<body>`). This is what made the print button produce an unstyled page
  (`handle_click_on_print_button` in `public/js/pages/calendar.js`; fixed by detaching everything *except*
  `style, link, script, noscript, template`). Exclude non-rendered elements, or work on a container.
- **A local `OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST` flips the dev site to the inline variant** and compiles on
  every request. Check it first when a CSS bug reproduces in one place and not the other:
  `curl -s <url> | grep -c 'id="osec-frontend-css-inline-css"'` (1 = inline, 0 = link; without the `id=` the grep
  also matches the comment WordPress appends, so it counts 2). It also makes
  `tests/Unit/ConstantsTest.php::test_is_less_debug_disabled` fail locally (expected; CI is unaffected).

## CSS Compile Caching

**When the CSS is compiled:**

| Trigger | When | Notes |
|---|---|---|
| Theme Options save | in that request (`invalidate_cache()`) | ignores lock and backoff; the admin sees the error at once |
| Theme switch, activation, plugin update | next request's `init` (`verifyCache()` → `compile_flagged()`) | `request_compile()` sets the flag `osec_invalidate_css_cache` and ends the backoff |
| No CSS stored (fresh install, file lost) | the CSS route `?osec-css-cache=` | answers a `no-store` comment while it cannot compile |
| "Clear all caches" (cache report) | in that request (REST `POST osec/v1/cache/clear`) | compiles in memory first, stores, then clears the other copies; a LESS or write error keeps the previous CSS and says so |

- **One compile at a time**: lock `osec_css_compile` (`ExecutionLimitController`, 120 s). A request that cannot get
  it skips (flag kept; route: `no-store` comment).
- **Backoff**: a failed automatic compile sets the transient `osec_css_compile_failed` for 30 minutes; flag and route
  wait for it. Every admin action and every successful compile ends it.
- **A successful compile clears the flag.** Before 1.2.0 it set it again, so everything compiled twice (D-CSS2).
- **Overlapping compiles: the later start wins.** `osec_css` stores when its compile *started*; a compile that
  started earlier is not stored (a visitor's route compile cannot overwrite a newer Theme Options save). The flag holds
  the time `request_compile()` set it; a compile clears it only if it was set before the compile started, so a theme
  switch during a running compile still gets its own compile. Both are read from the DB (`$wpdb`), because the
  option caches of the request do not see what other requests wrote.
- **A failed compile never removes working CSS**: the CSS and `osec_css` are written only after a successful compile.
- **WP-CLI and cron have their own APCu.** When the CSS would go to APCu, they leave the compile to the next web
  request (flag kept); with the file engine or an object cache (shared with web requests) they compile normally.
- **Why transients only with an object cache**: without one, a transient without expiration is an autoloaded option
  row - 400 KB on every page. The DB engine writes its own row with autoload off instead.
- **Loading WordPress is never read-only for the CSS**: any request that boots WordPress, WP-CLI included, compiles
  when the flag is set (incident 2026-10-02). Read cache state with `mysql`, not WP-CLI.
- `get_compiled_css()` keeps the CSS of the current request in a property; nothing else outlives a request except
  the engines and the browser cache.

**The browser cache**: the static file is linked with `?ver=<hash>`; the route sends a one-year `max-age` and an
ETag from its `?osec-css-cache=` value, and answers weak ETags (`W/"…"`, sent back after gzip) with 304. The URL
changes with the CSS, so an edit is never hidden by the browser cache once it compiled.

**If a LESS/theme-CSS edit doesn't seem to take effect**, it is never PHP opcache (`.less` files are read as text).
Check, in order:

1. **Did it compile?** `mysql -e "SELECT option_value FROM wp_options WHERE option_name='osec_css'"` - the `ver`
   changes with the CSS. Compile with Theme Options → Save, or "Clear all caches" in the cache report (Settings).
2. **Which engine?** `engine` in `osec_css`. With `file`, read the file itself
   (`wp-content/uploads/osec_cache/css/osec-compiled-1.css` on the dev site) - diff it to see
   what compiled. With `apcu`, the CSS lives in php-fpm's APCu: `supervisorctl restart php-fpm` clears it; a
   `wp eval 'apcu_clear_cache();'` does not (own CLI APCu).
3. **Backoff after a failure?** A notice in wp-admin carries the LESS error; the transient
   `osec_css_compile_failed` blocks automatic retries for 30 minutes. Saving Theme Options compiles anyway.

**Escape hatches**: `OSEC_ENABLE_CACHE_FILE` `false` keeps the CSS in the object cache, APCu or the database and
serves it through the route (H12), e.g. when static files misbehave on a host - Twig still uses files;
`OSEC_ENABLE_CACHE_TRANSIENT` `false` skips the object cache (without one it has no effect);
`OSEC_ENABLE_CACHE_APCU` `false` skips APCu. `OSEC_FILE_CACHE_DEFAULT_PATH` moves the file cache (CSS `css/`, Twig
`twig/site-<id>/`). All take effect at the next compile. **Set them in `constants-local.php`, never in the tracked
`constants.php`.**

**Twig cache**: compiled templates (PHP files) next to the CSS in the site's uploads folder
(`uploads[/sites/<id>]/osec_cache/twig/`), or in the override (`twig/site-<id>/`, as it may be shared by a network),
else none - never the plugin folder (replaced by updates), independent of `OSEC_ENABLE_CACHE_FILE`. Until 2026-10-03
the plan had `wp-content/cache/osec/` (D2b); moved to uploads per the review guideline (plugin data in uploads),
accepting that some security scanners flag PHP files there. The folder found is stored in the setting `twig_cache` and
rescanned when it is no longer writable, on upgrade, by "Check again"/"Clear all caches", and once an hour while none
was writable (transient `osec_twig_cache_rescan`). WordPress deletes a site's uploads with the site; its override
folder is removed on `wp_uninitialize_site`.

**1.1.x leftovers**: the old upload cache folder `open_source_event_calendar_cache/` stays after the upgrade (pages
cached with old HTML link its CSS) and is removed a week later (`osec_css_legacy_cleanup`) or by "Clear all caches" -
unless `OSEC_FILE_CACHE_WP_UPLOAD_DIR` was set to that old name (`CachePath::legacy_upload_dir()`).

**Multisite caveat (H10)**: OSEC keeps per-process singletons. A plugin that calls `switch_to_blog()` and renders
OSEC output for another site in the same request gets this site's cache paths; the APCu prefix follows the site.

## Base Font Size (Theme Setting)

The "Base font size" option (Calendar Theme Options) compiles to `@font-size-base`
(`@baseFontSize`, set in `public/osec_themes/vortex/less/user_variables.php` /
`user-variable-map.less`) and is applied as a literal, LESS-compiled px value on the
plugin's own wrapper element (`.timely`, via `bootstrap/scaffolding.less` imported inside
`.timely { }` in `style.less`) - not on `<html>`.

**Each theme ships its own default for it** (`user_variables.php`: vortex `13px`, umbra `0.8rem`;
plana `1em`, hidden from Theme Options since 1.2.0 - plana follows the site's font size presets), so *switching theme changes the base font size* unless a value is saved in
`osec_less_variables`. Rendered box heights then shift - don't read that as a LESS edit having
broken something. **Check the base font size after every theme switch**, and when comparing two
themes, pin them to the same value so only one variable moves.

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

**Special Bootstrap setup: `em`/`rem` bases.** The setting accepts `px`, `em` and `rem` (`rem` since `12bb14b8`,
2025-03; umbra defaults to `.8rem` since `5e4391ad`, plana to `1em` since `fe488d9a`). OSEC's own rules were made
unit-safe by multiplying instead of adding `px` (`12bb14b8` buttons in `style.less`, `08e319ef` event sizes,
`a58d837b` component sizes). `bootstrap/variables.less` is unchanged since the 2014 import: its `ceil()`/`floor()`
and `px` additions (`@line-height-computed`, `@input-height-*`, `@font-size-h1..h6`, `@font-size-large/small`) only
work with a `px` base. Measured 2026-10-23 by compiling twice with base `x` and `2x` (what does not double is broken):
plana `1em` 17 broken declarations (inputs `15em` high, `-lg` buttons and `.timely h1/h4` `2em`, dividers `-.5em`),
umbra `.8rem` 14 (`.timely h2` smaller than `h3`). No frontend template uses those rules; plana child themes or
third-party markup inside `.timely` would hit them. Fix options and status: `.claude/plans/plana-font-sizes.md` (B).
The short version for developers sits as a comment at `@font-size-base` in `vortex/less/user-variable-map.less`.

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
output in the CSS file (`osec_css` names it) - a plausible-looking wrong number is the normal
failure mode, not a compile error.
