<?php
/**
 * Test data for the calendar block options (dev site only).
 *
 *   /usr/local/bin/wp eval-file bin/dev-seed-block-test-data.php          # (re)create
 *   /usr/local/bin/wp eval-file bin/dev-seed-block-test-data.php delete   # remove
 *
 * Events are created relative to today in the site timezone, so every run gives
 * events in the current day, week and month. Everything created carries the
 * [OSEC-TEST] title prefix or the osec-test- term slug prefix; a run first
 * removes what an earlier run created.
 */

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;

if (! defined('WP_CLI')) {
    exit;
}

global $osec_app;

const OSEC_TEST_PREFIX = '[OSEC-TEST] ';
const OSEC_TEST_SLUG   = 'osec-test-';

$osec_test_delete = function () {
    $ids = get_posts([
        'post_type'      => OSEC_POST_TYPE,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        's'              => OSEC_TEST_PREFIX,
    ]);
    foreach ($ids as $id) {
        if (str_starts_with(get_post($id)->post_title, OSEC_TEST_PREFIX)) {
            wp_delete_post($id, true);
        }
    }
    foreach (['osec_events_categories', 'osec_events_tags'] as $taxonomy) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]);
        foreach ($terms as $test_term) {
            if (str_starts_with($test_term->slug, OSEC_TEST_SLUG)) {
                wp_delete_term($test_term->term_id, $taxonomy);
            }
        }
    }
    WP_CLI::log(sprintf('Removed %d test events and the test terms.', count($ids)));
};

$osec_test_delete();
if (isset($args[0]) && 'delete' === $args[0]) {
    return;
}

$make_term = function (string $name, string $taxonomy, int $parent_id = 0): int {
    $result = wp_insert_term($name, $taxonomy, [
        'slug'   => OSEC_TEST_SLUG . sanitize_title($name),
        'parent' => $parent_id,
    ]);
    if (is_wp_error($result)) {
        WP_CLI::error($result->get_error_message());
    }
    return (int) $result['term_id'];
};

$test_cat = [];
$test_cat['concerts']  = $make_term('Concerts', 'osec_events_categories');
$test_cat['workshops'] = $make_term('Workshops', 'osec_events_categories');
$test_cat['sports']    = $make_term('Sports', 'osec_events_categories');
$test_cat['football']  = $make_term('Football', 'osec_events_categories', $test_cat['sports']);
$test_cat['empty']     = $make_term('Empty category', 'osec_events_categories');

$test_tag = [];
foreach (['free', 'outdoor', 'family', 'online', 'unused tag'] as $name) {
    $test_tag[$name] = $make_term($name, 'osec_events_tags');
}

$tz    = wp_timezone_string();
$today = new DateTimeImmutable('today', new DateTimeZone($tz));

/**
 * @param  string  $title
 * @param  string  $start  Modifier relative to today 00:00, e.g. '+1 day 18:00'.
 * @param  string|null  $end  Modifier relative to the start, e.g. '+2 hours'; null = instant event.
 * @param  array  $opts  categories, tags, allday, rrule, exdates (modifiers relative to start), status, content, venue,
 *                       map (address, latitude, longitude: shows the map)
 */
$event = function (string $title, string $start, ?string $end, array $opts = []) use ($osec_app, $today, $tz): int {
    $start_dt = $today->modify($start);
    $end_dt   = null === $end ? $start_dt->modify('+15 minutes') : $start_dt->modify($end);

    $post_id = wp_insert_post([
        'post_type'    => OSEC_POST_TYPE,
        'post_title'   => OSEC_TEST_PREFIX . $title,
        'post_status'  => $opts['status'] ?? 'publish',
        'post_content' => $opts['content'] ?? 'Test event created by bin/dev-seed-block-test-data.php.',
    ], true);
    if (is_wp_error($post_id)) {
        WP_CLI::error($post_id->get_error_message());
    }

    $exdates = array_map(
        fn($modifier) => $start_dt->modify($modifier)->format('Ymd\THis'),
        $opts['exdates'] ?? []
    );

    $data = [
        'post_id'          => $post_id,
        'post'             => get_post($post_id),
        'start'            => new DT($start_dt->format('Y-m-d H:i:s'), $tz),
        'end'              => new DT($end_dt->format('Y-m-d H:i:s'), $tz),
        'allday'           => ! empty($opts['allday']) ? 1 : 0,
        'instant_event'    => null === $end ? 1 : 0,
        'timezone_name'    => $tz,
        'recurrence_rules' => $opts['rrule'] ?? '',
        'recurrence_dates' => '',
        'exception_rules'  => '',
        'exception_dates'  => implode(',', $exdates),
        'venue'            => $opts['venue'] ?? '',
    ];
    if (! empty($opts['map'])) {
        $data += $opts['map'] + [
            'show_map'         => true,
            'show_coordinates' => true,
        ];
    }
    (new Event($osec_app, $data))->save(false);

    // Event::save() does not assign terms (the editor saves them with the post).
    wp_set_object_terms($post_id, $opts['categories'] ?? [], 'osec_events_categories');
    wp_set_object_terms($post_id, $opts['tags'] ?? [], 'osec_events_tags');

    return $post_id;
};

