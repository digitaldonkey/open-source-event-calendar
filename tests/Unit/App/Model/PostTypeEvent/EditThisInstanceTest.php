<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventInstance;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Tests\Utilities\TestBase;
use ReflectionMethod;

/**
 * "Edit this instance": what the parent series looks like after one occurrence became its own event.
 *
 * Runs the part of EventParent::admin_init_post() after the child post exists (handleInstances(), then
 * EventInstance::clean()); admin_init_post() itself redirects and exits.
 *
 * @group event
 * @group recurrence
 */
class EditThisInstanceTest extends TestBase
{
    private const TIMEZONE = 'Europe/Berlin';

    /**
     * An occurrence moved to another day is excluded on its own date, also after the parent is rebuilt (B25).
     */
    public function test_moved_instance_excludes_its_original_date()
    {
        global $osec_app;

        $parent_id = $this->create_event('2026-01-05 09:00:00', 'FREQ=WEEKLY;COUNT=4');
        $this->edit_instance($parent_id, '2026-01-12', '2026-01-13 09:00:00');

        $parent = new Event($osec_app, $parent_id);
        self::assertSame('20260112T000000Z', $parent->get('exception_dates'));

        $parent->save(true);
        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($parent_id));
    }

    /**
     * An occurrence with a changed time on the same day is excluded on that day.
     */
    public function test_same_day_time_change_excludes_that_day()
    {
        global $osec_app;

        $parent_id = $this->create_event('2026-01-05 09:00:00', 'FREQ=WEEKLY;COUNT=4');
        $this->edit_instance($parent_id, '2026-01-12', '2026-01-12 11:00:00');

        $parent = new Event($osec_app, $parent_id);
        $parent->save(true);
        self::assertSame('20260112T000000Z', $parent->get('exception_dates'));
        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($parent_id));
    }

    /**
     * Editing the first occurrence moves the parent to the second; a COUNT series keeps its remaining number of
     * occurrences, whether it started in the past or lies in the future (B27: an empty exception date was
     * counted when the series started before now).
     *
     * @dataProvider first_occurrences
     */
    public function test_first_instance_edit_keeps_the_remaining_occurrences(string $start, array $expected)
    {
        global $osec_app;

        $parent_id = $this->create_event($start, 'FREQ=WEEKLY;COUNT=4');
        $first_day = substr($start, 0, 10);
        $this->edit_instance($parent_id, $first_day, $start);

        $parent = new Event($osec_app, $parent_id);
        self::assertSame('FREQ=WEEKLY;COUNT=3;', $parent->get('recurrence_rules'));
        self::assertEmpty($parent->get('exception_dates'));
        self::assertSame($expected, $this->instance_dates($parent_id));
    }

    /**
     * Editing only the time of the first occurrence moves the parent to the second occurrence; the first day
     * must not show twice (parent and child) after the parent is rebuilt.
     */
    public function test_first_instance_time_change_does_not_show_twice()
    {
        global $osec_app;

        $parent_id = $this->create_event('2026-01-05 09:00:00', 'FREQ=WEEKLY;COUNT=4');
        $this->edit_instance($parent_id, '2026-01-05', '2026-01-05 11:00:00');

        $parent = new Event($osec_app, $parent_id);
        $parent->save(true);
        self::assertSame(['2026-01-12', '2026-01-19', '2026-01-26'], $this->instance_dates($parent_id));
    }

    /**
     * Editing the first occurrence of a series that is not published moves the parent to the second, as for a
     * published one. It used to find no next occurrence, clear the rule and leave only the edited occurrence.
     *
     * @dataProvider unpublished_statuses
     */
    public function test_first_instance_edit_keeps_an_unpublished_series(string $status)
    {
        global $osec_app;

        $parent_id = $this->create_event('2026-01-05 09:00:00', 'FREQ=WEEKLY;COUNT=4');
        wp_update_post(['ID' => $parent_id, 'post_status' => $status]);
        $this->edit_instance($parent_id, '2026-01-05', '2026-01-05 09:00:00');

        $parent = new Event($osec_app, $parent_id);
        self::assertSame('FREQ=WEEKLY;COUNT=3;', $parent->get('recurrence_rules'));
        self::assertSame(['2026-01-12', '2026-01-19', '2026-01-26'], $this->instance_dates($parent_id));
    }

    public static function unpublished_statuses(): array
    {
        return [
            'private' => ['private'],
            'draft'   => ['draft'],
            'pending' => ['pending'],
        ];
    }

    public static function first_occurrences(): array
    {
        return [
            'past series'   => ['2026-01-05 09:00:00', ['2026-01-12', '2026-01-19', '2026-01-26']],
            'future series' => ['2030-01-07 09:00:00', ['2030-01-14', '2030-01-21', '2030-01-28']],
        ];
    }

    /**
     * Turns the parent's occurrence on $day into a child event starting at $child_start.
     *
     * @param  int  $parent_id  Parent event post.
     * @param  string  $day  Local date (Y-m-d) of the edited occurrence.
     * @param  string  $child_start  Local start of the child event.
     */
    private function edit_instance(int $parent_id, string $day, string $child_start): void
    {
        global $osec_app;

        $instance_id = array_search($day, $this->instance_dates($parent_id, true), true);
        self::assertNotFalse($instance_id, "No occurrence on $day");

        $child_id = $this->create_event($child_start, null);
        wp_update_post(['ID' => $child_id, 'post_parent' => $parent_id]);

        $handle = new ReflectionMethod(EventParent::class, 'handleInstances');
        $handle->invoke(
            EventParent::factory($osec_app),
            new Event($osec_app, $child_id),
            new Event($osec_app, $parent_id),
            $instance_id
        );
        EventInstance::factory($osec_app)->clean($parent_id, $instance_id);
    }

    /**
     * @param  int  $post_id  Event post.
     * @param  bool  $by_id  Key the dates by instance ID.
     *
     * @return string[] Local start dates (Y-m-d) of the event's stored instances, by start.
     */
    private function instance_dates(int $post_id, bool $by_id = false): array
    {
        global $wpdb;

        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, start FROM {$wpdb->prefix}osec_event_instances WHERE post_id = %d ORDER BY start",
                $post_id
            )
        );
        $dates = [];
        foreach ($rows as $row) {
            $dates[(int)$row->id] = wp_date('Y-m-d', (int)$row->start, new \DateTimeZone(self::TIMEZONE));
        }

        return $by_id ? $dates : array_values($dates);
    }

    /**
     * @param  string  $start  Local start (one hour long).
     * @param  string|null  $rrule  Repeat rule, or null for a single event.
     *
     * @return int Post ID.
     */
    private function create_event(string $start, ?string $rrule): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $begin   = new DT($start, self::TIMEZONE);
        $end     = new DT($start, self::TIMEZONE);
        $end->adjust(3600, 'second');
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => $begin,
                'end'              => $end,
                'allday'           => 0,
                'timezone_name'    => self::TIMEZONE,
                'recurrence_rules' => $rrule,
            ]
        ))->save(false);

        return $post_id;
    }
}
