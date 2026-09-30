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
