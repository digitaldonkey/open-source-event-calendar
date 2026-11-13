<?php

namespace Osec\Tests\Unit\Command;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Command\RenderEvent;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;
use RuntimeException;
use WPDieException;

/**
 * The event page reads instance_id from the URL, where a visitor or crawler can write anything (#74).
 *
 * A wrong value (casting to less than 1, or an array) is a 400. A number that is no date of this event (unknown, re-timed,
 * another event's) redirects to the event with a notice. Missing and empty stay as before.
 * Values are templates: {second} is the event's second occurrence, {other} an occurrence of another event.
 */
class RenderEventInstanceIdTest extends TestBase
{
    private array $saved_request = [];

    public function set_up()
    {
        parent::set_up();
        $this->saved_request = $_REQUEST;
    }

    public function tear_down()
    {
        $_REQUEST = $this->saved_request;
        parent::tear_down();
    }

    /**
     * @dataProvider values_selecting_the_second_occurrence
     *
     * @param  string  $instance_id  Value template of the instance_id URL parameter.
     */
    public function test_event_page_shows_the_occurrence_the_value_casts_to(string $instance_id)
    {
        $post_id = $this->create_weekly_event();
        $second  = $this->instance_ids($post_id)[1];

        $result = $this->render_event_page($post_id, str_replace('{second}', (string) $second, $instance_id));

        self::assertStringContainsString('ai1ec-event-instance-id-' . $second . '"', $result['data']);
        self::assertStringNotContainsString('ai1ec-alert', $result['data']);
    }

    public static function values_selecting_the_second_occurrence(): array
    {
        return [
            'number'            => ['{second}'],
            'number, then text' => ['{second}junk'],
        ];
    }

    /**
     * Missing and empty render the event as before the fix.
     *
     * @dataProvider values_rendering_the_event
     *
     * @param  string|null  $instance_id  Value of the instance_id URL parameter, null to leave it out.
     */
    public function test_event_page_renders_the_event(?string $instance_id)
    {
        $result = $this->render_event_page($this->create_weekly_event(), $instance_id);

        self::assertTrue($result['is_event']);
        self::assertStringContainsString('Weekly event', $result['data']);
    }

    public static function values_rendering_the_event(): array
    {
        return [
            'no parameter' => [null],
            'empty'        => [''],
            'only spaces'  => ['   '],
        ];
    }

    /**
     * @dataProvider wrong_values
     *
     * @param  string|array  $instance_id  Value of the instance_id URL parameter.
     */
    public function test_event_page_answers_400_for_a_wrong_value(string|array $instance_id)
    {
        $post_id = $this->create_weekly_event();
        try {
            $this->render_event_page($post_id, $instance_id);
            self::fail('No 400 for instance_id ' . var_export($instance_id, true));
        } catch (WPDieException $e) {
            self::assertSame(400, $e->getCode());
            self::assertStringContainsString('positive number', $e->getMessage());
        }
    }

    public static function wrong_values(): array
    {
        return [
            'text'            => ['junk'],
            'zero'            => ['0'],
            'negative number' => ['-5'],
            'minus one'       => ['-1'],
            'hexadecimal'     => ['0x1A'],
            'array'           => [['1']],
        ];
    }

    /**
     * @dataProvider values_of_no_date_of_this_event
     *
     * @param  string  $instance_id  Value template of the instance_id URL parameter.
     */
    public function test_event_page_redirects_when_the_value_is_no_date_of_the_event(string $instance_id)
    {
        $post_id     = $this->create_weekly_event();
        $other       = $this->instance_ids($this->create_weekly_event())[0];
        $instance_id = str_replace('{other}', (string) $other, $instance_id);

        $redirect = null;
        $stop     = static function ($location, $status) use (&$redirect) {
            $redirect = [$location, $status];
            throw new RuntimeException('redirect');
        };
        add_filter('wp_redirect', $stop, 10, 2);
        try {
            $this->render_event_page($post_id, $instance_id, ['utm_source' => 'x']);
            self::fail('No redirect for instance_id ' . $instance_id);
        } catch (RuntimeException $e) {
            self::assertSame('redirect', $e->getMessage());
        } finally {
            remove_filter('wp_redirect', $stop, 10);
        }

        [$location, $status] = $redirect;
        self::assertSame(301, $status);
        self::assertStringNotContainsString('instance_id', $location);
        self::assertStringContainsString('osec_date_not_found=1', $location);
        self::assertStringContainsString('utm_source=x', $location, 'Other parameters are kept.');
    }

    public static function values_of_no_date_of_this_event(): array
    {
        return [
            'unknown ID'            => ['999999999'],
            'beyond PHP_INT_MAX'    => ['99999999999999999999999'],
            'another event\'s date' => ['{other}'],
        ];
    }

    /**
     * The redirect's target shows the event with the notice.
     */
    public function test_event_page_shows_the_notice_after_the_redirect()
    {
        $_REQUEST['osec_date_not_found'] = '1';

        $result = $this->render_event_page($this->create_weekly_event(), null);

        self::assertStringContainsString('ai1ec-alert', $result['data']);
        self::assertStringContainsString('Showing the main event instead.', $result['data']);
        self::assertStringContainsString('Weekly event', $result['data']);
    }

    /**
     * Saves a published weekly event with three occurrences.
     *
     * @return int Post ID.
     */
    private function create_weekly_event(): int
    {
        global $osec_app;

        return (new Event(
            $osec_app,
            [
                'post'             => [
                    'post_status' => 'publish',
                    'post_type'   => OSEC_POST_TYPE,
                    'post_title'  => 'Weekly event',
                ],
                'start'            => new DT('2026-07-06 09:00:00', 'UTC'),
                'end'              => new DT('2026-07-06 10:00:00', 'UTC'),
                'allday'           => false,
                'instant_event'    => false,
                'timezone_name'    => 'UTC',
                'recurrence_rules' => 'FREQ=WEEKLY;COUNT=3',
            ]
        ))->save();
    }

    /**
     * The event's instance IDs, by start.
     *
     * @param  int  $post_id  Event post.
     *
     * @return int[] Instance IDs.
     */
    private function instance_ids(int $post_id): array
    {
        global $osec_app;

        $ids = array_map(
            'intval',
            $osec_app->db->get_col(
                $osec_app->db->prepare(
                    'SELECT id FROM ' . $osec_app->db->get_table_name(OSEC_DB__INSTANCES) .
                    ' WHERE post_id = %d ORDER BY start ASC',
                    $post_id
                )
            )
        );
        self::assertCount(3, $ids);

        return $ids;
    }

    /**
     * Renders the event page as the router does.
     *
     * @param  int                $post_id      Event post.
     * @param  string|array|null  $instance_id  instance_id URL parameter, null to leave it out.
     * @param  array              $extra        Further URL parameters.
     *
     * @return array The command's result.
     */
    private function render_event_page(int $post_id, string|array|null $instance_id, array $extra = []): array
    {
        global $osec_app;

        $url = add_query_arg($extra, get_permalink($post_id));
        if (is_string($instance_id)) {
            $url = add_query_arg('instance_id', rawurlencode($instance_id), $url);
        }
        $this->go_to($url);
        unset($_REQUEST['instance_id']);
        if (null !== $instance_id) {
            $_REQUEST['instance_id'] = $instance_id;
        }

        $request = new RequestParser($osec_app, [], 'agenda');
        $request->parse();
        $command = new RenderEvent($osec_app, $request);
        $command->setRenderStrategy($request);

        return $command->do_execute();
    }
}