// Title, start (relative to today 00:00), end (relative to the start; null = instant event),
// categories, tags, further options.
$events = [
    // Today and the next days: day, week and month view.
    ['Morning concert', '10:00', '+2 hours', ['concerts'], ['free']],
    ['Evening workshop (online)', '20:00', '+2 hours', ['workshops'], ['online']],
    ['Overlapping talk', '11:00', '+90 minutes', ['workshops'], []],
    ['Near midnight session', '23:30', '+1 hour', ['concerts'], []],
    ['Instant reminder (no end)', '12:00', null, [], ['free']],
    ['All-day family fair', '+1 day', '+1 day', ['sports'], ['family', 'outdoor'], ['allday' => true]],
    ['Three-day workshop', '+3 days 09:00', '+2 days 8 hours', ['workshops'], ['family']],
    ['No category, no tag', '+2 days 15:00', '+1 hour', [], []],
    // Single event page with a map (integration_tests/js_smoke).
    [
        'Open-air cinema (map)',
        '+2 days 21:00',
        '+2 hours',
        ['concerts'],
        ['outdoor'],
        [
            'venue' => 'Brandenburg Gate',
            'map'   => [
                'address'   => 'Pariser Platz 1, 10117 Berlin, Germany',
                'latitude'  => 52.51627,
                'longitude' => 13.377703,
            ],
        ],
    ],
    // Recurring.
    [
        'Weekly football training',
        '18:00',
        '+90 minutes',
        ['football'],
        ['outdoor'],
        [
            'rrule' => 'FREQ=WEEKLY;COUNT=10',
            'venue' => 'Sports ground',
        ],
    ],
    [
        'Daily course (one day skipped)',
        '+1 day 08:00',
        '+1 hour',
        ['workshops'],
        ['online'],
        [
            'rrule'   => 'FREQ=DAILY;COUNT=5',
            'exdates' => ['+2 days'],
        ],
    ],
    // Titles that need entity decoding in the block's "Filter by Events" search.
    ['D&D Night "quoted" & <b>not bold</b>', '+4 days 19:00', '+3 hours', ['concerts'], []],
    ['Café & Bar – Special', '+5 days 17:00', '+2 hours', [], ['free']],
    // Other months: navigation and fixed calendar date.
    ['Last month concert', '-1 month 19:00', '+2 hours', ['concerts'], []],
    ['Next month match', '+1 month 15:00', '+2 hours', ['football'], ['outdoor']],
    ['In two months: family day', '+2 months 10:00', '+6 hours', [], ['family']],
    // Must never show in the calendar.
    ['Draft event (hidden)', '+1 day 14:00', '+1 hour', ['concerts'], [], ['status' => 'draft']],
    ['Private event (hidden)', '+1 day 16:00', '+1 hour', ['concerts'], [], ['status' => 'private']],
];

// Enough events in the next two weeks for the agenda pager (limit 10) and "limit by days".
for ($i = 1; $i <= 10; $i++) {
    $events[] = [
        sprintf('Agenda filler %02d', $i),
        sprintf('+%d days %02d:00', $i, 9 + $i % 8),
        '+1 hour',
        [$i % 2 ? 'concerts' : 'sports'],
        $i % 3 ? [] : ['family'],
    ];
}

foreach ($events as $row) {
    $opts               = $row[5] ?? [];
    $opts['categories'] = array_map(fn($key) => $test_cat[$key], $row[3]);
    $opts['tags']       = array_map(fn($key) => $test_tag[$key], $row[4]);
    $event($row[0], $row[1], $row[2], $opts);
}

WP_CLI::success(sprintf(
    'Created %d test events, categories %s, tags %s (timezone %s).',
    count($events),
    implode(', ', array_keys($test_cat)),
    implode(', ', array_keys($test_tag)),
    $tz
));
