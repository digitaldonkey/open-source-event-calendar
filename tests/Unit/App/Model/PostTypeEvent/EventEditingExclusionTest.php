<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Tests\Utilities\TestBase;

/**
 * Exclusions saved through the event editor: a date list stays a date list, a rule is merged with the repeat rule.
 *
 * @group event
 * @group recurrence
 */
class EventEditingExclusionTest extends TestBase
{
    private const RRULE = 'FREQ=WEEKLY;COUNT=4';

    public function tear_down()
    {
        $_REQUEST = [];
        parent::tear_down();
    }

    /**
     * Excluding a custom date removes that date only; the repeat rule does not become an exclusion rule.
     */
    public function test_custom_date_excludes_that_date_only()
    {
        global $osec_app;

        $post_id = $this->create_event();
        $this->submit($post_id, 'EXDATE=20260112T000000Z');

        $event = new Event($osec_app, $post_id);
        self::assertSame('20260112T000000Z', $event->get('exception_dates'));
        self::assertStringNotContainsString('FREQ', (string)$event->get('exception_rules'));
        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($post_id));
    }

    /**
     * An exclusion rule gets the repeat rule's frequency and keeps its own parts.
     *
     * The series' first occurrence is always kept (EventInstance::createCollection()), so the rule excludes
     * every second week and the first week stays.
     */
    public function test_rule_is_merged_with_repeat_rule()
    {
        global $osec_app;

        $post_id = $this->create_event();
        $this->submit($post_id, 'FREQ=WEEKLY;INTERVAL=2');

        $event = new Event($osec_app, $post_id);
        self::assertSame('FREQ=WEEKLY;COUNT=4;INTERVAL=2', $event->get('exception_rules'));
        self::assertEmpty($event->get('exception_dates'));
        self::assertSame(['2026-01-05', '2026-01-12', '2026-01-26'], $this->instance_dates($post_id));
    }

    /**
     * "Edit this instance" adds an exception date to the parent; the next editor save of the parent posts the
     * exclusion box as loaded (EXDATE=<dates>) and must keep that date.
     */
    public function test_parent_save_after_edit_this_instance_keeps_the_series()
    {
        global $osec_app;

        $post_id = $this->create_event();
        EventParent::factory($osec_app)->add_exception_date(
            $post_id,
            new DT('2026-01-12 09:00:00', 'Europe/Berlin')
        );
        $loaded = new Event($osec_app, $post_id);
        self::assertSame('EXDATE=20260112T000000Z', $loaded->get('exception_rules'));

        $this->submit($post_id, $loaded->get('exception_rules'));

        $event = new Event($osec_app, $post_id);
        self::assertSame('20260112T000000Z', $event->get('exception_dates'));
        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($post_id));
    }

    /**
     * An event stored by 1.1.15 to 1.2.0 holds the repeat rule merged with the dates as exclusion rule (#71).
     * One editor save turns it back into a date list, joined with dates added to the column later.
     */
    public function test_editor_save_heals_a_merged_exclusion_rule()
    {
        global $osec_app, $wpdb;

        $post_id = $this->create_event();
        $broken  = self::RRULE . ';EXDATE=20260112T000000Z';
        $wpdb->update(
            $wpdb->prefix . 'osec_events',
            ['exception_rules' => $broken, 'exception_dates' => '20260112T000000Z,20260119T000000Z'],
            ['post_id' => $post_id]
        );

        $this->submit($post_id, $broken);

        $event = new Event($osec_app, $post_id);
        self::assertSame('20260112T000000Z,20260119T000000Z', $event->get('exception_dates'));
        self::assertStringNotContainsString('FREQ', (string)$event->get('exception_rules'));
        self::assertSame(['2026-01-05', '2026-01-26'], $this->instance_dates($post_id));
    }

    /**
     * Dates added next to a real exclusion rule ("Edit this instance") survive an editor save, which posts only
     * the rule.
     */
    public function test_rule_save_keeps_dates_added_next_to_it()
    {
        global $osec_app;

        $post_id = $this->create_event();
        $this->submit($post_id, 'FREQ=WEEKLY;INTERVAL=2');
        EventParent::factory($osec_app)->add_exception_date(
            $post_id,
            new DT('2026-01-26 09:00:00', 'Europe/Berlin')
        );

        $this->submit($post_id, (new Event($osec_app, $post_id))->get('exception_rules'));

        $event = new Event($osec_app, $post_id);
        self::assertSame('FREQ=WEEKLY;COUNT=4;INTERVAL=2', $event->get('exception_rules'));
        self::assertSame('20260126T000000Z', $event->get('exception_dates'));
        self::assertSame(['2026-01-05', '2026-01-12'], $this->instance_dates($post_id));
    }

    /**
     * Switching the exclusion from custom dates to a rule replaces the dates.
     */
    public function test_rule_replaces_former_custom_dates()
    {
        global $osec_app;

        $post_id = $this->create_event();
        $this->submit($post_id, 'EXDATE=20260112T000000Z');
        $this->submit($post_id, 'FREQ=WEEKLY;INTERVAL=2');

        $event = new Event($osec_app, $post_id);
        self::assertSame('FREQ=WEEKLY;COUNT=4;INTERVAL=2', $event->get('exception_rules'));
        self::assertEmpty($event->get('exception_dates'));
        self::assertSame(['2026-01-05', '2026-01-12', '2026-01-26'], $this->instance_dates($post_id));
    }

    /**
     * Submits the editor with a weekly repeat and the given exclusion.
     *
     * @param  int  $post_id  Event post.
     * @param  string  $exrule  Value of the editor's exclusion field (EXDATE=... or a rule).
     */
    private function submit(int $post_id, string $exrule): void
    {
        global $osec_app;

        $_REQUEST = wp_slash([
            EventEditing::NONCE_NAME => wp_create_nonce(EventEditing::NONCE_ACTION),
            'osec_start_time'        => '2026-01-05 09:00:00',
            'osec_end_time'          => '2026-01-05 10:00:00',
            'osec_timezone_name'     => 'Europe/Berlin',
            'osec_repeat'            => '1',
            'osec_rrule'             => self::RRULE,
            'osec_exclude'           => '1',
            'osec_exrule'            => $exrule,
        ]);
        EventEditing::factory($osec_app)->save_post($post_id, get_post($post_id), true);
        $_REQUEST = [];
    }

    /**
     * @return string[] Local start dates (Y-m-d) of the event's stored instances.
     */
    private function instance_dates(int $post_id): array
    {
        global $wpdb;

        $starts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT start FROM {$wpdb->prefix}osec_event_instances WHERE post_id = %d ORDER BY start",
                $post_id
            )
        );

        return array_map(fn($start) => wp_date('Y-m-d', (int)$start, new \DateTimeZone('Europe/Berlin')), $starts);
    }

    private function create_event(): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event(
            $osec_app,
            [
                'post_id'       => $post_id,
                'post'          => get_post($post_id),
                'start'         => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                'end'           => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                'allday'        => 0,
                'timezone_name' => 'Europe/Berlin',
            ]
        ))->save(false);

        return $post_id;
    }
}
