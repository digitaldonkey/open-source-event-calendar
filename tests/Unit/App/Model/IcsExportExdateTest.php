<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * Multiple excluded dates share one EXDATE property without empty values.
 */
class IcsExportExdateTest extends TestBase
{
    /**
     * @dataProvider exclusions
     *
     * @param string[] $dates Stored excluded dates.
     */
    public function test_export_preserves_excluded_dates(array $dates)
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
            'recurrence_rules' => 'FREQ=WEEKLY;COUNT=4',
            'exception_dates' => implode(',', $dates),
        ]);
        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events' => [$event],
            'do_not_export_as_calendar' => false,
        ]);
        $unfolded = str_replace("\r\n ", '', $ics);
        preg_match_all('/^EXDATE[^\r\n]*/m', $unfolded, $matches);

        self::assertCount(1, $matches[0]);
        self::assertStringNotContainsString(':,', $matches[0][0]);
        $values = explode(':', $matches[0][0], 2)[1];
        self::assertCount(count($dates), explode(',', $values));
    }

    /**
     * @return array[] Multiple exclusions and the single-date control.
     */
    public static function exclusions(): array
    {
        return [
            'two dates' => [['20261127T000000Z', '20261204T000000Z']],
            'three dates' => [['20261127T000000Z', '20261204T000000Z', '20261211T000000Z']],
            'one date' => [['20261127T000000Z']],
        ];
    }
}
