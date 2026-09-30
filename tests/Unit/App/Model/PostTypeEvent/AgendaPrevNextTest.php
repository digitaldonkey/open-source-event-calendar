<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * The agenda shows "previous" and "next" only when there is something to page to.
 *
 * The check counted every row of the instance table, so drafts, trashed
 * events, rows of deleted posts and events the view filters out all turned a
 * button on that led to an empty page.
 *
 * @group agenda
 */
class AgendaPrevNextTest extends TestBase
{
    private const SHOWN = '2030-06-15 12:00:00';

    public function test_buttons_off_when_only_hidden_events_lie_beyond()
    {
        global $wpdb;

        $this->create_event('Shown', self::SHOWN);
        $this->create_event('Draft before', '2030-06-01 12:00:00', 'draft');
        $this->create_event('Draft after', '2030-06-30 12:00:00', 'draft');
        $trashed = $this->create_event('Trashed after', '2030-07-01 12:00:00');
        wp_trash_post($trashed);
        $wpdb->insert(
            "{$wpdb->prefix}osec_event_instances",
            ['post_id' => 999999, 'start' => strtotime('2030-07-02 12:00:00 UTC'), 'end' => 0]
        );

        $this->assertSame([false, false], $this->buttons());
    }

    public function test_buttons_on_when_visible_events_lie_beyond()
    {
        $this->create_event('Before', '2030-06-01 12:00:00');
        $this->create_event('Shown', self::SHOWN);
        $this->create_event('After', '2030-06-30 12:00:00');

        $this->assertSame([true, true], $this->buttons());
    }

    public function test_buttons_respect_the_category_filter()
    {
        $term  = self::factory()->term->create(['taxonomy' => 'osec_events_categories']);
        $shown = $this->create_event('Shown', self::SHOWN);
        wp_set_object_terms($shown, [$term], 'osec_events_categories');
        $this->create_event('Other category after', '2030-06-30 12:00:00');

        $this->assertSame([false, false], $this->buttons(['cat_ids' => [$term]]));
    }

    /**
     * @return bool[] [prev, next] for a page of one event starting at SHOWN.
     */
    private function buttons(array $filter = []): array
    {
        global $osec_app;

        $result = EventSearch::factory($osec_app)->get_events_relative_to(
            strtotime(self::SHOWN . ' UTC') - 60,
            1,
            0,
            $filter
        );
        $this->assertCount(1, $result['events']);

        return [$result['prev'], $result['next']];
    }

    private function create_event(string $title, string $start, string $status = 'publish'): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(
            [
                'post_type'   => OSEC_POST_TYPE,
                'post_title'  => $title,
                'post_status' => $status,
            ]
        );
        $end     = new DT($start, 'UTC');
        $end->adjust(1, 'hour');
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT($start, 'UTC'),
                'end'              => $end,
                'allday'           => 0,
                'timezone_name'    => 'UTC',
                'recurrence_rules' => '',
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        ))->save(false);

        return $post_id;
    }
}
