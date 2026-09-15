<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Calendar\CalendarShortcodeView;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\Tests\Utilities\TestBase;

/**
 * Print header (title, view name, view URL) and the print color variable.
 *
 * @group osec
 */
class PrintHeaderTest extends TestBase
{
    public static function viewProvider(): array
    {
        return [
            'month'  => ['monthly', 'September 2026 · Month', 'action~month', 'exact_date~1-9-2026'],
            'week'   => ['weekly', 'Week 38 / September 2026 · Week', 'action~week', 'exact_date~14-9-2026'],
            'oneday' => ['oneday', 'September 16, 2026 · Day', 'action~oneday', 'exact_date~16-9-2026'],
            'agenda' => ['agenda', 'September 2026 · Agenda', 'action~agenda', 'exact_date~'],
        ];
    }

    private function get_print_header(string $view, array $atts = []): string
    {
        global $osec_app;
        $atts += [
            'view'       => $view,
            'exact_date' => '16-9-2026',
        ];
        $html  = CalendarShortcodeView::factory($osec_app)->shortcode($atts);
        $this->assertSame(1, substr_count($html, 'class="osec-print-header"'));
        preg_match('~<div class="osec-print-header".*?</div>~s', $html, $matches);

        return $matches[0];
    }

    /**
     * @dataProvider viewProvider
     */
    public function test_print_header_has_title_view_name_and_url(
        string $view,
        string $title,
        string $action,
        string $date
    ) {
        $header = $this->get_print_header($view);

        $this->assertStringContainsString(' hidden>', $header);
        $this->assertStringContainsString('>' . $title . '</h2>', $header);
        $this->assertStringContainsString($action, $header);
        $this->assertStringContainsString($date, $header);
        $this->assertStringNotContainsString('request_format', $header);
    }

    /**
     * @dataProvider viewProvider
     */
    public function test_print_url_keeps_filters_but_not_display_options(string $view)
    {
        $cat_id = self::factory()->term->create(['taxonomy' => 'osec_events_categories']);
        $tag_id = self::factory()->term->create(['taxonomy' => 'osec_events_tags']);

        $header = $this->get_print_header(
            $view,
            [
                'cat_id'            => (string) $cat_id,
                'tag_id'            => (string) $tag_id,
                'display_filters'   => 'false',
                'display_subscribe' => 'false',
            ]
        );

        $this->assertStringContainsString('cat_ids~' . $cat_id, $header);
        $this->assertStringContainsString('tag_ids~' . $tag_id, $header);
        $this->assertStringNotContainsString('display_', $header);
    }

    public function test_color_style_contains_print_color_variable()
    {
        global $osec_app;
        $start = strtotime('+2 days 10:00');
        $event = new Event(
            $osec_app,
            [
                'start'         => new DT($start, 'UTC'),
                'end'           => new DT($start + HOUR_IN_SECONDS, 'UTC'),
                'allday'        => false,
                'instant_event' => false,
                'timezone_name' => 'UTC',
                'post'          => [
                    'post_status' => 'publish',
                    'post_type'   => OSEC_POST_TYPE,
                    'post_title'  => 'Colored event',
                ],
            ]
        );
        $event->save();
        $color = fn() => '#c0392b';
        add_filter('osec_event_color', $color);

        $style = EventTaxonomyView::factory($osec_app)->get_color_style($event);

        remove_filter('osec_event_color', $color);
        $this->assertSame('color: #c0392b !important; --osec-event-color: #c0392b;', $style);
    }
}
