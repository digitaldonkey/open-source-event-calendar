<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\AbstractView;
use Osec\Tests\Utilities\TestBase;

/**
 * Group depth of stacked overlapping events in week/day view, which the template uses to fit a
 * deep stack into its day column.
 *
 * @group osec
 */
class IndentDepthTest extends TestBase
{
    /**
     * @param  array  $indents  Indents of one day's timed events, in start order.
     *
     * @return array Their 'indent_depth', in the same order.
     */
    private function depths(array $indents): array
    {
        $method = self::getPrivateMethod(AbstractView::class, 'addIndentDepth');
        $events = array_map(fn($indent) => ['indent' => $indent], $indents);

        return array_column($method->invoke(null, $events), 'indent_depth');
    }

    public function test_no_events()
    {
        $this->assertSame([], $this->depths([]));
    }

    public function test_events_without_overlap_have_depth_zero()
    {
        $this->assertSame([0, 0, 0], $this->depths([0, 0, 0]));
    }

    public function test_every_event_of_a_group_gets_its_deepest_indent()
    {
        $this->assertSame([3, 3, 3, 3], $this->depths([0, 1, 2, 3]));
    }

    public function test_a_group_ends_where_indent_returns_to_zero()
    {
        // Two groups: a deep one, then a shallow one after everything before it has ended.
        $this->assertSame([2, 2, 2, 1, 1], $this->depths([0, 1, 2, 0, 1]));
    }

    public function test_depth_counts_indents_that_step_back_within_a_group()
    {
        // The stack pops back to indent 1 without emptying, so this is still one group.
        $this->assertSame([2, 2, 2, 2], $this->depths([0, 1, 2, 1]));
    }

    public function test_keys_are_preserved()
    {
        $method = self::getPrivateMethod(AbstractView::class, 'addIndentDepth');
        $events = $method->invoke(null, [5 => ['indent' => 0], 9 => ['indent' => 1]]);

        $this->assertSame([5, 9], array_keys($events));
        $this->assertSame(1, $events[9]['indent_depth']);
    }
}
