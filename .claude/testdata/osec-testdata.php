<?php
/**
 * Test data for `wp osec event regenerate` and `wp osec feed update`.
 *
 *   /usr/local/bin/wp eval-file .claude/testdata/osec-testdata.php create
 *   /usr/local/bin/wp eval-file .claude/testdata/osec-testdata.php cleanup
 *
 * Everything it creates is titled "TESTDATA …" or has a feed URL containing
 * "osec-testdata", which is what cleanup removes. ICS files are written to
 * wp-content/uploads/osec-testdata/ and served over plain http, so no DDEV
 * certificate setup is needed.
 */

// A script run by `wp eval-file`: it declares its helpers and runs them.
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

defined('WP_CLI') || exit;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;

const OSEC_TESTDATA_TITLE = 'TESTDATA';
const OSEC_TESTDATA_TZ    = 'Europe/Berlin';

$osec_testdata_mode = $args[0] ?? 'create';
if ('cleanup' === $osec_testdata_mode) {
    osec_testdata_cleanup();
    return;
}
if ('create' !== $osec_testdata_mode) {
    WP_CLI::error('Usage: wp eval-file osec-testdata.php create|cleanup');
}
osec_testdata_cleanup();
osec_testdata_events();
osec_testdata_feeds();
WP_CLI::success('Test data created. See the summary above.');

function osec_testdata_event(string $title, string $start, string $rrule, string $exdates = '', int $hours = 1): int
{
    global $osec_app;

    $post_id = wp_insert_post(
        [
            'post_type'    => OSEC_POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => OSEC_TESTDATA_TITLE . ' ' . $title,
            'post_content' => 'Created by .claude/testdata/osec-testdata.php',
        ]
    );
    $end = new DT($start, OSEC_TESTDATA_TZ);
    $end->adjust($hours, 'hour');
    (new Event(
        $osec_app,
        [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT($start, OSEC_TESTDATA_TZ),
            'end'              => $end,
            'allday'           => 0,
            'timezone_name'    => OSEC_TESTDATA_TZ,
            'recurrence_rules' => $rrule,
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => $exdates,
        ]
    ))->save(false);

    return $post_id;
}

function osec_testdata_instances(int $post_id): int
{
    global $wpdb;

    return (int)$wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}osec_event_instances WHERE post_id = %d", $post_id)
    );
}

function osec_testdata_events(): void
{
    global $wpdb;
    $rows = [];

    // Healthy, for comparison: nothing to do for regenerate.
    $id     = osec_testdata_event('healthy daily 09:00', '2026-10-05 09:00:00', 'FREQ=DAILY;COUNT=10');
    $rows[] = [$id, 'healthy, 10 instances', osec_testdata_instances($id), 'unchanged'];

    // Instances lost: regenerate must rebuild them.
    $id = osec_testdata_event('lost all instances', '2026-10-05 18:00:00', 'FREQ=WEEKLY;COUNT=8');
    $wpdb->delete("{$wpdb->prefix}osec_event_instances", ['post_id' => $id]);
    $rows[] = [$id, 'all instances deleted', osec_testdata_instances($id), 'rebuilt to 8'];

    $id = osec_testdata_event('lost some instances', '2026-10-01 12:00:00', 'FREQ=MONTHLY;COUNT=12');
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}osec_event_instances WHERE post_id = %d AND start > %d",
            $id,
            strtotime('2027-04-01')
        )
    );
    $rows[] = [$id, 'instances after 2027-04 deleted', osec_testdata_instances($id), 'rebuilt to 12'];

    // Stale instances: the rule was changed in the database without a save.
    $id = osec_testdata_event('rule changed behind its back', '2026-10-06 10:00:00', 'FREQ=WEEKLY;COUNT=4');
    $wpdb->update(
        "{$wpdb->prefix}osec_events",
        ['recurrence_rules' => 'FREQ=WEEKLY;COUNT=12'],
        ['post_id' => $id]
    );
    $rows[] = [$id, 'rule now COUNT=12, instances still 4', osec_testdata_instances($id), 'rebuilt to 12'];

    // Near midnight with an EXDATE, as the editor stores it (local date).
    $id = osec_testdata_event(
        '00:30 weekly, 2nd excluded',
        '2026-10-06 00:30:00',
        'FREQ=WEEKLY;COUNT=5',
        '20261013T000000Z'
    );
    $rows[] = [$id, 'near midnight, EXDATE 2026-10-13', osec_testdata_instances($id), 'unchanged, 4'];

    // A rule older versions stored, which the generator cannot use.
    $id = osec_testdata_event('invalid stored rule', '2026-10-07 14:00:00', '');
    $wpdb->update(
        "{$wpdb->prefix}osec_events",
        ['recurrence_rules' => 'FREQ=YEARLY;BYMONTH=13'],
        ['post_id' => $id]
    );
    $rows[] = [$id, 'stored FREQ=YEARLY;BYMONTH=13', osec_testdata_instances($id), 'warning: not usable'];

    // More occurrences than OSEC_REOCCURRENCE_MAX_INSTANCES.
    $id = osec_testdata_event('hourly until 2030', '2026-10-01 08:00:00', 'FREQ=HOURLY;UNTIL=20300101T000000Z');
    $rows[] = [$id, 'hourly to 2030', osec_testdata_instances($id), 'warning: stopped after 2000'];

    // Orphans: rows of posts deleted without the cleanup (before the D4 fix).
    $id = osec_testdata_event('orphan event row', '2026-10-08 09:00:00', 'FREQ=DAILY;COUNT=3');
    $wpdb->delete($wpdb->posts, ['ID' => $id]);
    $rows[] = [$id, 'post deleted, event row + instances left', osec_testdata_instances($id), 'removed'];

    $id = osec_testdata_event('orphan instances', '2026-10-09 09:00:00', 'FREQ=DAILY;COUNT=3');
    $wpdb->delete("{$wpdb->prefix}osec_events", ['post_id' => $id]);
    $rows[] = [$id, 'event row deleted, instances left', osec_testdata_instances($id), 'removed'];

    WP_CLI\Utils\format_items(
        'table',
        array_map(
            fn($r) => array_combine(['post_id', 'state', 'instances', 'regenerate should'], $r),
            $rows
        ),
        ['post_id', 'state', 'instances', 'regenerate should']
    );
}

