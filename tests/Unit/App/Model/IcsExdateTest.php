<?php

namespace Osec\Tests\Unit\App\Model;

use DateTime;
use DateTimeZone;
use Osec\App\Model\IcsImportExportParser;
use Osec\Tests\Utilities\TestBase;

/**
 * EXDATE of an imported series excludes the right day.
 *
 * The instance generator reads only the date part of an exclusion and applies
 * the series' start time. Stored as a UTC date, an EXDATE named the wrong day
 * whenever the local start falls on another UTC date: after midnight east of
 * UTC, and in the evening west of it.
 *
 * @group feeds
 * @group recurrence
 */
class IcsExdateTest extends TestBase
{
    /**
     * @dataProvider exdates
     */
    public function test_exdate_excludes_the_named_day(string $timezone, string $time, string $exdate, array $expected)
    {
        $vevent = "BEGIN:VEVENT\r\nUID:exdate@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
            "DTSTART;TZID={$timezone}:20250107T{$time}\r\nDTEND;TZID={$timezone}:20250107T{$time}\r\n" .
            "RRULE:FREQ=WEEKLY;COUNT=4\r\nEXDATE{$exdate}\r\nSUMMARY:Series\r\nEND:VEVENT\r\n";

        $this->import($vevent);

        $this->assertSame($expected, $this->occurrences($timezone));
    }

    public static function exdates(): array
    {
        $berlin = ['2025-01-07 00:30', '2025-01-21 00:30', '2025-01-28 00:30'];
        $york   = ['2025-01-07 20:00', '2025-01-21 20:00', '2025-01-28 20:00'];

        return [
            'Berlin after midnight, EXDATE in the series\' TZID' => [
                'Europe/Berlin', '003000', ';TZID=Europe/Berlin:20250114T003000', $berlin,
            ],
            'Berlin after midnight, EXDATE in UTC'               => [
                'Europe/Berlin', '003000', ':20250113T233000Z', $berlin,
            ],
            'New York evening, EXDATE in the series\' TZID'      => [
                'America/New_York', '200000', ';TZID=America/New_York:20250114T200000', $york,
            ],
            'New York evening, EXDATE as a date'                 => [
                'America/New_York', '200000', ';VALUE=DATE:20250114', $york,
            ],
            'New York after midnight, EXDATE floating'           => [
                'America/New_York', '003000', ':20250114T003000',
                ['2025-01-07 00:30', '2025-01-21 00:30', '2025-01-28 00:30'],
            ],
            'Berlin midday, EXDATE in UTC'                       => [
                'Europe/Berlin', '120000', ':20250114T110000Z',
                ['2025-01-07 12:00', '2025-01-21 12:00', '2025-01-28 12:00'],
            ],
        ];
    }

    private function import(string $vevent): void
    {
        global $osec_app;

        IcsImportExportParser::factory($osec_app)->import(
            [
                'events_in_db'   => [],
                'feed'           => (object)[
                    'feed_id'              => '1',
                    'feed_url'             => 'https://example.org/exdate.ics',
                    'feed_name'            => 'exdate',
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
                                    $vevent . "END:VCALENDAR\r\n",
            ]
        );
    }

    /**
     * @return string[] 'Y-m-d H:i' of every instance, in the series' timezone.
     */
    private function occurrences(string $timezone): array
    {
        global $wpdb;

        $starts = $wpdb->get_col(
            "SELECT i.start FROM {$wpdb->prefix}osec_events e
                JOIN {$wpdb->prefix}osec_event_instances i ON i.post_id = e.post_id
                WHERE e.ical_feed_url = 'https://example.org/exdate.ics' ORDER BY i.start"
        );

        return array_map(
            function ($start) use ($timezone) {
                $date = new DateTime('@' . $start);
                $date->setTimezone(new DateTimeZone($timezone));

                return $date->format('Y-m-d H:i');
            },
            $starts
        );
    }
}
