<?php

namespace Osec\Tests\Unit\App\Model;

use DateTime;
use DateTimeZone;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * RDATE of an imported event adds the right days.
 *
 * The import kept only the text after the last colon of all RDATE lines, so only
 * the last line survived, with its timezone dropped. Next to an RRULE, the dates
 * replaced the rule. And the instance generator reads only the date part, in the
 * series' timezone, so a date taken in UTC named the wrong day.
 *
 * @group feeds
 * @group recurrence
 */
class IcsRdateTest extends TestBase
{
    private const FEED_URL = 'https://example.org/rdate.ics';

    /**
     * @dataProvider rdates
     */
    public function test_rdate_adds_the_named_days(string $timezone, string $properties, array $expected)
    {
        $this->import(
            "DTSTART;TZID={$timezone}:{$properties}"
        );

        $this->assertSame($expected, $this->occurrences($timezone));
    }

    public static function rdates(): array
    {
        return [
            'two RDATE lines'                               => [
                'Europe/Berlin',
                "20250107T090000\r\nDTEND;TZID=Europe/Berlin:20250107T100000\r\n" .
                "RDATE;TZID=Europe/Berlin:20250110T090000\r\nRDATE;TZID=Europe/Berlin:20250115T090000",
                ['2025-01-07 09:00', '2025-01-10 09:00', '2025-01-15 09:00'],
            ],
            'one RDATE line listing two dates'              => [
                'Europe/Berlin',
                "20250107T090000\r\nDTEND;TZID=Europe/Berlin:20250107T100000\r\n" .
                'RDATE;TZID=Europe/Berlin:20250110T090000,20250115T090000',
                ['2025-01-07 09:00', '2025-01-10 09:00', '2025-01-15 09:00'],
            ],
            'Berlin after midnight, RDATE in UTC'           => [
                'Europe/Berlin',
                "20250107T003000\r\nDTEND;TZID=Europe/Berlin:20250107T013000\r\nRDATE:20250113T233000Z",
                ['2025-01-07 00:30', '2025-01-14 00:30'],
            ],
            'New York evening, RDATE in UTC'                => [
                'America/New_York',
                "20250107T200000\r\nDTEND;TZID=America/New_York:20250107T210000\r\nRDATE:20250115T010000Z",
                ['2025-01-07 20:00', '2025-01-14 20:00'],
            ],
            'New York evening, RDATE as a date'             => [
                'America/New_York',
                "20250107T200000\r\nDTEND;TZID=America/New_York:20250107T210000\r\nRDATE;VALUE=DATE:20250114",
                ['2025-01-07 20:00', '2025-01-14 20:00'],
            ],
            'New York after midnight, RDATE floating'       => [
                'America/New_York',
                "20250107T003000\r\nDTEND;TZID=America/New_York:20250107T013000\r\nRDATE:20250114T003000",
                ['2025-01-07 00:30', '2025-01-14 00:30'],
            ],
            'RDATE as a period'                             => [
                'Europe/Berlin',
                "20250107T090000\r\nDTEND;TZID=Europe/Berlin:20250107T100000\r\n" .
                'RDATE;VALUE=PERIOD:20250110T080000Z/20250110T110000Z',
                ['2025-01-07 09:00', '2025-01-10 09:00'],
            ],
            'RRULE and RDATE'                               => [
                'Europe/Berlin',
                "20250107T090000\r\nDTEND;TZID=Europe/Berlin:20250107T100000\r\nRRULE:FREQ=WEEKLY;COUNT=2\r\n" .
                "RDATE;TZID=Europe/Berlin:20250110T090000\r\nRDATE;TZID=Europe/Berlin:20250125T090000",
                ['2025-01-07 09:00', '2025-01-10 09:00', '2025-01-14 09:00', '2025-01-25 09:00'],
            ],
        ];
    }

    public function test_all_day_rdates()
    {
        $this->import(
            "DTSTART;VALUE=DATE:20250107\r\nDTEND;VALUE=DATE:20250108\r\n" .
            "RDATE;VALUE=DATE:20250110\r\nRDATE;VALUE=DATE:20250115"
        );

        $this->assertSame(
            ['2025-01-07', '2025-01-10', '2025-01-15'],
            array_map(
                fn($occurrence) => substr($occurrence, 0, 10),
                $this->occurrences(get_option('timezone_string') ?: 'UTC')
            )
        );
    }

    /**
     * The export writes the rule and the dates, so a round trip keeps both.
     */
    public function test_rrule_and_rdates_are_exported()
    {
        global $osec_app;

        $this->import(
            "DTSTART;TZID=Europe/Berlin:20250107T090000\r\nDTEND;TZID=Europe/Berlin:20250107T100000\r\n" .
            "RRULE:FREQ=WEEKLY;COUNT=2\r\nRDATE;TZID=Europe/Berlin:20250110T090000"
        );

        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events'                    => [new Event($osec_app, $this->post_id())],
            'do_not_export_as_calendar' => false,
        ]);

        $this->assertStringContainsString('RRULE:FREQ=WEEKLY;COUNT=2', $ics);
        $this->assertMatchesRegularExpression('/^RDATE[^:\r\n]*:20250110T090000\r?$/m', $ics);
    }

    private function import(string $properties): void
    {
        global $osec_app;

        IcsImportExportParser::factory($osec_app)->import(
            [
                'events_in_db'   => [],
                'feed'           => (object)[
                    'feed_id'              => '1',
                    'feed_url'             => self::FEED_URL,
                    'feed_name'            => 'rdate',
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
                                    "BEGIN:VEVENT\r\nUID:rdate@example.org\r\nDTSTAMP:20250101T000000Z\r\n" .
                                    $properties . "\r\nSUMMARY:Series\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
            ]
        );
    }

    private function post_id(): int
    {
        global $wpdb;

        return (int)$wpdb->get_var(
            $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}osec_events WHERE ical_feed_url = %s", self::FEED_URL)
        );
    }

    /**
     * @return string[] 'Y-m-d H:i' of every instance, in the given timezone.
     */
    private function occurrences(string $timezone): array
    {
        global $wpdb;

        $starts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT start FROM {$wpdb->prefix}osec_event_instances WHERE post_id = %d ORDER BY start",
                $this->post_id()
            )
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
