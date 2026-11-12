<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * Excluded dates export as one EXDATE property: every date at the series' start time, in the series' timezone.
 *
 * The event uses the site's timezone, which the export writes as TZID for every event (an event in another
 * timezone currently gets the site's TZID too, a separate bug). The test site's timezone comes from
 * PHP's date.timezone (Europe/Berlin locally and in CI), so the expected line is built from it.
 */
class IcsExportExdateTest extends TestBase
{
    /**
     * @dataProvider exclusions
     *
     * @param string[] $dates    Stored excluded dates.
     * @param string[] $expected Exported values, in this order.
     */
    public function test_export_writes_excluded_dates_at_the_series_start_time(array $dates, array $expected)
    {
        global $osec_app;

        $timezone = Timezones::factory($osec_app)->get_default_timezone();
        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $start = new DT('2026-11-20 18:00:00', $timezone);
        $start->set_preferred_timezone($timezone);
        $event = new Event($osec_app, [
            'post_id' => $post_id,
            'post' => get_post($post_id),
            'start' => $start,
            'end' => clone $start,
            'allday' => false,
            'timezone_name' => $timezone,
            'recurrence_rules' => 'FREQ=WEEKLY;COUNT=30',
            'exception_dates' => implode(',', $dates),
        ]);
        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events' => [$event],
            'do_not_export_as_calendar' => false,
        ]);
        $unfolded = str_replace("\r\n ", '', $ics);
        preg_match_all('/^EXDATE[^\r\n]*/m', $unfolded, $matches);

        // iCalcreator writes UTC as a "Z" suffix instead of a TZID.
        $line = 'UTC' === $timezone
            ? 'EXDATE:' . implode('Z,', $expected) . 'Z'
            : 'EXDATE;TZID=' . $timezone . ':' . implode(',', $expected);
        self::assertSame([$line], $matches[0]);
    }

    /**
     * @return array[] Stored dates and the values the export writes for them.
     */
    public static function exclusions(): array
    {
        return [
            'one date' => [['20261127T000000Z'], ['20261127T180000']],
            'two dates' => [['20261127T000000Z', '20261204T000000Z'], ['20261127T180000', '20261204T180000']],
            'three dates' => [
                ['20261127T000000Z', '20261204T000000Z', '20261211T000000Z'],
                ['20261127T180000', '20261204T180000', '20261211T180000'],
            ],
            'stored out of order' => [['20261204T000000Z', '20261127T000000Z'], ['20261127T180000', '20261204T180000']],
            'after a DST change, same wall time' => [['20270402T000000Z'], ['20270402T180000']],
        ];
    }
}
