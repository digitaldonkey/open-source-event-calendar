# #13: One hour before midnight test fails

- **Ticket**: https://github.com/digitaldonkey/open-source-event-calendar/issues/13
- **Type**: task (flaky integration test)
- **Status**: still valid
- **Reproduced**: no, confirmed by code analysis. A real repro needs the Selenium suite to run between 23:00 and 00:00 Europe/Berlin, or a faked clock, and the suite writes to the dev site.
- **Priority (suggested)**: low (CI flakiness only, in a narrow time window)
- **Effort (estimate)**: S
- **Related**: #44 (repeat excludes do not work in sqlite wordpress preview), #30 (wp multisite testing)
- **Processed**: 2026-09-13, re-assessed 2026-09-23
- **Ticket updated**: 2026-02-07T13:20:26Z
- **Master**: 1dffb2b6b6e0a13cab3296c22da73b0f0cae5cf1

## Summary
Between 23:00 and midnight Berlin time, the CircleCI integration tests "Add daily repeating event" and
"Delete daily repeating event" fail. The reporter's explanation: the event created by the test spans two
days, so view counts don't match. The delete test fails as a consequence of the add test.

## Evaluation
- The test (now `integration_tests/test/03_OsecPluginFrontend.spec.js:72-206`; the ticket cites the old
  `OsecPluginInstall.spec.js:280`) creates a daily repeating event **without setting a time**, so it uses the defaults.
- Defaults: `src/App/Model/PostTypeEvent/EventEntity.php:214-216`, `start = now`, `end = now + 1 hour` in
  `sys.default` timezone. CI uses `TZ=Europe/Berlin` (`.circleci/config.yml:52`), and the site timezone is set
  to Europe/Berlin by `ActivatePluginAndSettings.js:162`. Created after 23:00, each occurrence crosses midnight,
  becomes multi-day, and shows up on two days, which breaks:
  - agenda: `10 === eventCountAgenda` and `ai1ec-day` count == 10 (line ~150-160)
  - week: 7 events (line ~182)
  - day: 1 event (line ~198)
  - month: count == days in next month (line ~130)
- A second, separate time bug in the test: line 128
  `new Date(new Date().getFullYear(), new Date().getUTCMonth()+2, 0)` mixes local year with UTC month. Between
  00:00 and 01:00/02:00 Berlin on the 1st of a month, `getUTCMonth()` still returns the previous month, and around New Year the year is off too.
- `Delete daily repeating event` (line 207) only trashes the first `.submitdelete`, so it fails when the add test
  left a different state. It isn't independently broken.

## A second, unrelated bug lives in the same window (found 2026-09-23)

While chasing "daily event at 00:15 shows twice", a **real product bug** turned up that also breaks this
test, in an adjacent slice of the clock. It is *not* the multi-day-span mechanism described above.

- **Cause**: `EventInstance::process_rrule_freq()` expanded the RRULE in UTC. `new DateTime('@'.$ts, $tz)`
  silently ignores the timezone argument (PHP always yields `+00:00` for an `@` timestamp), so the intended
  conversion back to local time never ran. Occurrences therefore stepped in fixed UTC increments and drifted
  by an hour in wall clock time at every DST transition.
- **Introduced**: 5d7ea57d (2026-06-07), the switch from `RecurFactory::recur2date()` to `rlanvin/php-rrule`.
  First shipped in **1.1.5**; `EventInstance.php` is byte-identical from there through 1.1.14 and current
  release-1.1.x. Not a php-rrule bug - reproduces identically on php-rrule 2.6.0 and 3.0.0.
- **Fixed**: expand in the event's own timezone and take php-rrule's occurrences directly.
  Covered by `tests/Unit/App/Model/PostTypeEvent/EventInstanceTest.php`.

### Why it looks like a flaky-clock problem too

An hour of drift is only *visible* when it crosses a calendar-day boundary, so it needs a start time within
an hour of midnight - which, with `start = now`, means the pipeline's wall clock decides whether it shows:

