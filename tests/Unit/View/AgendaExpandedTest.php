<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Controller\BlockController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;
use WP_Block_Type_Registry;

/**
 * T16: whether the agenda's events start expanded, per calendar. The site setting "Keep all events expanded" is the
 * default; a block (agendaEvents: global / expanded / collapsed, older blocks agendaToggle) or a shortcode
 * (agenda_expanded, older spelling agenda_toggle) can override it. The class choosing the visible Expand all /
 * Collapse all button follows the same value, not the site setting.
 *
 * @group agenda
 */
class AgendaExpandedTest extends TestBase
{
    private const BLOCK = 'open-source-event-calendar/osec-calendar-classic';

    private bool $saved_setting;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_setting = (bool) $osec_app->settings->get('agenda_events_expanded');
        if ( ! WP_Block_Type_Registry::get_instance()->is_registered(self::BLOCK)) {
            BlockController::factory($osec_app)->registerCalendarBlock();
        }
        $this->create_event();
        $this->go_to(get_permalink(self::factory()->post->create(['post_type' => 'page'])));
    }

    public function tear_down()
    {
        global $osec_app;

        $osec_app->settings->set('agenda_events_expanded', $this->saved_setting);
        parent::tear_down();
    }

    public static function cases(): array
    {
        return [
            'block, setting expanded'            => [true, 'block', '', true],
            'block, setting collapsed'           => [false, 'block', '', false],
            'block collapsed, setting expanded'  => [true, 'block', '"agendaEvents":"collapsed"', false],
            'block expanded, setting collapsed'  => [false, 'block', '"agendaEvents":"expanded"', true],
            'older block switched on'            => [false, 'block', '"agendaToggle":true', true],
            'shortcode, setting expanded'        => [true, 'shortcode', '', true],
            'shortcode agenda_expanded false'    => [true, 'shortcode', 'agenda_expanded="false"', false],
            'shortcode agenda_expanded true'     => [false, 'shortcode', 'agenda_expanded="true"', true],
            'shortcode agenda_toggle false'      => [true, 'shortcode', 'agenda_toggle="false"', false],
        ];
    }

    /**
     * @dataProvider cases
     */
    public function test_agenda_events_and_button_follow_the_calendar(
        bool $setting,
        string $kind,
        string $option,
        bool $expanded
    ) {
        global $osec_app;
        $osec_app->settings->set('agenda_events_expanded', $setting);

        $html = 'block' === $kind
            ? do_blocks('<!-- wp:' . self::BLOCK . ' {"view":"agenda"' . ($option ? ',' . $option : '') . '} /-->')
            : do_shortcode('[osec view="agenda" ' . $option . ']');

        $this->assertStringContainsString(
            $expanded ? 'osec-all-events-expanded' : 'osec-all-events-collapsed',
            $html,
            'class choosing the visible Expand/Collapse all button'
        );
        $this->assertSame(! $expanded, str_contains($html, 'ai1ec-agenda-expand-all'), 'Expand/Collapse all buttons');
    }

    /**
     * One event in the coming days, so the agenda shows events and its Expand/Collapse all buttons.
     */
    private function create_event(): void
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish']);
        $start   = new DT('+2 days 10:00', 'UTC');
        $end     = new DT('+2 days 12:00', 'UTC');
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => $start,
                'end'              => $end,
                'allday'           => 0,
                'timezone_name'    => 'UTC',
                'recurrence_rules' => '',
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        ))->save(false);
    }
}
