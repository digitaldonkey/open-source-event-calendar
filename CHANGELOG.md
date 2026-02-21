

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
