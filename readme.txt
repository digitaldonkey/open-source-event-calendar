=== Open Source Event Calendar ===
Contributors: digitaldonkey, hubrik, vtowel, yani.iliev, nicolapeluchetti, jbutkus, lpawlik, bangelov
Tags: calendar, events, ics, ics feed, wordpress ical importer, google, block
calendar, ical, iCalendar, all-in-one, events sync, events widget,
calendar widget, Osec, Open source Event Calendar
Requires WordPress at least: 6.6
Tested up to: 6.7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GNU General Public License, version 3 (GPL-3.0)

A calendar system with many views, upcoming events widget, color-coded
categories, recurrence, and import/export of .ics feeds.

== Description ==

The [Open Source Event Calendar]() is based on the Timely All-in-one-event-calendar version v2.3.4. by [Timely](http://time.ly/). The calendar system combines clean visual design with a basic set of features to create share and aggregate Events in WordPress. Ical import is possible.

This Plugin is open source software in traditional sense. I pledge this plugin will not urge you to connect to any proprietary/payed service to use described features.

Osec calendar is most likely not compatible with any previous All-in-one-event-calendar release, but with some effort you might get it working.

= Import and Export Events =

Osec offers full ics/ical support. You can import events from other
calendars and offer users the ability to subscribe to your calendar.

Importing and exporting iCalendar (.ics) feeds is one of the strongest
features of the Event Calendar system. Enter an event on
one site and you can have it appear automatically in another website's
calendar. You can even send events from a specific category or tag (or
combination of categories and tags).

= Features =

* Import and Export (!) of Ical feeds without additional addons.
** Import other calendars automatically to display in your calendar.
** Categorize and tag imported calendar feeds automatically.
* Recurring** events including complex patterns.
* Filtering** by event category or tag.
* Easy **sharing** with Google Calendar, Apple iCal, MS Outlook and
any other system that accepts iCalendar (.ics) feeds.
* Embedded Google Maps.
* **Color-coded** events based on category.
* Featured **event images** and **category images**.
* **Month**, **week**, **day**, **agenda** views.
* **Upcoming Events** widget.
* Direct links to **filtered calendar views**.
* **Theme** options to customize your calendar appearence (based on bootstrap 3)
* Each event is SEO-optimized.
* Each event links to the original calendar.
* Your calendar can be embedded into a WordPress page without needing
to create template files or modify the theme.

= Requirements =

* PHP >= 8.2 is currently required at least for development. PHP 8.1 may work too with release version (using `composer install --no-dev`).

= Languages =

You may change the plugin textdomain in constants.php to use the following languages.

* German
* French
* Russian
* Italian
* Dutch
* Japanese
* Portuguese
* Swedish
* Polish
* Danish
* Spanish
* Bulgarian
* Greek
* Hungarian
* Latvian

= This is a fork =

This is a fork of the GPL licensed plugin All-in-on-Event-Calendar by Timely.
At it's time a great plugin with a solid but unmaintainable codebase (not all required developer tools where opensourced).

In later releases of the original softeware was deprived of core feature: Importing iCal feeds in favor of a service provided by Timely.

If you need a professionally supported plugin you should consider using the [original all in one event calendar](https://wordpress.org/plugins/all-in-one-event-calendar/)

If you love truly open source software and don't mind to get your hands dirty you should join here. Free people need free software to manage and share events in a selfhosted manner.

**Please do not ask for support at Time.ly for this Plugin**.

Source and developer support you can find at https://github.com/digitaldonkey/open-source-event-calendar

== Other notes ==

There is no Block provided yet :(
Plan is to provide a simple shortcode-based Block before releasing 1.0.

For now it might be better to use "Shortcodes".

You may use the Widget with help of [X3P0 - Legacy Widget](https://wordpress.org/support/plugin/x3p0-legacy-widget/), but it is considered deprecated and will be removed in favor of "Shortcode-Block".

= Shortcodes =

* Monthly view: **[ai1ec view="monthly"]**
* Weekly view: **[ai1ec view="weekly"]**
* Agenda view: **[ai1ec view="agenda"]**
* Posterboard view: **[ai1ec view="posterboard"]**
* Default view as per settings: **[ai1ec]**

* Filter by event category name: **[ai1ec cat_name="Holidays"]**
* Filter by event category names (separate names by comma):
**[ai1ec cat_name="Lunar Cycles,zodia-date-ranges"]**
* Filter by event category id: **[ai1ec cat_id="1"]**
* Filter by event category ids (separate IDs by comma):
**[ai1ec cat_id="1, 2"]**

* Filter by event tag name: **[ai1ec tag_name="tips-and-tricks"]**
* Filter by event tag names (separate names by comma):
**[ai1ec tag_name="creative writing,performing arts"]**
* Filter by event tag id: **[ai1ec tag_id="1"]**
* Filter by event tag ids (separate IDs by comma):
**[ai1ec tag_id="1, 2"]**

* Filter by post id: **[ai1ec post_id="1"]**
* Filter by post ids (separate IDs by comma):
**[ai1ec post_id="1, 2"]**

== Changelog ==

== 0.9.0
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