function osec_testdata_feeds(): void
{
    global $wpdb;

    $dir = wp_upload_dir()['basedir'] . '/osec-testdata';
    $url = str_replace('https://', 'http://', wp_upload_dir()['baseurl']) . '/osec-testdata';
    wp_mkdir_p($dir);

    $vevent = fn(string $uid, string $body) =>
        "BEGIN:VEVENT\r\nUID:{$uid}@osec-testdata\r\nDTSTAMP:20260901T000000Z\r\n{$body}END:VEVENT\r\n";
    $ics    = fn(string $name, string ...$vevents) => file_put_contents(
        "{$dir}/{$name}.ics",
        "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec testdata//EN\r\nX-WR-CALNAME:TESTDATA {$name}\r\n" .
        implode('', $vevents) . "END:VCALENDAR\r\n"
    );

    // Near midnight and evening: the D9/D10 cases.
    $ics(
        'timezones',
        $vevent(
            'berlin-0030',
            "DTSTART;TZID=Europe/Berlin:20261006T003000\r\nDTEND;TZID=Europe/Berlin:20261006T013000\r\n" .
            "RRULE:FREQ=WEEKLY;COUNT=5\r\nEXDATE;TZID=Europe/Berlin:20261020T003000\r\n" .
            "SUMMARY:Berlin 00:30 weekly - 20 Oct excluded, 13 Oct moved\r\n"
        ),
        $vevent(
            'berlin-0030',
            "RECURRENCE-ID:20261012T223000Z\r\nDTSTART;TZID=Europe/Berlin:20261013T190000\r\n" .
            "DTEND;TZID=Europe/Berlin:20261013T200000\r\nSUMMARY:Berlin 00:30 weekly - moved to 13 Oct 19:00\r\n"
        ),
        $vevent(
            'newyork-2000',
            "DTSTART;TZID=America/New_York:20261006T200000\r\nDTEND;TZID=America/New_York:20261006T210000\r\n" .
            "RRULE:FREQ=WEEKLY;COUNT=5\r\nEXDATE;TZID=America/New_York:20261013T200000\r\n" .
            "SUMMARY:New York 20:00 weekly - 13 Oct excluded\r\n"
        )
    );

    // Remove a VEVENT from this file, then `feed update`: it must be deleted (D6).
    $ics(
        'keep-old-events',
        $vevent('keep-a', "DTSTART:20261015T090000Z\r\nDTEND:20261015T100000Z\r\nSUMMARY:Keep-old A\r\n"),
        $vevent(
            'keep-b',
            "DTSTART:20261016T090000Z\r\nDTEND:20261016T100000Z\r\nSUMMARY:Keep-old B - delete me from the file\r\n"
        ),
        $vevent('keep-c', "DTSTART:20261017T090000Z\r\nDTEND:20261017T100000Z\r\nSUMMARY:Keep-old C\r\n")
    );

    // Volume: 1,500 events, 10% of them weekly (batch lines, memory).
    $many = [];
    for ($i = 0; $i < 1500; $i++) {
        $day    = gmdate('Ymd', strtotime('2026-10-01 +' . ($i % 90) . ' days'));
        $many[] = $vevent(
            "bulk-{$i}",
            "DTSTART;TZID=Europe/Berlin:{$day}T090000\r\nDTEND;TZID=Europe/Berlin:{$day}T100000\r\n" .
            ($i % 10 === 0 ? "RRULE:FREQ=WEEKLY;COUNT=10\r\n" : '') .
            "SUMMARY:Bulk {$i}\r\n"
        );
    }
    $ics('bulk-1500', ...$many);

    $feeds = [
        ['timezones', "{$url}/timezones.ics", 0],
        ['keep old events off', "{$url}/keep-old-events.ics", 0],
        ['bulk 1500', "{$url}/bulk-1500.ics", 0],
        [
            'repo fixture (overrides)',
            str_replace('https://', 'http://', untrailingslashit(OSEC_URL)) .
            '/tests/Unit/App/Model/ical_feeds/simple_occurrences_with_overide.ics?osec-testdata',
            1,
        ],
        [
            'public https (valid TLS)',
            'https://ics.calendarlabs.com/641/64bc8358/FIFA_Womens_World_Cup.ics?osec-testdata',
            1,
        ],
        ['self-signed TLS (must fail)', 'https://self-signed.badssl.com/osec-testdata.ics', 1],
        ['404 (must fail)', "{$url}/does-not-exist.ics", 1],
    ];
    $rows = [];
    foreach ($feeds as [$name, $feed_url, $keep_old]) {
        $wpdb->insert(
            "{$wpdb->prefix}osec_event_feeds",
            [
                'feed_url'        => $feed_url,
                'feed_name'       => OSEC_TESTDATA_TITLE . ' ' . $name,
                'feed_category'   => '',
                'feed_tags'       => '',
                'keep_old_events' => $keep_old,
            ]
        );
        $rows[] = [
            'feed_id'         => $wpdb->insert_id,
            'name'            => $name,
            'keep_old_events' => $keep_old,
        ];
    }

    // A lock left behind by a crashed import, for `feed update <id> --force`.
    $locked = $rows[0]['feed_id'];
    update_option(
        "osec_xlock_ics_import_{$locked}",
        wp_json_encode(
            [
                'time' => time() - 3 * HOUR_IN_SECONDS,
                'pid'  => 4711,
            ]
        ),
        false
    );
    $rows[0]['name'] .= ' (LOCKED 3 h ago)';

    WP_CLI\Utils\format_items('table', $rows, ['feed_id', 'name', 'keep_old_events']);
}

