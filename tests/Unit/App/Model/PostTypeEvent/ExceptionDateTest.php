<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use DateTime;
use DateTimeZone;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Tests\Utilities\TestBase;

/**
 * Excluding one occurrence of a recurring event ("edit this instance").
 *
 * An exception date names the day in the series' own timezone. It must match
 * whenever the event's local date differs from its UTC date: evening events
 * west of UTC and morning events east of it (side finding B2).
 *
 * @group event
 * @group recurrence
 */
class ExceptionDateTest extends TestBase
{
    public static function timezones(): array
    {
        return [
            'UTC'                    => ['UTC', '10:00'],
            'New York, evening'      => ['America/New_York', '20:00'],
            'Tokyo, morning'         => ['Asia/Tokyo', '08:00'],
            'Berlin, after midnight' => ['Europe/Berlin', '00:30'],
        ];
    }

    /**
     * @dataProvider timezones
     */
    public function test_an_excluded_date_removes_that_occurrence(string $timezone, string $time)
    {
        global $osec_app;

        $post_id = $this->create_event($timezone, $time);
        $this->assertSame(
            ['2030-03-02', '2030-03-03', '2030-03-04', '2030-03-05', '2030-03-06'],
            $this->local_dates($post_id, $timezone)
        );

        EventParent::factory($osec_app)->add_exception_date($post_id, new DT("2030-03-04 $time", $timezone));

        $this->assertSame(
            ['2030-03-02', '2030-03-03', '2030-03-05', '2030-03-06'],
            $this->local_dates($post_id, $timezone)
        );
    }

    /**
     * Loading an event by instance ID gives that instance's dates (#44: the
     * SQLite driver evaluated `IF(aei.start IS NOT NULL, ...)` to the base
     * event's dates, so "edit this instance" forked a child on the series start
     * and never excluded anything).
     *
     * @dataProvider timezones
     */
    public function test_an_instance_loads_with_its_own_dates(string $timezone, string $time)
    {
        global $osec_app;

        $post_id  = $this->create_event($timezone, $time);
        $instance = $this->instances($post_id)[2];

        $event = new Event($osec_app, $post_id, (int)$instance->id);

        $this->assertSame((int)$instance->start, (int)$event->get('start')->format_to_gmt());
        $this->assertSame((int)$instance->end, (int)$event->get('end')->format_to_gmt());
    }

    /**
     * The start of an instance-loaded event, as the "edit this instance" form
     * receives it, excludes that instance.
     *
     * @dataProvider timezones
     */
    public function test_the_start_of_a_loaded_instance_excludes_it(string $timezone, string $time)
    {
        global $osec_app;

        $post_id  = $this->create_event($timezone, $time);
        $instance = $this->instances($post_id)[2];
        $event    = new Event($osec_app, $post_id, (int)$instance->id);

        EventParent::factory($osec_app)->add_exception_date($post_id, $event->get('start'));

        $this->assertSame(
            ['2030-03-02', '2030-03-03', '2030-03-05', '2030-03-06'],
            $this->local_dates($post_id, $timezone)
        );
    }

    private function create_event(string $timezone, string $time): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $end     = new DT("2030-03-02 $time", $timezone);
        $end->adjust(1, 'hour');
        $event = new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT("2030-03-02 $time", $timezone),
                'end'              => $end,
                'allday'           => 0,
                'timezone_name'    => $timezone,
                'recurrence_rules' => 'FREQ=DAILY;COUNT=5',
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        );
        $event->save(false);

        return $post_id;
    }

    private function instances(int $post_id): array
    {
        global $osec_app;

        return $osec_app->db->get_results(
            $osec_app->db->prepare(
                'SELECT id, start, end FROM ' . $osec_app->db->get_table_name(OSEC_DB__INSTANCES) .
                ' WHERE post_id = %d ORDER BY start',
                $post_id
            )
        );
    }

    private function local_dates(int $post_id, string $timezone): array
    {
        return array_map(
            fn($row) => (new DateTime('@' . $row->start))->setTimezone(new DateTimeZone($timezone))->format('Y-m-d'),
            $this->instances($post_id)
        );
    }
}
