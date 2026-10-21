---
paths:
  - "src/App/Model/PostTypeEvent/**"
  - "src/App/Model/IcsImportExportParser.php"
  - "src/App/Model/ImportExportParserInterface.php"
  - "src/App/Controller/FeedsController.php"
  - "src/App/Controller/ImportExportController.php"
  - "src/App/View/RepeatRuleToText.php"
  - "src/WpCli/**"
  - "tests/Unit/App/Model/**"
---

<!-- Loaded when working on events, recurrence or ICS feeds. Moved from CLAUDE.md. -->

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
  `recurrence_dates` (RDATE) follows the same convention since `45f0e747`; the import reads RDATE and EXDATE through
  `IcsImportExportParser::recurrence_dates()` (a DATE or floating value keeps its date). Stored alone, the dates
  also stand in as the rule `RDATE=<dates>` (the editor's "custom dates"); next to a real RRULE both are kept.
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
