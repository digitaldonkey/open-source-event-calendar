= 1.2.0 =

**Plana appearance settings**
- Colours, backgrounds and lines as settings on the Theme Options page
- Text sizes and agenda spacing as settings
- Defaults follow the colours and font sizes of the WordPress theme
- A setting takes a colour or size, a theme variable or another setting
- Each setting shows its CSS variable and the value it resolves to
- Values that do not work are refused and reported
- Font size and font come from the site; no longer offered for plana

**Faster and lighter**
- Icons are inline SVG instead of icon fonts, in the calendar and in wp-admin
- Calendar stylesheet down from 406 to 227 KB (gzipped 137 to 30 KB)
- Stylesheet served as a static file from wp-content/uploads/osec_cache/
- From memory with a persistent object cache (Redis, Memcached) or APCu
- Compiled once per change; a failed compile keeps the previous CSS
- Compiled templates in wp-content/uploads/osec_cache/twig/, kept on plugin updates
- Cache Report: where CSS and templates are, new "Clear all caches" button
- Instant back/forward navigation (back/forward cache)

**Calendar views, block and print**
- Calendar width follows the page or block around it
- Full-width calendar block: edge padding, agenda with a readable maximum width
- Calendar block: agenda expanded or collapsed per block
- Calendar block: print icon shown, hidden or as in the settings
- Calendar block: settings open and close from the header
- "Show all 24 hours" stays on for the next views
- Subscribe menu with Google, Microsoft and Apple logos
- Selected categories and tags show a remove x
- "Edit" links in every view
- Print: month, week and day fill the page on any paper size
- Print: multi-day bars in month view stay on one line
- Accessibility: names for icon-only buttons
- Complete schema.org/Event microdata on the event page

**Events, feeds and database**
- Emoji and other 4-byte characters in events and feeds
- Older tables converted from a button in Settings
- Longer event fields (venue, address, URLs, iCal UID and others)
- A value that still does not fit is shortened or left out, with a notice
- New setting "Trust this server's CA certificates for feeds"
- Event list in wp-admin: all-day label, date under the title on narrow screens
- Event list in wp-admin: event times in the backend date and time formats

**Translations**
- Updated translations

**Fixes**
- Security: imported venue, address, contact, cost and URLs stored as plain text and safe URLs
- Security: event titles filtered with kses before the calendar views print them
- Security: JSONP callback accepts only a JavaScript function name
- Security: "Check again" in the cache report needs capability and nonce
- Security: admin output lets no script or event handler attributes through
- Event and feed fields were saved and shown escaped twice ("D&amp;D")
- Fields stored that way can be repaired from a notice or with `wp osec repair-escaping`
- Event image alt text escaped twice
- Feed import: categories and tags of the feed assigned again #62
- Feed import: categories and tags set by hand are kept #62
- Feed import: duplicates of events with a quote or backslash in the iCal UID
- Feed import: a timeout could block the feed until the lock was removed by hand
- Feed import: a too long field failed the save and stopped the import
- Feed import: fatal error on a UTC offset without a named time zone
- Imported events link their original again ("View original")
- Imported events without a contact name show the organizer again
- Excluded dates on SQLite and outside the site time zone #44
- A plain numeric cost ("10", "0") broke the event
- Settings lost backslashes and line breaks
- Saving a custom frontend date format could end in a fatal error
- Ticket and contact URLs lost percent-encoded characters
- "Got it – dismiss this" on admin notices did nothing
- Custom calendar themes did not compile after switching to them
- A calendar theme was not found after its folder moved
- An invalid calendar theme setting caused a fatal error
- Category image missing from calendar views (since 1.1.9)
- "Something went wrong while fetching events" after Back following a view change
- Calendar views led to a deleted page after the calendar page changed
- Plana: changing the view loaded a new page
- Month view: multi-day bars crossing the same day covered each other
- Month view: titles overlapped with "Word-wrap event stubs in Month view"
- Month view: event text clipped at the bottom
- Plana month view: events without category colour use the link colour again
- Agenda: "Expand all" / "Collapse all" lost on the next date
- Agenda: an empty agenda showed the current month instead of the requested one
- Date picker: picking the shown date again did nothing
- Date picker: month and week views start on the right day
- Date picker: the agenda names the picked date
- Date picker links changed meaning with the input date format setting
- Print: agenda images floated out of their event
- Print: plana's event page moved the whole details list to the next page
- Map script errors on events without a map
- Calendar block editor strings were not translatable
- Event editor styles loaded on every admin page #65
- "Remove all data": events left behind on SQLite and with a table prefix other than wp_
- "Remove all data": other plugins' options starting with "osec" were removed
- Deactivation emptied the server's whole APCu cache
- Stylesheet linked with a broken URL after WP-CLI or cron compiled it
- Shortcode or block on a classic theme got its stylesheet late or not at all
- Broken "Preview" link in the time zone notice
- PHP 8.1 deprecation for unknown UTC offsets

