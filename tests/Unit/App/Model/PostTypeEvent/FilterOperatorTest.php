<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * The osec_filter_distinct_types_logic filter combines category, tag and author
 * filters with OR instead of AND. Only the filters may be combined: the date
 * range and the post status still apply to every event.
 *
 * The filter conditions were appended to the WHERE without brackets, so with OR
 * an event matching a category or tag bypassed the date range, and one matching
 * a category also bypassed the post status: drafts, private and trashed events
 * showed to anyone.
 *
 * @group search
 */
class FilterOperatorTest extends TestBase
{
    private int $cat;
    private int $tag;

    public function set_up()
    {
        parent::set_up();
        $this->cat = self::factory()->term->create(['taxonomy' => 'osec_events_categories']);
        $this->tag = self::factory()->term->create(['taxonomy' => 'osec_events_tags']);
    }

    public function tear_down()
    {
        remove_all_filters('osec_filter_distinct_types_logic');
        parent::tear_down();
    }

    /**
     * @dataProvider operators
     */
    public function test_month_shows_only_published_events_in_range(string $operator, array $expected, array $later)
    {
        global $osec_app;

        add_filter('osec_filter_distinct_types_logic', fn() => $operator);
        $ids = $this->create_events();

        $events = EventSearch::factory($osec_app)->get_events_between(
            new DT('2030-06-01 00:00:00', 'UTC'),
            new DT('2030-07-01 00:00:00', 'UTC'),
            ['cat_ids' => [$this->cat], 'tag_ids' => [$this->tag]]
        );

        $this->assertSame($expected, $this->titles($ids, array_map(fn($e) => $e->get('post_id'), $events)));
    }

    /**
     * @dataProvider operators
     */
    public function test_agenda_shows_only_published_events_from_its_date(string $operator, array $expected, array $later)
    {
        global $osec_app;

        add_filter('osec_filter_distinct_types_logic', fn() => $operator);
        $ids = $this->create_events();

        $result = EventSearch::factory($osec_app)->get_events_relative_to(
            strtotime('2030-06-01 00:00:00 UTC'),
            20,
            0,
            ['cat_ids' => [$this->cat], 'tag_ids' => [$this->tag]]
        );

        // The agenda has no end date, so events after the month belong to it.
        $this->assertSame(
            array_merge($expected, $later),
            $this->titles($ids, array_map(fn($e) => $e->get('post_id'), $result['events']))
        );
        $this->assertTrue($result['prev'], '"category, before range" is on the previous page.');
    }

    public static function operators(): array
    {
        return [
            'AND' => ['AND', ['category and tag'], []],
            'OR'  => ['OR', ['category and tag', 'category only', 'tag only'], ['tag, after range']],
        ];
    }

    /**
     * @return array<string, int> Post IDs by title.
     */
    private function create_events(): array
    {
        return [
            'category and tag'       => $this->create_event('2030-06-10', [$this->cat], [$this->tag]),
            'category only'          => $this->create_event('2030-06-11', [$this->cat], []),
            'tag only'               => $this->create_event('2030-06-12', [], [$this->tag]),
            'neither'                => $this->create_event('2030-06-13', [], []),
            'category, before range' => $this->create_event('2030-05-10', [$this->cat], [$this->tag]),
            'tag, after range'       => $this->create_event('2031-06-10', [], [$this->tag]),
            'category, draft'        => $this->create_event('2030-06-14', [$this->cat], [$this->tag], 'draft'),
            'category, private'      => $this->create_event('2030-06-15', [$this->cat], [], 'private'),
        ];
    }

    /**
     * Titles of the found $post_ids, in the order of $ids, so the comparison ignores the query's order.
     */
    private function titles(array $ids, array $post_ids): array
    {
        return array_values(array_keys(array_intersect($ids, array_map('intval', $post_ids))));
    }

    private function create_event(string $day, array $cats, array $tags, string $status = 'publish'): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => $status]);
        wp_set_object_terms($post_id, $cats, 'osec_events_categories');
        wp_set_object_terms($post_id, $tags, 'osec_events_tags');
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT("{$day} 09:00:00", 'UTC'),
                'end'              => new DT("{$day} 10:00:00", 'UTC'),
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
