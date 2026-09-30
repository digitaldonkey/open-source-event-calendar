<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\BigCalendarBlockController;
use Osec\App\Controller\RestControllerDays;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;
use WP_Block_Type_Registry;
use WP_REST_Request;

/**
 * The React Big Calendar block and the REST routes the blocks use.
 */
class BlocksRestTest extends TestBase
{
    private const BIG_CALENDAR_BLOCK = 'open-source-event-calendar/react-big-calendar';

    public function set_up()
    {
        parent::set_up();
        global $osec_app;
        if (! WP_Block_Type_Registry::get_instance()->is_registered(self::BIG_CALENDAR_BLOCK)) {
            BigCalendarBlockController::factory($osec_app)->registerCalendarBlock();
        }
    }

    private function get(string $route, array $params = []): \WP_REST_Response
    {
        $request = new WP_REST_Request('GET', $route);
        $request->set_query_params($params);

        return rest_do_request($request);
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

    public function test_settings_route_answers_logged_in_users()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));
        $response = $this->get('/osec/v1/settings');

        $this->assertSame(200, $response->get_status());
        $this->assertArrayHasKey('dateFormat', $response->get_data());
    }

    public function test_settings_route_refuses_anonymous()
    {
        wp_set_current_user(0);

        $this->assertSame(401, $this->get('/osec/v1/settings')->get_status());
    }

    public function test_days_route_returns_published_events_in_range_only()
    {
        wp_set_current_user(0);
        $this->create_event('In range', '2026-07-06 09:00:00');
        $this->create_event('Out of range', '2026-09-06 09:00:00');
        $this->create_event('Draft', '2026-07-07 09:00:00', 'draft');

        $response = $this->get('/osec/v1/days', [
            'start' => (string) strtotime('2026-07-01 00:00:00 UTC'),
            'end'   => (string) strtotime('2026-08-01 00:00:00 UTC'),
        ]);

        $this->assertSame(200, $response->get_status());
        $titles = array_map(fn($event) => $event->title, $response->get_data()['events']);
        $this->assertSame(['In range'], $titles);
    }

    public function test_days_route_rejects_a_range_longer_than_the_limit()
    {
        $start    = strtotime('2026-01-01 00:00:00 UTC');
        $response = $this->get('/osec/v1/days', [
            'start' => (string) $start,
            'end'   => (string) ($start + (RestControllerDays::MAX_RANGE_DAYS + 1) * DAY_IN_SECONDS),
        ]);

        $this->assertSame(400, $response->get_status());
        $this->assertSame('osec_invalid_range', $response->as_error()->get_error_code());
    }

    public function test_days_route_rejects_a_reversed_range()
    {
        $start    = strtotime('2026-01-10 00:00:00 UTC');
        $response = $this->get('/osec/v1/days', [
            'start' => (string) $start,
            'end'   => (string) ($start - DAY_IN_SECONDS),
        ]);

        $this->assertSame(400, $response->get_status());
    }

    public function test_big_calendar_block_renders_an_escaped_mount_point()
    {
        $html = do_blocks(serialize_block([
            'blockName'    => self::BIG_CALENDAR_BLOCK,
            'attrs'        => ['view' => 'month', 'fixedDate' => '"><script>alert(1)</script>'],
            'innerBlocks'  => [],
            'innerHTML'    => '',
            'innerContent' => [],
        ]));

        $this->assertMatchesRegularExpression('/<div [^>]*class="[^"]*osec-react-big-calendar/', $html);
        $this->assertMatchesRegularExpression('/ id="osec-react-big-calendar-\d+"/', $html);
        $this->assertStringNotContainsString('<script>', $html);

        preg_match('/data-props="([^"]*)"/', $html, $match);
        $props = json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
        $this->assertSame('month', $props['view']);
        $this->assertSame('"><script>alert(1)</script>', $props['fixedDate']);
        $this->assertStringStartsWith('osec-react-big-calendar-', $props['id']);
    }
}
