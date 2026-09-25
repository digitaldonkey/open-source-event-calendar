<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\View\Calendar\AgendaView;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * Agenda paging counts pages from a fixed exact_date.
 *
 * The links moved exact_date to the page's first event and always used a
 * page_offset of ±1. On a day with more events than one page holds the date
 * did not move, so "forward" returned the same page again and again, and
 * "back" did not return to where it came from.
 *
 * @group agenda
 */
class AgendaPagingTest extends TestBase
{
    private const EXACT_DATE = '2030-06-15 12:00:00';

    /**
     * @dataProvider page_offsets
     */
    public function test_links_count_the_page_from_the_same_exact_date(int $page, int $prev, int $next)
    {
        global $osec_app;

        $time    = strtotime(self::EXACT_DATE . ' UTC');
        $request = new RequestParser($osec_app, ['action' => 'agenda'], 'agenda');
        $request->parse();
        $links = (new \ReflectionMethod(AgendaView::class, 'getPaginationLinks'))->invoke(
            new AgendaView($osec_app, $request),
            [
                'action'                  => 'agenda',
                'exact_date'              => $time,
                'page_offset'             => $page,
                'cat_ids'                 => [],
                'tag_ids'                 => [],
                'display_filters'         => 'true',
                'display_subscribe'       => 'true',
                'agenda_toggle'           => 'true',
                'display_view_switch'     => 'true',
                'display_date_navigation' => 'true',
                'data_type'               => 'data-type="json"',
            ],
            true,
            true,
            new DT('2030-06-20 09:00:00', 'UTC'),
            new DT('2030-06-20 09:00:00', 'UTC')
        );
        $hrefs = wp_list_pluck(array_filter($links, 'is_array'), 'href', 'class');

        foreach (['ai1ec-prev-page' => $prev, 'ai1ec-next-page' => $next] as $class => $offset) {
            $this->assertStringContainsString("exact_date~{$time}", $hrefs[$class], $class);
            $this->assertSame($offset, $this->page_offset($hrefs[$class]), $class);
        }
    }

    public static function page_offsets(): array
    {
        return [
            'first page'   => [0, -1, 1],
            'third page'   => [2, 1, 3],
            'back page -2' => [-2, -3, -1],
        ];
    }

    /**
     * Pages -3 to 3 hold every event once: none twice, none skipped. The
     * event in progress at exact_date belongs to page 0 only, and five events
     * share one start, more than a page holds.
     */
    public function test_pages_hold_every_event_once()
    {
        global $osec_app;

        $expected = [
            $this->create_event('2030-06-14 09:00:00', 1),
            $this->create_event('2030-06-14 10:00:00', 1),
            $this->create_event('2030-06-15 08:00:00', 1),
            $this->create_event('2030-06-15 11:00:00', 3),
        ];
        for ($i = 0; $i < 5; $i++) {
            $expected[] = $this->create_event('2030-06-20 09:00:00', 1);
        }
        $expected[] = $this->create_event('2030-06-21 09:00:00', 1);

        $time  = strtotime(self::EXACT_DATE . ' UTC');
        $found = [];
        for ($page = -3; $page <= 3; $page++) {
            $result = EventSearch::factory($osec_app)->get_events_relative_to($time, 2, $page);
            foreach ($result['events'] as $event) {
                $found[] = $event->get('post_id');
            }
        }

        $this->assertSame([], array_diff_assoc($found, array_unique($found)), 'No event on two pages.');
        sort($expected);
        sort($found);
        $this->assertSame($expected, $found, 'Every event on a page.');
    }

    private function page_offset(string $href): int
    {
        // A page_offset of 0 is the default and left out of the URL.
        return preg_match('#page_offset~(-?\d+)#', $href, $match) ? (int)$match[1] : 0;
    }

    private function create_event(string $start, int $hours): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish']);
        $end     = new DT($start, 'UTC');
        $end->adjust($hours, 'hour');
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
