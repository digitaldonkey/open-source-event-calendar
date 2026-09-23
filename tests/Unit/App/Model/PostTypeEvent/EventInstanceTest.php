<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use DateTime;
use DateTimeZone;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventInstance;
use Osec\Tests\Utilities\TestBase;

/**
 * @group event
 * @group recurrence
 */
class EventInstanceTest extends TestBase
{
    /**
     * Recurrence must keep the wall clock time of the event's own timezone.
     *
     * Expanding in UTC shifts every occurrence after a DST transition by an
     * hour, which makes an event starting shortly after midnight appear twice
     * on the transition day and never on the last day of the series.
     */
    public function test_daily_recurrence_keeps_wall_clock_time_over_dst_end()
    {
        $starts = $this->recurrence_starts(
            '2026-10-20 00:15:00',
            '2026-11-03 00:15:00',
            'Europe/Berlin',
            'FREQ=DAILY'
        );

        $this->assertSame(
            [
                '2026-10-20 00:15',
                '2026-10-21 00:15',
                '2026-10-22 00:15',
                '2026-10-23 00:15',
                '2026-10-24 00:15',
                '2026-10-25 00:15',
                '2026-10-26 00:15',
                '2026-10-27 00:15',
                '2026-10-28 00:15',
                '2026-10-29 00:15',
                '2026-10-30 00:15',
                '2026-10-31 00:15',
                '2026-11-01 00:15',
                '2026-11-02 00:15',
                '2026-11-03 00:15',
            ],
            $starts,
            'One occurrence per day at the same local time across the end of DST.'
        );
    }

    /**
     * Same as above for the spring transition, which shifts the other way.
     */
    public function test_daily_recurrence_keeps_wall_clock_time_over_dst_start()
    {
        $starts = $this->recurrence_starts(
            '2027-03-26 23:30:00',
            '2027-03-31 23:30:00',
            'Europe/Berlin',
            'FREQ=DAILY'
        );

        $this->assertSame(
            [
                '2027-03-26 23:30',
                '2027-03-27 23:30',
                '2027-03-28 23:30',
                '2027-03-29 23:30',
                '2027-03-30 23:30',
                '2027-03-31 23:30',
            ],
            $starts
        );
    }

    /**
     * Weekly recurrence must stay on the same weekday and time.
     */
    public function test_weekly_recurrence_keeps_wall_clock_time_over_dst_end()
    {
        $starts = $this->recurrence_starts(
            '2026-10-18 00:30:00',
            '2026-11-08 00:30:00',
            'Europe/Berlin',
            'FREQ=WEEKLY;BYDAY=SU'
        );

        $this->assertSame(
            [
                '2026-10-18 00:30',
                '2026-10-25 00:30',
                '2026-11-01 00:30',
                '2026-11-08 00:30',
            ],
            $starts
        );
    }

    /**
     * Builds an event and returns its recurrence starts as local date strings.
     *
     * @param  string  $start  Local start date time.
     * @param  string  $until  Local date time to stop generating at.
     * @param  string  $timezone  Event timezone.
     * @param  string  $rrule  Recurrence rule without UNTIL.
     *
     * @return string[] Sorted 'Y-m-d H:i' values in the event timezone.
     */
    protected function recurrence_starts(string $start, string $until, string $timezone, string $rrule): array
    {
        global $osec_app;

        $tz         = new DateTimeZone($timezone);
        $until_utc  = (new DateTime($until, $tz))->setTimezone(new DateTimeZone('UTC'));
        $start_date = new DT($start, $timezone);
        $end_date   = new DT($start, $timezone);
        $end_date->adjust(1, 'hour');

        $event = new Event(
            $osec_app,
            [
                'post_id'          => 1,
                'start'            => $start_date,
                'end'              => $end_date,
                'allday'           => 0,
                'timezone_name'    => $timezone,
                'recurrence_rules' => $rrule . ';UNTIL=' . $until_utc->format('Ymd\THis\Z'),
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        );

        $instances = EventInstance::factory($osec_app)->create_instances_by_recurrence(
            $event,
            ['post_id' => 1],
            $start_date->format_to_gmt(),
            3600,
            $timezone
        );

        $starts = [];
        foreach (array_keys($instances) as $timestamp) {
            $starts[] = (new DateTime('@' . $timestamp))
                ->setTimezone($tz)
                ->format('Y-m-d H:i');
        }
        sort($starts);

        return $starts;
    }
}