function osec_testdata_cleanup(): void
{
    global $wpdb;

    $feeds     = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT feed_id, feed_url FROM {$wpdb->prefix}osec_event_feeds WHERE feed_url LIKE %s",
            '%osec-testdata%'
        )
    );
    $feed_urls = wp_list_pluck($feeds, 'feed_url');
    $feed_ids  = wp_list_pluck($feeds, 'feed_id');
    $post_ids  = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title LIKE %s",
            OSEC_POST_TYPE,
            OSEC_TESTDATA_TITLE . '%'
        )
    );
    foreach ($feed_urls as $feed_url) {
        $post_ids = array_merge(
            $post_ids,
            $wpdb->get_col(
                $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}osec_events WHERE ical_feed_url = %s", $feed_url)
            )
        );
    }
    $post_ids = array_unique(array_map('intval', $post_ids));
    foreach ($post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }
    // Rows the script orphaned on purpose, and anything wp_delete_post() left.
    $orphans = array_merge(
        $wpdb->get_col(
            "SELECT post_id FROM {$wpdb->prefix}osec_events WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
        ),
        $wpdb->get_col(
            "SELECT DISTINCT i.post_id FROM {$wpdb->prefix}osec_event_instances i
                LEFT JOIN {$wpdb->prefix}osec_events e ON e.post_id = i.post_id WHERE e.post_id IS NULL"
        ),
        $post_ids
    );
    foreach (array_unique(array_map('intval', $orphans)) as $post_id) {
        $wpdb->delete("{$wpdb->prefix}osec_events", ['post_id' => $post_id]);
        $wpdb->delete("{$wpdb->prefix}osec_event_instances", ['post_id' => $post_id]);
    }
    foreach ($feed_ids as $feed_id) {
        delete_option("osec_xlock_ics_import_{$feed_id}");
        $wpdb->delete("{$wpdb->prefix}osec_event_feeds", ['feed_id' => $feed_id]);
    }
    $dir = wp_upload_dir()['basedir'] . '/osec-testdata';
    array_map('wp_delete_file', glob("{$dir}/*.ics") ?: []);
    is_dir($dir) && rmdir($dir); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    delete_option('osec_admin_notifications');

    WP_CLI::log(sprintf('Cleanup: %d posts, %d feeds removed.', count($post_ids), count($feed_ids)));
}