**For developers and custom themes**
- Internet Explorer is no longer supported
- Custom themes: icon classes outside the shipped set show no icon
- Custom themes: add own icons in `override.less` (see `osec-icons.less`)
- The precompiled theme CSS fallback is gone; every theme compiles on first use
- New template `event-popup.twig` for the month, week and day popups
- Event image is `event.image_html`; `event.avatar_not_wrapped` is deprecated
- Frontend templates render with twig.js 3 and autoescape
- Fields printed with `|raw` must be safe server-side
- WP-CLI: `wp osec theme list|activate`
- WP-CLI: `wp osec content generate` (test content)
- WP-CLI: `wp osec repair-escaping`
- `osec_pre_save_settings` receives the values unslashed once
- `OSEC_FILE_CACHE_DEFAULT_PATH` is empty by default
- `OSEC_ENABLE_CACHE_FILE` no longer turns off the template cache
- New constant `OSEC_ENABLE_CACHE_TRANSIENT`
- `public/js/scripts/` is gone; the four used files moved (check `get_module()` users)

= 1.1.16 =
- Security: title and description of events imported from feeds are sanitized, whoever runs the import. Every earlier version stored them unfiltered when a feed was added or refreshed by an administrator (since All-in-One Event Calendar 1.0, 2011, which this plugin is based on) or updated with `wp osec feed update` (1.1.15)

= 1.1.15 =
- Print support for all calendar views with a print header; overlapping events print side by side, busy days as a list; category colors print as event borders; a calendar block can hide the print icon #55
- Fix: many overlapping events in week and day view ran into the next day's column
- Week and day grid follows the window width
- Month view can show start and end time above the event title (new option)
- Fix: recurring events drifted an hour at every daylight saving change (since 1.1.5). Existing events are corrected when saved again, or all at once with `wp osec event regenerate`
- A recurring event creates at most 2000 occurrences; rules that cannot be expanded are rejected and reported
- WP-CLI: `wp osec event regenerate`, `wp osec feed update`, `wp osec feed list`
- Feed import: events removed from a feed are deleted again when "keep old events" is off, EXDATE, RDATE and moved occurrences land on the right day, every RDATE is imported, a failed import no longer blocks the feed for 24 hours
- Feed fetches verify TLS certificates again; a self-signed feed now fails with "cURL error 60" (see FAQ)
- The calendar block works in the WordPress 7 editor
- Leaflet maps are shipped with the plugin instead of loaded from unpkg.com
- Fix: agenda paging got stuck or led to empty pages
- Fix: category and tag filters combined with OR showed draft and private events
- Fix: agenda view showed the entire homepage in the expanded toggle #59
- Shortcode taxonomy filters warn about unknown terms instead of silently showing everything #53 #54 (thanks @xnaveira)
- Fix: error when meta boxes are added on other post types #63
- Fix: "Default calendar start date" was ignored, dates like 21/9/2026 showed as 1970, the date picker was off by a day and ignored "week starts on"
- Fix: events deleted outside wp-admin left their occurrences behind
- Database schema updates no longer report false failures

= 1.1.14 =
- Fix: an exception rule with an ordinal BYDAY did not parse as recurring rule #45
- Fix: default fallback image issues #49
- Fix: agenda view still listed an all-day event of yesterday #51
- (first built as 1.1.12, which never reached WordPress.org)

= 1.1.13 =
WP 7.1 compatibility

= 1.1.11 =
Fix: Json displayed instead of coast #52

= 1.1.10 =
- Include ongoing events in ical export #47
- Display hide cost, enable to hide cost when importing events via feed #42
- Fix double escaped Urls in some links
- Introduce OSEC_LEGACY_COST_SERIALIZED and to support fallback on Ai1ec serialized DB cost format #46

= 1.1.9 =
- Reword Subscribe button text  on single, fix subscribe buttons display and JS loading
- deploy development releases to WP.org
- Fixed ICS feed not refreshing #43
- Allow to select a post status for feed imports
- add blueprint
- Fix some Url/ampersans issues around edit instance
-  fix Aspect ratio in Event Popups
- Rework image fallback mechanisms. Add option for default fallback image

= 1.1.8 =
- Fix Location, GEO and Contact - Events from ICS feed lose their location #40
- Weekly/Monthly view not respecting the "week starts on day" when set to Sunday #38
- Add iCal ORGANIZER prop import/export
- fix HTML display in iCal export
- Unify how subscribe buttons are displayd and oepened in apps
- Add image as attachment to ical export.

= 1.1.7 =
- plugin-check waste of time release only

= 1.1.6 =
- Add unit tests for iCal import dates handling and fix date issues related to iCalcreateor 2.41 upgrade
- Extended ical_feed_url max length from to 768 chars #34
- Timezone storage and display improved
- Remove feed 'Keep Events' fixed. Enhanced filter so a filter relating removed feed is kept
- End times short by a minute. Fixes #33
- Checked WordPress 7.x compatibility
- Buffers are not cleared before rendering Ical or Json. Caused wrong HTTP content type for Json/ical in sad circumstances
- Adapt to WP-7 CSS change
- Fix Calendar url decoding. Fixes #35
- icalCreator feed import refactored, added tests for dates and reuoccurence handling. Added php-rrule to circumvent Iclalcreator API changes.
- Fix: parent event not found
- Hide date boxes in Agenda views on screens <16rem/480px
- Remove Add to timely calendar from subscribe options
- Imported events now reflect overrides with RECURRENCE-ID
- Improve toggle-all button display in plana theme
- 1.1.5 was dropped due to deployment issues

