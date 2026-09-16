<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\AbstractView;
use Osec\Tests\Utilities\TestBase;

/**
 * Side-by-side columns for overlapping events in week/day view.
 *
 * @group osec
 */
class OverlapColumnsTest extends TestBase
{
    /**
     * @param  array  $events  [top, height] pairs in minutes.
     *
     * @return array [column, columns] pairs in the given order.
     */
    private function columns(array $events, int $max_columns = 0): array
    {
        $method = self::getPrivateMethod(AbstractView::class, 'addOverlapColumns');
        $events = array_map(
            fn($event) => [
                'top'    => $event[0],
                'height' => $event[1],
            ],
            $events
        );

        return array_map(
            fn($event) => [$event['column'], $event['columns']],
            $method->invoke(null, $events, $max_columns)
        );
    }

    /**
     * @param  array  $events  [top, height] pairs in minutes.
     *
     * @return array Stack offsets in the given order.
     */
    private function stacks(array $events, int $max_columns): array
    {
        $method = self::getPrivateMethod(AbstractView::class, 'addOverlapColumns');
        $events = array_map(
            fn($event) => [
                'top'    => $event[0],
                'height' => $event[1],
            ],
            $events
        );

        return array_column($method->invoke(null, $events, $max_columns), 'stack');
    }

    public function test_separate_events_use_full_width()
    {
        $this->assertSame([[0, 1], [0, 1]], $this->columns([[480, 60], [600, 60]]));
    }

    public function test_events_at_same_time_are_side_by_side()
    {
        $this->assertSame([[0, 2], [1, 2]], $this->columns([[539, 60], [539, 60]]));
    }

    public function test_free_column_is_reused_within_a_group()
    {
        // A 8:00-9:00, B 8:30-9:30, C 9:00-10:00: C reuses A's column.
        $this->assertSame(
            [[0, 2], [1, 2], [0, 2]],
            $this->columns([[480, 60], [510, 60], [540, 60]])
        );
    }

    public function test_longer_event_first_at_same_start()
    {
        $this->assertSame([[1, 2], [0, 2]], $this->columns([[480, 30], [480, 120]]));
    }

    public function test_short_events_overlap_by_minimum_height()
    {
        // 10 minute events 20 minutes apart overlap visually (min height 34px = 34 minutes).
        $this->assertSame([[0, 2], [1, 2]], $this->columns([[480, 10], [500, 10]]));
        $this->assertSame([[0, 1], [0, 1]], $this->columns([[480, 10], [514, 10]]));
    }

    public function test_groups_get_their_own_column_count()
    {
        $this->assertSame(
            [[0, 3], [1, 3], [2, 3], [0, 1]],
            $this->columns([[480, 60], [480, 60], [500, 30], [600, 60]])
        );
    }

    public function test_no_events()
    {
        $this->assertSame([], $this->columns([]));
        $this->assertSame([], $this->stacks([], 2));
    }

    public function test_events_beyond_the_column_limit_share_the_last_column()
    {
        $events = [[480, 60], [480, 60], [480, 60], [480, 60]];

        $this->assertSame([[0, 2], [1, 2], [1, 2], [1, 2]], $this->columns($events, 2));
        $this->assertSame([0, 0, 1, 2], $this->stacks($events, 2));
    }

    public function test_column_limit_does_nothing_below_the_limit()
    {
        $events = [[480, 60], [480, 60]];

        $this->assertSame([[0, 2], [1, 2]], $this->columns($events, 4));
        $this->assertSame([0, 0], $this->stacks($events, 4));
    }

    public function test_limit_applies_per_group()
    {
        // 8:00-9:00 three times, then a single event at 10:00.
        $events = [[480, 60], [480, 60], [480, 60], [600, 60]];

        $this->assertSame([[0, 2], [1, 2], [1, 2], [0, 1]], $this->columns($events, 2));
        $this->assertSame([0, 0, 1, 0], $this->stacks($events, 2));
    }
}
