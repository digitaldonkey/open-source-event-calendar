=== Open Source Event Calendar ===

Tags: calendar, events, ics, ical importer  
Requires at least: 6.6
Requires PHP: 8.2  
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: open-source-event-calendar
Domain Path: /languages
Contributors: digitaldonkey, hubrik, vtowel, yani.iliev nicolapeluchetti, jbutkus, lpawlik, bangelov  
Donate link: https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6
Tested up to: 6.9
Stable Tag: 1.0.10

With Osec you can create, share and aggregate and import (ical, ics) Events in WordPress Based on All-in-one-event-calendar (v2.3.4).

== Description ==

The [Open Source Event Calendar](https://github.com/digitaldonkey/open-source-event-calendar) is based on the Timely All-in-one-event-calendar version v2.3.4. by [Timely](http://time.ly/). The calendar system combines clean visual design with a basic set of features to create share and aggregate Events in WordPress. Ical import is possible.

This Plugin is open source software in traditional sense. I pledge this plugin will not urge you to connect to any proprietary/payed service to use described features.

Osec calendar is most likely not compatible with any previous All-in-one-event-calendar release, but with some effort you might get it working.

= Import and Export Events =

Osec offers **full ics/ical support**. You can import events from other calendars and offer users the ability to subscribe to your calendar.

Importing and exporting iCalendar (.ics) feeds is one of the strongest features of the Event Calendar system.  This allows you to manage your websites calendar by providing a public calendar from your Google, Apple or other calendar management software.    

You can even send events from a specific category or tag (or combination of categories and tags).

= This is a fork = 

This is a fork of the GPL licensed plugin All-in-on-Event-Calendar by Timely.
At it's time a great plugin with a solid but unmaintainable codebase (not all required developer tools where opensourced).

In later releases of the original softeware was deprived of core feature: Importing iCal feeds in favor of a service provided by Timely.

If you need a professionally supported plugin you should consider using the [original all in one event calendar](https://wordpress.org/plugins/all-in-one-event-calendar/)

If you love truly open source software and don't mind to get your hands dirty you should join here. Free people need free software to manage and share events in a selfhosted manner.

Please do not ask for support at Time.ly for this Plugin.

Source and developer support you can find at [Plugin's github page](https://github.com/digitaldonkey/open-source-event-calendar). There is also a [public CircleCI build pipeline](https://app.circleci.com/pipelines/github/digitaldonkey/open-source-event-calendar?filter=all)

== Features == 

* **Import and Exportof Ical feeds** without additional addons.
  * Import other calendars automatically to display in your calendar.
  * Categorize and tag imported calendar feeds automatically.
* **Recurring events** including complex patterns.
* **Filtering** by event category or tag.
* Easy **sharing** with Google Calendar, Apple iCal, MS Outlook and
any other system that accepts iCalendar (.ics) feeds.
* Embedded Google Maps (may be outdated)
* **Color-coded** events based on category.
* Featured **event images** and **category images**.
* **Month**, **week**, **day**, **agenda** views.
* **Upcoming Events** block.
* Direct links to **filtered calendar views**.
* **Theme** options to customize your calendar appearence (based on bootstrap 3)
* Each event is SEO-optimized.
* Each event links to the original calendar.
* Your calendar can be embedded into a WordPress page without needing
to create template files or modify the theme.

== Requirements ==

* PHP >= 8.2 is currently required at least for development. PHP 8.1 may work too with release version (using `composer install --no-dev`).

== Languages ==  

This Plugin supports multiple languages. 


== Blocks == 

You can embed the calendar by adding a **Osec Calendar** Block to any page or post. Alternatively there is a schortcode available. 

Please note that by this time (most likely) only one Calendar per page/post-List will work 

On the long run its planed to have a Rest API to allow the calendar being rendered with more modern frontend tools than the current, outdated, but nice old Bootstrap 3 stuff. 

### Shortcodes

* Monthly view: **[osec view="monthly"]**
* Weekly view: **[osec view="weekly"]**
* Agenda view: **[osec view="agenda"]**
* Posterboard view: **[osec view="dayly"]**
* Default view as per settings: **[osec]**

* Filter by event category name: **[osec cat_name="Holidays"]**
* Filter by event category names (separate names by comma):
**[osec cat_name="Lunar Cycles,zodia-date-ranges"]**
* Filter by event category id: **[osec cat_id="1"]**
* Filter by event category ids (separate IDs by comma):
**[osec cat_id="1, 2"]**

* Filter by event tag name: **[osec tag_name="tips-and-tricks"]**
* Filter by event tag names (separate names by comma):
**[osec tag_name="creative writing,performing arts"]**
* Filter by event tag id: **[osec tag_id="1"]**
* Filter by event tag ids (separate IDs by comma):
**[osec tag_id="1, 2"]**

* Filter by post id: **[osec post_id="1"]**
* Filter by post ids (separate IDs by comma):
**[osec post_id="1, 2"]**

== Upgrade Notice ==

Database structure is not fully compatible with All-in-one-event-calendar (v2.3.4). But it might be possible to upgrade with some effort. If there is demand and input a standardized upgrade path might be developed.

== Installation ==

Installation as usual

* Make sure to verify the PHP version requirements.
* After installing you need to save the plugin settings page once. Set Timezone, UI Date formats, WeekStart Day.
* Make sure to also check settings-general.php to review output date formats.
* You may add a constants-local.php file by copying [constants-local.php](https://raw.githubusercontent.com/digitaldonkey/open-source-event-calendar/refs/heads/master/constants-local.php.example) and overwrite whatever you find in open-source-event-calendar/constants.php 
* To purge all content on plugin uninstall set OSEC_UNINSTALL_PLUGIN_DATA to TRUE.


== Frequently Asked Questions ==

= How can I accelerate future development =

The principle behind this plugin is to be Open Source.
So you might get in touch on github accelerate development.

Writing this fork was [a huge effort](https://github.com/wp-plugins/all-in-one-event-calendar/compare/master...digitaldonkey:open-source-event-calendar:master).

Digitaldonkey believes everybody should be able to set up and manage public calendars. 

If you are implementing this plugin for others you should support ongoing development with a [donnation](https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6) or [contribution](https://github.com/digitaldonkey/open-source-event-calendar/issues). 

[Be a maker](https://dri.es/solving-the-maker-taker-problem) &#128512;

= I really need feature XYZ =

Let's draft it out on github. You could donnate/pay me development time to get it contributed. Invoices possible.

I may also provide paid support.
  
== Screenshots ==
1. Month view with catergory colors set
2. Week view
3. Agenda view
4. Calendar block UI
5. Manage Ical feeds
6. Reoccurring events UI (based on [iCalendar-RFC-5545](https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html))
7. Cache settings
8. Agenda view in mobile. All calendars are mobile friendly

== External services ==

This plugin connects may connect to Google maps API render event locations.
(Terms of Service)[https://cloud.google.com/maps-platform/terms] (Privacy statements)[https://policies.google.com/privacy].

== Changelog ==

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
- Allow all data attributes in Ksess. Fixes persisten admin notices can not be dismissed.
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
