=== Open Source Event Calendar ===

Tags: calendar, events, ics, ical importer
Requires PHP: 8.2
Requires at least: 6.6
Tested up to: 6.9.1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://github.com/digitaldonkey/open-source-event-calendar
Domain Path: /languages
Author: digitaldonkey, Time.ly Network Inc
Author URI: https://github.com/digitaldonkey
Contributors: digitaldonkey, hubrik, vtowel, yaniiliev, nicolapeluchetti, jbutkus, lpawlik, bangelov
Donate link: https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6
Text Domain: open-source-event-calendar
Stable Tag: 1.0.11
Version: 1.0.11

An event calendar with native iCal / ICS import and export

## Features
All features are provided in their entirety. No features are locked behind any add-ons.
- **Full iCal / ICS import & export**
  - Automatically import external calendars
  - Categorize and tag imported feeds
- **Recurring events**, including complex recurrence rules [(RFC 5545)](https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html)
- **Filtering** by category and tag
- **Calendar sharing** with Google Calendar, Apple iCal, Outlook, and any other system that accepts iCalendar (.ics) feeds
- **Month, week, day, and agenda views**
- **Upcoming Events** Gutenberg block
- Direct links to **filtered calendar views**
- **Color-coded events** by category
- **Featured event images** and category images
- **SEO-optimized** event pages
- **Mobile-friendly** and responsive layouts
- ~~Embedded **Google Maps** for~~ event locations (Broken, plans to implement OpenStreetMap embedding)
- **Theme options** to customize your calendar appearence (based on bootstrap 3)
- Your calendar can be embedded into a WordPress page without needing to create template files or modify the theme.

---

## Import & Export (iCal / ICS)

Osec offers **full ics/ical support**. You can import events from other calendars and offer users the ability to subscribe to your calendar.

Importing and exporting iCalendar (.ics) feeds is one of the strongest features of the Event Calendar system. This allows you to manage your websites calendar by providing a public calendar from your Google, Apple or other calendar management software.

You can even send events from a specific category or tag (or combination of categories and tags).

---

## Blocks

You can embed the calendar by adding a **OSEC Calendar Block** to any page or post. Alternatively there is a shortcode available.

> [!WARNING] 
> At this time, only **one calendar per page or post** is supported.

On the long run it's planned to have a Rest API to allow the calendar being rendered with more modern frontend tools than the current, outdated, but nice old Bootstrap 3 stuff.

### Shortcodes

#### Calendar Views

    [osec]                       // Default view per settings
    [osec view="monthly"]
    [osec view="weekly"]
    [osec view="agenda"]
    [osec view="daily"]

#### Filtering

**By category**

    [osec cat_name="Holidays"]
    [osec cat_name="Lunar Cycles,zodia-date-ranges"]
    [osec cat_id="1"]
    [osec cat_id="1,2"]

**By tag**

    [osec tag_name="tips-and-tricks"]
    [osec tag_name="creative writing,performing arts"]
    [osec tag_id="1"]
    [osec tag_id="1,2"]

**By post ID**

    [osec post_id="1"]
    [osec post_id="1,2"]

---

## Requirements

- WordPress: 6.6 or newer
- PHP:
  - PHP 8.2+ required for development
  - PHP 8.1 may work for production builds when installed with `composer install --no-dev`

## Installation
Install as any other plugin, or from GitHub with the following steps:
1. Download .zip from release tab on right
2. Upload .zip to WordPress Plugins tab

### Setup:
1. Open the plugin settings page and save once
2. Configure:
  - Timezone
  - UI date formats
  - Week start day

3. Review `WordPress → Settings → General` for output date formats.
4. (Optional) Override constants file:
Copy [constants-local.php.example](https://raw.githubusercontent.com/digitaldonkey/open-source-event-calendar/refs/heads/master/constants-local.php.example) and save as `constants-local.php`

To remove all plugin data on uninstall, set: `define('OSEC_UNINSTALL_PLUGIN_DATA', true);`

---

## Languages

OSEC supports multiple languages

## This Is a Fork

OSEC is a fork of the GPL licensed plugin All-in-one-Event-Calendar by Timely. At it's time a great plugin with a solid but unmaintainable codebase (not all required developer tools where opensourced).

In later releases of the original softeware was deprived of core feature: Importing iCal feeds in favor of a service provided by Timely.

If you love truly open source software and don't mind to get your hands dirty you should join here. Free people need free software to manage and share events in a selfhosted manner.

## Migration Notes
Database structure is not fully compatible with All-in-One Event Calendar v2.3.4

Migration may be possible with manual effort

A standardized upgrade path may be developed if there is demand and contributions

See this [wiki](https://github.com/digitaldonkey/open-source-event-calendar/wiki/migration-from-all%E2%80%90in%E2%80%90one%E2%80%90event%E2%80%90calendar) for currently known information on migrating.

---

## Development & Support

The principle behind this plugin is to be Open Source. Get in touch on [GitHub](https://github.com/digitaldonkey/open-source-event-calendar) to report issues, propose feature enhancements, and get general guidance for contributing.

Writing this fork was [a huge effort](https://github.com/wp-plugins/all-in-one-event-calendar/compare/master...digitaldonkey:open-source-event-calendar:master).

Digitaldonkey believes everybody should be able to set up and manage public calendars. 

If you are implementing this plugin for others you should support ongoing development with a [donation](https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6) or [contribution](https://github.com/digitaldonkey/open-source-event-calendar/issues). 

[Be a maker](https://dri.es/solving-the-maker-taker-problem)😀

Those wishing to contribute to the development of this project, please see the [Development Guide](https://github.com/digitaldonkey/open-source-event-calendar/blob/master/.github/CONTRIBUTORS.md) for more information.

## Frequently Asked Questions

### "I really need feature XYZ"

Let's draft it out on [GitHub](https://github.com/digitaldonkey/open-source-event-calendar). You could donnate/pay me development time to get it contributed. Invoices possible. Or feel free to implement the requested feature yourself and create a Pull Request for it.
I may also provide paid support.

## Future plans (order irrelavent, features not guaranteed)
- Remove broken Google Maps integration and replace with OpenStreetMaps [(#5)](https://github.com/digitaldonkey/open-source-event-calendar/issues/5)
- Create modern looking theme to replace outdated default [(#2)](https://github.com/digitaldonkey/open-source-event-calendar/issues/2)

---

## Screenshots
1. Month view
2. Week view
3. Agenda view
4. Calendar Block UI
5. Manage iCal Feeds
6. Recurring Events
7. Cache Settings
8. Mobile Agenda View

## Changelog

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
