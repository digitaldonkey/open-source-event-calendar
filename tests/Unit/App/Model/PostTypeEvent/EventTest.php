<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventInstance;
use Osec\Tests\Utilities\TestBase;

/**
 * @group event
 * Sample test case.
 */
class EventTest extends TestBase
{
    public function test_event_defaults()
    {
        $this->assertFalse(Event::is_geo_value(0));
        $this->assertFalse(Event::is_geo_value('0'));
    }

    /**
     * An occurrence of the event loads with its own dates and ID, and links to "Edit this occurrence".
     */
    public function test_loading_an_occurrence_of_the_event()
    {
        global $osec_app;

        $post_id = $this->create_weekly_event();
        $second  = $this->instances($post_id)[1];

        $event = new Event($osec_app, $post_id, (int) $second->id);

        self::assertSame((int) $second->id, (int) $event->get('instance_id'));
        self::assertSame((int) $second->start, (int) $event->get('start')->format_to_gmt());
        self::assertStringContainsString('&instance=' . $second->id, $event->get_instance_edit_link());
    }

    /**
     * An ID that is no occurrence of this event (unknown, or another event's) loads the event's own dates
     * without an instance ID, so no edit link points at it (#74).
     */
    public function test_an_id_of_no_occurrence_leaves_instance_id_empty()
    {
        global $osec_app;

        $post_id = $this->create_weekly_event();
        $first   = $this->instances($post_id)[0];
        $other   = $this->instances($this->create_weekly_event())[1];

        foreach ([999999999, (int) $other->id] as $instance_id) {
            $event = new Event($osec_app, $post_id, $instance_id);

            self::assertNull($event->get('instance_id'), "instance $instance_id");
            self::assertSame((int) $first->start, (int) $event->get('start')->format_to_gmt(), "instance $instance_id");
            self::assertStringNotContainsString('&instance=', $event->get_instance_edit_link(), "instance $instance_id");
        }
    }

    /**
     * -1 looks up the occurrence on the event's own dates; without one, no instance ID (was -1).
     */
    public function test_first_occurrence_lookup()
    {
        global $osec_app;

        $post_id = $this->create_weekly_event();
        $first   = $this->instances($post_id)[0];

        self::assertSame((int) $first->id, (int) (new Event($osec_app, $post_id, -1))->get('instance_id'));

        EventInstance::factory($osec_app)->clean($post_id, (int) $first->id);
        $event = new Event($osec_app, $post_id, -1);

        self::assertNull($event->get('instance_id'));
        self::assertStringNotContainsString('&instance=', $event->get_instance_edit_link());
    }

    /**
     * Saves a published weekly event with three occurrences.
     *
     * @return int Post ID.
     */
    private function create_weekly_event(): int
    {
        global $osec_app;

        return (new Event(
            $osec_app,
            [
                'post'             => [
                    'post_status' => 'publish',
                    'post_type'   => OSEC_POST_TYPE,
                    'post_title'  => 'Weekly event',
                ],
                'start'            => new DT('2026-07-06 09:00:00', 'UTC'),
                'end'              => new DT('2026-07-06 10:00:00', 'UTC'),
                'allday'           => false,
                'instant_event'    => false,
                'timezone_name'    => 'UTC',
                'recurrence_rules' => 'FREQ=WEEKLY;COUNT=3',
            ]
        ))->save();
    }

    /**
     * The event's occurrences, by start.
     *
     * @param  int  $post_id  Event post.
     *
     * @return object[] Rows with id and start.
     */
    private function instances(int $post_id): array
    {
        global $osec_app;

        $rows = $osec_app->db->get_results(
            $osec_app->db->prepare(
                'SELECT id, start FROM ' . $osec_app->db->get_table_name(OSEC_DB__INSTANCES) .
                ' WHERE post_id = %d ORDER BY start ASC',
                $post_id
            )
        );
        self::assertCount(3, $rows);

        return $rows;
    }
}
