---
paths:
  - "src/App/Model/Notifications/**"
  - "src/App/Controller/FeedsController.php"
  - "src/App/Controller/ImportExportController.php"
  - "src/App/Model/PostTypeEvent/EventEditing.php"
  - "src/WpCli/**"
  - "public/admin/**/notification/**"
---

<!-- Loaded when working on notifications or their callers. CLAUDE.md keeps the summary. -->

## Admin Notifications

**Never hand-roll an `admin_notices` callback.** The plugin has one notification mechanism and everything
that needs to tell an admin something goes through it:

- `NotificationAdmin::factory($app)->store($message, $class, $importance, $recipients, $persistent)` queues a
  message; `BootstrapController` already registers the `admin_notices` **and** `network_admin_notices`
  dispatch (`->send()`), so a new notice needs **no new hook registration**.
- `are_notices_available($importance)` does the screen gating for you: importance `0` shows only on the
  calendar's own screens (`AccessControl::is_all_events_page()`, `are_we_editing_our_post()` — i.e. the event
  edit screen), `1` adds Plugins/Updates, `2` adds the Dashboard.
- `$persistent = true` renders the dismiss button wired to `wp_ajax_osec_dismiss_notice`
  (`NotificationAdmin::dismiss_notice()`). A non-persistent message is deleted the first time it is shown.
- Messages are keyed by `sha1` of the entity, so storing the same message twice does not stack it.
- **`notification/admin.twig` prints `{{ message | raw }}`** — escape every interpolated value with
  `esc_html()` *before* calling `store()`, never at render time.
- Dispatch is delayed by design: `store()` during a `save_post` or a cron run, and the notice appears on the
  next admin page load.
- The `osec_admin_notification_pre_store` filter short-circuits `store()`. The WP-CLI commands hook it for
  the length of a run (`Osec\WpCli\PrintsAdminNotices`) to print instead of store. Keep it scoped like that:
  `wp cron event run` is also WP-CLI, and a global redirect would send nightly feed failures to a cron log.

**Anything that runs without a browser to answer to must use it.** Scheduled feed imports call
`FeedsController::update_ics($feed_id)` with `$ajax === false`, and that return value — error message and all —
is simply discarded. Before this was noticed, a nightly feed could fail forever in silence. The same applies to
the ICS export, which runs unauthenticated: `ImportExportController::export_events()` subscribes to the export
problem hook and stores a notice rather than answering the fetcher.

**In tests**, notices live in the `osec_admin_notifications` option and therefore leak between tests in a
class (and the bootstrap leaves one behind). Clear it in `set_up()`:
`$osec_app->options->delete(NotificationAdmin::OPTION_KEY);` — see
`tests/Unit/App/Model/PostTypeEvent/RecurrenceNoticeTest.php`.

**Degrade and report, do not throw.** The pattern used for recurrence problems: the model fires a documented
action, the caller decides how to surface it (editor notice, feed result message, admin notice). Current
actions: `osec_recurrence_truncated`, `osec_recurrence_rule_invalid`, `osec_recurrence_rule_not_exportable`.
A listener registered around one operation must be removed afterwards (`EventEditing` does this around
`$event->save()`), or it outlives the request that wanted it.
