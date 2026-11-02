<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventExclusionRepair;
use Osec\Tests\Utilities\TestBase;
use RuntimeException;

/**
 * Repair of events whose exclusion dates were merged into the repeat rule (#71).
 *
 * @group event
 * @group recurrence
 */
class EventExclusionRepairTest extends TestBase
{
    private const RRULE = 'FREQ=WEEKLY;COUNT=4';

    private const DATE = '20260112T000000Z';

    public function test_repair_brings_back_every_date_but_the_excluded_one()
    {
        $post_id = $this->create_broken_event();
        self::assertSame(['2026-01-05'], $this->instance_dates($post_id), 'Collapsed before the repair.');

        $result = $this->repair();

        self::assertSame(1, $result['repaired']);
        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($post_id));
        self::assertSame([], $this->affected_ids(), 'Nothing is left, so running it again changes nothing.');
        self::assertSame(self::RRULE, $this->column($post_id, 'recurrence_rules'));
        self::assertSame('EXDATE=' . self::DATE, $this->column($post_id, 'exception_rules'));
    }

    /**
     * "Edit this instance" adds dates to the column only, so they count too.
     */
    public function test_repair_keeps_dates_only_in_the_column()
    {
        $post_id = $this->create_broken_event(self::DATE . ',20260119T000000Z');

        $this->repair();

        self::assertSame(['2026-01-05', '2026-01-26'], $this->instance_dates($post_id));
        self::assertSame(self::DATE . ',20260119T000000Z', $this->column($post_id, 'exception_dates'));
    }

    /**
     * @dataProvider other_statuses
     */
    public function test_repair_covers_every_post_status(string $status)
    {
        $post_id = $this->create_broken_event();
        wp_update_post(['ID' => $post_id, 'post_status' => $status]);

        self::assertSame([$post_id], $this->affected_ids());
        $this->repair();

        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($post_id));
        self::assertSame($status, get_post_status($post_id), 'The status stays.');
    }

    public static function other_statuses(): array
    {
        return [
            'private' => ['private'],
            'draft'   => ['draft'],
            'pending' => ['pending'],
            'trash'   => ['trash'],
        ];
    }

    /**
     * Only the merged shape matches: a date list, or a real rule with dates next to it, is correct data.
     *
     * @dataProvider correct_exclusions
     */
    public function test_correct_exclusions_are_left_alone(string $rule, string $dates)
    {
        $post_id = $this->create_event();
        $this->write_exclusion($post_id, $rule, $dates);

        self::assertSame([], $this->affected_ids());
        $this->repair();

        self::assertSame($rule, $this->column($post_id, 'exception_rules'));
        self::assertSame($dates, $this->column($post_id, 'exception_dates'));
    }

    public static function correct_exclusions(): array
    {
        return [
            'date list'           => ['EXDATE=' . self::DATE, self::DATE],
            'rule'                => ['FREQ=WEEKLY;INTERVAL=2', ''],
            'rule and dates'      => ['FREQ=WEEKLY;INTERVAL=2', self::DATE],
            'part of other value' => ['X-EXDATE=1;X-FREQ=2', ''],
        ];
    }

    /**
     * Saving would delete the unusable rule, so the event is listed but not touched.
     */
    public function test_unusable_repeat_rule_is_skipped_and_kept()
    {
        global $wpdb;

        $post_id = $this->create_broken_event();
        $wpdb->update($wpdb->prefix . 'osec_events', ['recurrence_rules' => 'FREQ=NOPE;COUNT=x'], ['post_id' => $post_id]);

        $result = $this->repair();

        self::assertSame(0, $result['repaired']);
        self::assertSame([get_the_title($post_id)], $result['skipped']);
        self::assertSame('FREQ=NOPE;COUNT=x', $this->column($post_id, 'recurrence_rules'));
        self::assertSame([$post_id], $this->affected_ids(), 'Stays listed.');
    }

    /**
     * Other fields survive the save.
     */
    public function test_repair_keeps_the_other_fields()
    {
        $post_id = $this->create_broken_event(self::DATE, ['venue' => 'D&D "Hall"', 'ical_feed_url' => 'https://example.test/f.ics?a=1&b=2']);

        $this->repair();

        self::assertSame('D&D "Hall"', $this->column($post_id, 'venue'));
        self::assertSame('https://example.test/f.ics?a=1&b=2', $this->column($post_id, 'ical_feed_url'));
    }

    /**
     * One failing event does not stop the others, and a later run picks it up.
     */
    public function test_failing_event_does_not_stop_the_others()
    {
        $failing = $this->create_broken_event();
        $other   = $this->create_broken_event();
        $fail    = static function (Event $event) use ($failing) {
            if ($event->get('post_id') === $failing) {
                throw new RuntimeException('Save failed');
            }
        };
        add_action('osec_pre_save_event', $fail);
        $result = $this->repair();
        remove_action('osec_pre_save_event', $fail);

        self::assertSame(1, $result['repaired']);
        self::assertSame([get_the_title($failing)], $result['failed']);
        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($other));

        self::assertSame(1, $this->repair()['repaired']);
        self::assertSame([], $this->affected_ids());
    }

    /**
     * find_affected() runs on admin_init: a malformed date must not break wp-admin, the event is listed instead.
     */
    public function test_malformed_date_is_listed_as_not_repairable()
    {
        $post_id = $this->create_event();
        $this->write_exclusion($post_id, self::RRULE . ';EXDATE= ,' . self::DATE, '');

        $result = $this->repair();

        self::assertSame(0, $result['repaired']);
        self::assertSame([$post_id], $this->affected_ids());
        self::assertCount(1, $result['skipped']);
    }

    public function test_parent_with_edited_occurrences_is_marked()
    {
        global $osec_app;

        $post_id = $this->create_broken_event();
        self::assertFalse(EventExclusionRepair::factory($osec_app)->find_affected()[0]['has_children']);

        $child_id = $this->create_event();
        wp_update_post(['ID' => $child_id, 'post_parent' => $post_id]);
        self::assertTrue(EventExclusionRepair::factory($osec_app)->find_affected()[0]['has_children']);
    }

    /**
     * @return array Result of the repair of everything affected.
     */
    private function repair(): array
    {
        global $osec_app;

        $repair = EventExclusionRepair::factory($osec_app);

        return $repair->repair($repair->find_affected());
    }

    /**
     * @return int[] Posts the repair finds.
     */
    private function affected_ids(): array
    {
        global $osec_app;

        return array_column(EventExclusionRepair::factory($osec_app)->find_affected(), 'post_id');
    }

    /**
     * Creates a weekly series of four, as 1.1.15 to 1.2.0 stored it after excluding a date.
     *
     * @param  string  $dates  Stored exception dates.
     * @param  array  $fields  Further event fields.
     *
     * @return int Post ID.
     */
    private function create_broken_event(string $dates = self::DATE, array $fields = []): int
    {
        global $osec_app;

        $post_id = $this->create_event($fields);
        $this->write_exclusion($post_id, self::RRULE . ';EXDATE=' . self::DATE, $dates);
        // Rebuilds the instances from the stored rule, which collapses the series.
        (new Event($osec_app, $post_id))->save(true);

        return $post_id;
    }

    /**
     * Writes the exclusion columns directly, as an earlier release left them.
     */
    private function write_exclusion(int $post_id, string $rule, string $dates): void
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'osec_events',
            [
                'exception_rules' => $rule,
                'exception_dates' => $dates,
            ],
            ['post_id' => $post_id]
        );
    }

    /**
     * @param  array  $fields  Further event fields.
     *
     * @return int Post ID of a new weekly series of four.
     */
    private function create_event(array $fields = []): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event(
            $osec_app,
            array_merge(
                [
                    'post_id'          => $post_id,
                    'post'             => get_post($post_id),
                    'start'            => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                    'end'              => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                    'allday'           => 0,
                    'timezone_name'    => 'Europe/Berlin',
                    'recurrence_rules' => self::RRULE,
                ],
                $fields
            )
        ))->save(false);

        return $post_id;
    }

    /**
     * @return string Value of an events table column.
     */
    private function column(int $post_id, string $column): string
    {
        global $wpdb;

        return (string)$wpdb->get_var(
            $wpdb->prepare("SELECT {$column} FROM {$wpdb->prefix}osec_events WHERE post_id = %d", $post_id)
        );
    }

    /**
     * @return string[] Local start dates of the event's instances.
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

        return array_map(static fn($start) => wp_date('Y-m-d', (int)$start, new \DateTimeZone('Europe/Berlin')), $starts);
    }
}
