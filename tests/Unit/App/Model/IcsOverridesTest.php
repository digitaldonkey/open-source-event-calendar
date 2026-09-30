<?php

namespace Osec\Tests\Unit\App\Model;

use DateTime;
use DateTimeZone;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * Overrides (RECURRENCE-ID) replace one occurrence of their series.
 *
 * The replaced occurrence must be excluded from the series, or the event shows
 * twice: once where it was and once where it moved to.
 *
 * @group feeds
 * @group recurrence
 */
class IcsOverridesTest extends TestBase
{
    private const SERIES = "BEGIN:VEVENT\r\nUID:series@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
        "DTSTART:20250106T090000Z\r\nDTEND:20250106T100000Z\r\nRRULE:FREQ=WEEKLY;COUNT=4\r\n" .
        "SUMMARY:Series\r\nEND:VEVENT\r\n";

    private const MOVED = "BEGIN:VEVENT\r\nUID:series@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
        "RECURRENCE-ID:20250113T090000Z\r\nDTSTART:20250115T140000Z\r\nDTEND:20250115T150000Z\r\n" .
        "SUMMARY:Moved\r\nEND:VEVENT\r\n";

    /**
     * 00:30 in Berlin is 23:30 UTC of the day before.
     */
    private const MIDNIGHT_SERIES = "BEGIN:VEVENT\r\nUID:midnight@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
        "DTSTART;TZID=Europe/Berlin:20250107T003000\r\nDTEND;TZID=Europe/Berlin:20250107T013000\r\n" .
        "RRULE:FREQ=WEEKLY;COUNT=4\r\nSUMMARY:Midnight\r\nEND:VEVENT\r\n";

    private const MIDNIGHT_MOVED = "BEGIN:VEVENT\r\nUID:midnight@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
        "RECURRENCE-ID%s\r\nDTSTART;TZID=Europe/Berlin:20250114T120000\r\n" .
        "DTEND;TZID=Europe/Berlin:20250114T130000\r\nSUMMARY:Moved\r\nEND:VEVENT\r\n";

    public function test_override_after_its_series()
    {
        $this->import('a', self::SERIES . self::MOVED);

        $this->assertSame(
            ['2025-01-06 09:00 Series', '2025-01-15 14:00 Moved', '2025-01-20 09:00 Series', '2025-01-27 09:00 Series'],
            $this->occurrences('a', 'UTC')
        );
        $this->assertOverrideIsChildOfSeries('a');
    }

    public function test_override_before_its_series()
    {
        $this->import('b', self::MOVED . self::SERIES);

        $this->assertSame(
            ['2025-01-06 09:00 Series', '2025-01-15 14:00 Moved', '2025-01-20 09:00 Series', '2025-01-27 09:00 Series'],
            $this->occurrences('b', 'UTC')
        );
        $this->assertOverrideIsChildOfSeries('b');
    }

    public function test_refreshing_keeps_one_exclusion()
    {
        $this->import('c', self::SERIES . self::MOVED);
        $this->import('c', self::SERIES . self::MOVED);

        $this->assertSame(
            ['2025-01-06 09:00 Series', '2025-01-15 14:00 Moved', '2025-01-20 09:00 Series', '2025-01-27 09:00 Series'],
            $this->occurrences('c', 'UTC')
        );
    }

    /**
     * The exclusion must be the date in the series' timezone. Taken from the
     * RECURRENCE-ID as written (UTC, the 13th), it excluded nothing and the
     * 14th showed twice.
     *
     * @dataProvider midnight_recurrence_ids
     */
    public function test_override_of_a_series_near_midnight(string $recurrence_id)
    {
        $this->import('d', self::MIDNIGHT_SERIES . sprintf(self::MIDNIGHT_MOVED, $recurrence_id));

        $this->assertSame(
            [
                '2025-01-07 00:30 Midnight',
                '2025-01-14 12:00 Moved',
                '2025-01-21 00:30 Midnight',
                '2025-01-28 00:30 Midnight',
            ],
            $this->occurrences('d', 'Europe/Berlin')
        );
    }

    public static function midnight_recurrence_ids(): array
    {
        return [
            'in UTC'                  => [':20250113T233000Z'],
            'in the series\' TZID'    => [';TZID=Europe/Berlin:20250114T003000'],
        ];
    }

    public function test_all_day_override()
    {
        $series = "BEGIN:VEVENT\r\nUID:allday@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
            "DTSTART;VALUE=DATE:20250107\r\nDTEND;VALUE=DATE:20250108\r\nRRULE:FREQ=WEEKLY;COUNT=4\r\n" .
            "SUMMARY:AllDay\r\nEND:VEVENT\r\n";
        $moved  = "BEGIN:VEVENT\r\nUID:allday@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
            "RECURRENCE-ID;VALUE=DATE:20250114\r\nDTSTART;VALUE=DATE:20250116\r\nDTEND;VALUE=DATE:20250117\r\n" .
            "SUMMARY:Moved\r\nEND:VEVENT\r\n";

        $this->import('e', $series . $moved);

        $this->assertSame(
            ['2025-01-07 00:00 AllDay', '2025-01-16 00:00 Moved', '2025-01-21 00:00 AllDay', '2025-01-28 00:00 AllDay'],
            $this->occurrences('e', get_option('timezone_string') ?: 'UTC')
        );
    }

    private function import(string $name, string $vevents): void
    {
        global $osec_app;

        $url = $this->url($name);
        IcsImportExportParser::factory($osec_app)->import(
            [
                'events_in_db'   => array_flip(EventSearch::factory($osec_app)->get_event_ids_for_feed($url)),
                'feed'           => (object)[
                    'feed_id'              => '1',
                    'feed_url'             => $url,
                    'feed_name'            => $name,
                    'feed_category'        => '',
                    'feed_tags'            => '',
                    'hide_cost'            => '0',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '0',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '0',
                    'import_post_status'   => 'publish',
                ],
                'comment_status' => 'closed',
                'do_show_map'    => 0,
                'source'         => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\n" .
                                    $vevents . "END:VCALENDAR\r\n",
            ]
        );
    }

    /**
     * @return string[] 'Y-m-d H:i Title' of every instance of the feed, in order.
     */
    private function occurrences(string $name, string $timezone): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.start, p.post_title FROM {$wpdb->prefix}osec_events e
                    JOIN {$wpdb->posts} p ON p.ID = e.post_id
                    JOIN {$wpdb->prefix}osec_event_instances i ON i.post_id = e.post_id
                    WHERE e.ical_feed_url = %s ORDER BY i.start",
                $this->url($name)
            )
        );

        return array_map(
            function ($row) use ($timezone) {
                $start = new DateTime('@' . $row->start);
                $start->setTimezone(new DateTimeZone($timezone));

                return $start->format('Y-m-d H:i') . ' ' . $row->post_title;
            },
            $rows
        );
    }

    private function assertOverrideIsChildOfSeries(string $name): void
    {
        global $wpdb;

        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_parent FROM {$wpdb->prefix}osec_events e
                    JOIN {$wpdb->posts} p ON p.ID = e.post_id WHERE e.ical_feed_url = %s",
                $this->url($name)
            ),
            OBJECT_K
        );
        $by_title = wp_list_pluck($posts, 'post_parent', 'post_title');
        $series   = array_search('Series', wp_list_pluck($posts, 'post_title', 'ID'), true);

        $this->assertSame('0', $by_title['Series']);
        $this->assertSame((string)$series, $by_title['Moved']);
    }

    private function url(string $name): string
    {
        return "https://example.org/overrides-{$name}.ics";
    }
}