| Event created (Europe/Berlin) | Symptom at the DST transition |
|---|---|
| `[00:00, 01:00)` in CEST (summer) | transition day gets **two** occurrences (e.g. 2026-10-25: 00:15 *and* 23:15) |
| `[23:00, 24:00)` in CET (winter) | transition day gets **zero** (e.g. 2027-03-28 missing entirely) |
| any other time | 1/day; the event still drifts an hour, but nothing crosses midnight and the views look fine |

So the winter variant overlaps this ticket's 23:00-00:00 window, and the summer variant sits immediately
after it. Both were invisible outside a DST transition date, which the test never navigates to.

### What this means for the plan below

- **The fix for this bug does not fix #13.** Verified by diffing the full instance list for the 23:30
  scenario before and after: identical from the start date up to the DST transition - every occurrence still
  spans midnight (`23:30 -> 00:30`), which is the intended UX decided 2026-09-14. Only from the transition
  date on do the two differ.
- **Plan step 1 as written would have hidden it.** Pinning the test to 10:00-11:00 moves it out of both
  windows, so the DST drift would never have surfaced in CI.
- Before the fix, the drift *cancelled* the midnight spanning after the autumn transition (`23:30 -> 00:30`
  became `22:30 -> 23:30`), so a #13-shaped failure could disappear on its own in November. Now that the
  drift is gone, #13's symptom is consistent year-round. Don't read a passing winter run as #13 being fixed.

## Reproduction
Not executed. Running the Selenium suite mutates the dev site, and the window can't be reached without faking
time. Analytical reproduction: create any event at 23:30 site time with default times, and the instance becomes
`23:30 → 00:30 next day`, `is_multiday() === true`.
A cheap way to confirm later is to run the suite in CI with a scheduled pipeline at 23:15 Europe/Berlin, or locally
with libfaketime on the web container (`FAKETIME="@2026-09-13 23:15:00"`).

## Plan
1. Make the test deterministic: after enabling repeat, set explicit start and end times in the form,
   through the date/time inputs (`#osec_start-date-input` / time pickers; check the
   selectors in `AdminPageAddEvent::meta_box_date()` template), instead of relying on "now".
   **Pick the times deliberately, not just a safe hour**: a fixed 10:00-11:00 sidesteps every
   near-midnight edge case, including the one that hid the DST bug above. Prefer a pinned date *and*
   time that keeps a near-midnight case in the suite (e.g. a fixed 23:30 start on a fixed date), so
   the assertion stays meaningful without being clock-dependent.
2. Fix the month-length computation to use one clock consistently:
   `const now = new Date(); const daysInNextMonth = new Date(now.getFullYear(), now.getMonth() + 2, 0).getDate();`
   Better: compute it from the calendar's displayed month title, since the server-side timezone decides the month.
3. Make the delete test locate the event by title ("Daily repeating event") instead of the first `.submitdelete`.
4. Optional: add a unit-level guard instead of relying on Selenium. A PHPUnit test that a DAILY event spanning
   midnight yields N instances and the agenda date array contains the expected number of days. This also documents
   the intended multi-day behavior.
5. Run `cd integration_tests && npm run test` with the Selenium add-on (`SELENIUM_REMOTE_URL`) against a disposable
   site, or in CI.

## Risks & open questions
- Showing a 23:30-00:30 occurrence on **both** days is the intended UX (**decided 2026-09-14**), so only the test changes.
- Open: should the suite also assert a DST transition date directly? The unit tests now cover the
  generator, so the integration test does not strictly need it, but nothing asserts the *rendered*
  view on a transition day.
- The integration suite writes to whatever `settings.local.js` points at. Don't run it against the dev site without asking.

## History
- 2026-09-13: created
- 2026-09-14: assessed against master 1dffb2b - no relevant changes
- 2026-09-23: found and fixed a separate DST bug sharing this time window (see section above).
  #13 itself is unchanged and still open - the multi-day-span mechanism is untouched.
