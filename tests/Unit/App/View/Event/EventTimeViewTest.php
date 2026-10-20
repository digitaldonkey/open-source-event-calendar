<?php

namespace Osec\Tests\Unit\App\View\Event;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventTimeView;
use Osec\Tests\Utilities\TestBase;

/**
 * EventTimeView::get_timespan_html(): the 'backend' display mode (wp-admin) uses the input formats of
 * Settings > Editing events, the other modes the WordPress date and time formats.
 *
 * @group osec
 */
class EventTimeViewTest extends TestBase
{
    /** @var array Settings changed by the tests, restored in tear_down(): the settings object outlives a test. */
    private array $saved = [];

    public function set_up()
    {
        global $osec_app;
        parent::set_up();
        update_option('date_format', 'F j, Y');
        update_option('time_format', 'g:i a');
        foreach (['input_date_format', 'input_24h_time'] as $key) {
            $this->saved[$key] = $osec_app->settings->get($key);
        }
    }

    public function tear_down()
    {
        global $osec_app;
        foreach ($this->saved as $key => $value) {
            $osec_app->settings->set($key, $value);
        }
        parent::tear_down();
    }

    /**
     * An unsaved event; get_timespan_html() needs no database row.
     *
     * @param  bool  $allday
     * @param  string  $end  End date and time (Europe/Berlin).
     *
     * @return Event
     */
    private function event(bool $allday, string $end): Event
    {
        global $osec_app;

        return new Event(
            $osec_app,
            [
                'start'         => new DT('2026-10-16 15:00:00', 'Europe/Berlin'),
                'end'           => new DT($end, 'Europe/Berlin'),
                'allday'        => $allday ? 1 : 0,
                'timezone_name' => 'Europe/Berlin',
            ]
        );
    }

    /**
     * The timespan as plain text, non-breaking spaces (the separators use them) as plain ones.
     *
     * @param  string  $html
     *
     * @return string
     */
    private function text(string $html): string
    {
        return trim(preg_replace('/[\s\x{a0}]+/u', ' ', html_entity_decode(wp_strip_all_tags($html))));
    }

    public static function backendFormatProvider(): array
    {
        return [
            'default, 12h' => ['def', false, '16/10/2026 @ 3:00 pm - 5:00 pm'],
            'dotted, 24h'  => ['dot', true, '16.10.2026 @ 15:00 - 17:00'],
            'US, 24h'      => ['us', true, '10/16/2026 @ 15:00 - 17:00'],
            'ISO, 12h'     => ['iso', false, '2026-10-16 @ 3:00 pm - 5:00 pm'],
        ];
    }

    /**
     * @dataProvider backendFormatProvider
     */
    public function test_backend_uses_the_input_formats(string $date_format, bool $h24, string $expected)
    {
        global $osec_app;
        $osec_app->settings->set('input_date_format', $date_format);
        $osec_app->settings->set('input_24h_time', $h24);

        $html = EventTimeView::factory($osec_app)->get_timespan_html(
            $this->event(false, '2026-10-16 17:00:00'),
            'backend'
        );

        $this->assertSame($expected, $this->text($html));
    }

    public function test_backend_all_day_keeps_the_badge()
    {
        global $osec_app;
        $osec_app->settings->set('input_date_format', 'dot');

        $html = EventTimeView::factory($osec_app)->get_timespan_html(
            $this->event(true, '2026-10-17 00:00:00'),
            'backend'
        );

        $this->assertStringStartsWith('16.10.2026', $html);
        $this->assertStringContainsString('<span class="ai1ec-allday-badge">', $html);
    }

    public function test_long_keeps_the_wordpress_formats()
    {
        global $osec_app;
        $osec_app->settings->set('input_date_format', 'dot');
        $osec_app->settings->set('input_24h_time', true);

        $html = EventTimeView::factory($osec_app)->get_timespan_html(
            $this->event(false, '2026-10-16 17:00:00'),
            'long'
        );

        $this->assertSame(
            'October 16, 2026 @ 3:00 pm - 5:00 pm',
            $this->text($html)
        );
    }
}
