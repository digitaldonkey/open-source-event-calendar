# Test content

One dataset for reviewing the calendar, the plugin directory screenshots and the Playwright regression tests:
every kind of event the calendar knows, in every view, on dates around **May of next year**.

`wp osec content generate` (`src/WpCli/ContentCommand.php`, logic in `src/WpCli/ContentGenerator.php`) fills a
site with it. The command ships with the plugin; this folder (dataset, photos) does not.

```
/usr/local/bin/wp osec content generate --dataset=tests/data/content/dataset.json --site-settings
/usr/local/bin/wp osec content generate --dataset=tests/data/content/dataset.json --set=showcase --anchor=2027-05-17 \
    --theme=plana --manifest=/tmp/manifest.json
```

No purge needed: everything `generate` creates is marked, and a run replaces exactly that. It:

1. stops, changing nothing, if a category or tag it did not create has the name of a dataset one. Other calendar
   events, categories, tags and feeds are never touched; they show up in the calendar next to the test content.
   With `--only-generated` it stops if there is any (the screenshot and regression tools use it);
2. removes what an earlier run created: events, categories and tags (post and term meta `_osec_content_generated`),
   pages and images (same post meta), feeds whose URL lies below `uploads/osec-content/` with their imported events,
   and the folder `uploads/osec-content/<database>/`. Other posts, pages, media, users and the WordPress theme stay;
   no file outside that folder is deleted (a copied database's attachments point to the original site's files);
3. creates the calendar page (setting `calendar_page_id`), switches the calendar theme (deleting saved Theme Options,
   as any switch does), applies the dataset's calendar settings, then categories, tags, feeds, events and pages;
4. prints the manifest, or writes it with `--manifest=<file>`.

| Option | Meaning |
|---|---|
| `--dataset=<file>` | This dataset (or another); images are read from `images/` next to it. |
| `--anchor=<date>` | Base week of every date: default the week with 15 May of **next** year (moves every January), `today` the current week, or a date (moved to its Monday). |
| `--set=all\|showcase` | `showcase`: only the events of that group (feed events always). |
| `--theme=<name>` | Calendar theme instead of the dataset's (`wp osec theme list`). |
| `--site-settings` | Also set site title, timezone, date/time format, week start and permalink structure from `site`. Without it the site's timezone and permalinks are used. The rewrite rules are flushed in the database only; on Apache, save Settings › Permalinks once so `.htaccess` gets its block. |
| `--only-generated` | Stop, changing nothing, if the site has calendar events, categories, tags or feeds the test content did not create, so the calendar shows the test content alone. |
| `--manifest=<file>` | Write the manifest there instead of printing it. |

Why May: nothing depends on "today" (no today marker, no "now" line, no agenda cut-off inside the month), and there
is no DST change in May, so recurrences keep their times unless an event crosses DST on purpose.

## The anchor and dates

The **anchor** is Monday 00:00 (site timezone) of the anchor week: "week 0". Every dataset date is a PHP
`DateTime::modify()` chain applied to it, a string or a list applied in order:

- `"+2 days 10:00"`: Wednesday of week 0, 10:00.
- `["first day of this month", "+3 days 19:30"]`: the anchor's month.
- `["last sunday of october", "-3 days 00:30"]`: the anchor's year (DST cases).

`end`, `exdates`, `rdates` and `override.start` are relative to the event's own start. Avoid `"<day> this week"` after
a month jump (on a Sunday it goes back into the previous month); use `"first saturday of next month"` and the like.

A wall clock time in the hour repeated when DST ends (02:00-03:00 on the last Sunday of October in Europe) exists
twice; PHP picks either one depending on how the time was built. `repeated_hour` (`first` = still summer time,
`second` = standard time) says which.

## `dataset.json`

- `site`: `title`, `timezone`, `date_format`, `time_format`, `permalink_structure`, `theme` (WordPress options; only with
  `--site-settings`; `theme` is not applied by the command), `calendar_theme`, `calendar_page` (title), `settings`
  (OSEC settings, set as given).
- `categories[]`: `{key, name, color, parent?, image?}`; `parent` is a key defined earlier (sub-category), `image` a
  file in `images/` (category image, shown when an event has no featured image).
- `tags[]`: names. `venues`: `{<key>: {venue, address, city, postal_code, country, latitude, longitude}}`.
- `feeds[]`: `{name, file, categories, tags, events}`, events `{key, title, start, end, allday}` (timed ones written
  in UTC). The `.ics` goes to `uploads/osec-content/<database>/` and is imported over http, so a scheduled re-import
  finds the same events.
- `pages[]`: `{key, title, content}`, e.g. the calendar block and the `[osec]` shortcode.

### Events

| Key | Meaning |
|---|---|
| `key` | Stable ID; the manifest and the tests use it. |
| `group` | `showcase` (screenshots) or `edge` (exists to break things; review and regression). |
| `title`, `content`, `excerpt`, `status` | Post fields; `status` `draft` or `private` for events that must not show. |
| `start`, `end` | See *The anchor and dates*; a missing or `null` end makes an instant event. |
| `repeated_hour` | `first` or `second`: which of the two occurrences of a time in the repeated DST hour. |
| `allday` | `true` for an all-day event. |
| `timezone` | The event's own timezone; `start` is then that wall clock time there ("07:00 in Tokyo"). |
| `rrule` | Recurrence rule. `rrule_until`: a date (from the anchor) added as `UNTIL` at 23:59:59. |
| `exdates`, `rdates` | Excluded / extra dates, relative to the start. `rdates` without `rrule` is the editor's "custom dates". |
| `override` | `{key, title, start}`: "edit this instance" for the instance on `start`'s day: a child event linked to it, that date excluded from the series. Same day only (side finding B25). |
| `categories`, `tags` | Category keys, tag names. |
| `venue`, `map` | Venue key; `map: true` shows the map and the coordinates (keep it to one or two events). |
| `image` | Featured image, a file in `images/`. |
| `cost`, `ticket_url`, `contact` | Cost text (empty = free), ticket URL, `{name, email, phone, url}`. |

## The manifest

```json
{
  "anchor": "2027-05-17", "timezone": "Europe/Berlin", "set": "all", "calendar_theme": "vortex",
  "pages": {"calendar": "/calendar/", "block": {"id": 68, "path": "/calendar-block/"}, "shortcode": {"…": "…"}},
  "events": {"jazz": {"id": 7, "title": "Jazz in the Park", "start": "2027-05-17 19:00"}}
}
```

`start` is the first occurrence in the site timezone; titles are raw (as stored, not texturized). Feed events are
listed under their keys too. The calendar page may be `/calendar-2/` when another page already uses `/calendar/`.

## Files

| File | Role |
|---|---|
| `dataset.json` | Site settings, categories, tags, venues, a feed, events and pages. |
| `images/` | CC0 photos for featured and category images; sources and licenses in `images/SOURCES.md`. |