= 1.1.4 =
 - Upgrade Icalcreateor to 2.4.1
 - Cleaned some PHP-8.5 deprecation messages.

= 1.1.3 =
 - minor reponsive improvements in Plana theme
 - Removed: Serverside Javascript compression

 = 1.1.2 =
- Fix webcal URLs are not generated when OSEC_SCRIPT_URL uses https. #18
- Fix: Tags not working in Edit form
- Agenda-forward-and-back-buttons-are-not-transative #32

= 1.1.1 =
- Change default theme to plana
- Reworking Plana theme

= 1.1.0 =
- OpenStreetMap support including Geocoding in Edit form.
- Added full schema.org/Event support for single Event page using plana theme. Check [search engine readability](https://validator.schema.org).
- Reworked backend forms: Event edit, Themes, Theme options, Settings, Feeds.
- Reworked PLANA theme fit better into current WP standard themes.
- Removed outdated Google Maps support.
- Reworked Readme.md to look better on github. Build tool to generate Readme.txt from Readme.md, CHANGELOG.md and constants.
- Automated WordPress plugin directory deployment
- Excerpt support: Enables to write Event summary and content separately.
- Ability to enable/disable features: Location, Excerpt, Maps, Coast, Comments, Shortcodes.

= 1.0.11 =
- Beautified Theme options admin page
- Adjusted information text on settings page
- Back button url is not stored in cookie die to missing div#id. Fixes #16
- Fixing Can not selext Sunday in admin page settings fixes #14
- Add legacy Uri support for Ical feeds. Maybe Fixes: #12
- Cleanup Metaboxes, fix Metabox-Editing, remove unnecessary constants.

= 1.0.10 =
- Beautified Admin Theme admin page
- Twigify Admin Theme theme-row.
- Add more plugin-check fixes, escape shortcodes,

= 1.0.7 =
- Fix additional redirect happening due to trailing slash in Link
- Fix custom font. Closes #8
More WP plugin check work.
- Renaming capabilities with prefix
- Rename Taxonomies: events_categories, events_tags, events_feeds to osec_events_categories,  osec_events_...
- Fix some default value issues, timezone default
- clean up translations, nonces, prefixes
- migrate php templates to twig
- Rework/fix: robots.txt generation, exact_date, get_exact_date, variable variants,
- Updates: WP phpcs config, tools (npm) and composer updates


= 1.0.4 =
- Fix: Move content display out of OSEC block
- Fix: subscribe display settings inverted.
- Disallow direct file access
- Renaming capabilities consistently
- composer upgrade

= 1.0.3 =
- Allow all data attributes in Kses. Fixes persisten admin notices can not be dismissed.
- fix overriding time/date-separators using i18n

= 1.0.2 =

- Rework translation at German example (I love Loco Translate)
- Fixed: Category image will now be used as default featured image in single event view.
- Fixed: Function _load_textdomain_just_in_time was called incorrectly.
- If toggle in Agenda view is disabled link to the single Event on title click.
- Fixed: "Click on title toggles when toggler is disabled."
- Fixed: OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST does not work but lead to undefined variables.
- Enabled disabling the Print icon in settings.
- Improve (responsive) Linebreaks in date views with non-breaking spaces.
- Simplified Plana theme to apply more WP global styles.
- Update Twi-js tooling enables updating Twig-JS based templates for frontend-rendering
- Simplify plana singe page template
- Fix Category image upload UI and add option to use fallback image if no post featured image is set.

= 1.0.1 =
- Add more integration tests

= 1.0.0 =

* Rework query params, fixed date pagers
* Reworked date display to be consistent for Single and multiday and Allday Events.
* Add flexible width Gutenberg Calendar Block
* Removed Widget and Agenda Widget.
* Reworked date display to be consistent.

= 0.9.0 =

* Added Sourcemaps for CSS (requires OSEC_DEBUG )
* Documented hooks and actions (@see hooks-and-filters.md)
* Added WP > 6 compatibility
* Reworked plugin using PHP-Composer, Added PHP8 compatibility. Replaced Registry class loading with PHP use-statements
* Removed tons of unused, service integration and legacy code.
* Rewrote install/Uninstall/bootstrapping. You can purge all data on uninstallation by setting OSEC_UNINSTALL_PLUGIN_DATA to TRUE.
* Cleand up unclear date formatter settings. Frontend Date formats are now defined/changed in WordPress settings-general page.
* Removed legacy theme support, merged chains of purposeless inherited classes, renamed many things hopefully improving code clarity and maintainability.
* Fixed Week-view date selection.
* Fixed/rewrote caching system. Added APCU caching.
* Added Test environment working well in ddev. Based on WP handbook standards [plugin-unit-tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/).
* Upgrade strings to match current translation requirements.
* Solving WordPress "Plugin Check" minimum requirements.
