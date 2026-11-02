<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\ExclusionRepairController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;
use RuntimeException;
use WPDieException;

/**
 * Notice and action of the exclusion repair.
 *
 * @group event
 */
class ExclusionRepairControllerTest extends TestBase
{
    private array $saved_request = [];

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_request = [$_POST, $_REQUEST];
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->options->delete(ExclusionRepairController::OPTION);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    public function tear_down()
    {
        [$_POST, $_REQUEST] = $this->saved_request;
        wp_set_current_user(0);
        parent::tear_down();
    }

    public function test_no_notice_without_affected_events()
    {
        global $osec_app;

        ExclusionRepairController::factory($osec_app)->maybe_check();

        self::assertSame([], $this->notices());
    }

    public function test_notice_links_to_the_review_page_once_per_version()
    {
        global $osec_app;

        $this->create_broken_event();
        $controller = ExclusionRepairController::factory($osec_app);
        $controller->maybe_check();

        $notices = $this->notices();
        self::assertCount(1, $notices);
        self::assertStringContainsString('page=osec-repair-exclusions', array_values($notices)[0]['message']);

        NotificationAdmin::factory($osec_app)->remove(array_key_first($notices));
        $controller->maybe_check();
        self::assertSame([], $this->notices(), 'A dismissed notice stays away in this version.');
    }

    public function test_user_without_the_capability_gets_no_notice()
    {
        global $osec_app;

        $this->create_broken_event();
        wp_set_current_user(self::factory()->user->create(['role' => 'osec_event_assistant']));

        ExclusionRepairController::factory($osec_app)->maybe_check();

        self::assertSame([], $this->notices());
    }

    public function test_notice_goes_when_nothing_is_left_to_repair()
    {
        global $osec_app, $wpdb;

        $post_id    = $this->create_broken_event();
        $controller = ExclusionRepairController::factory($osec_app);
        $controller->maybe_check();

        $controller->clear_notice_if_done();
        self::assertCount(1, $this->notices(), 'Still affected.');

        $wpdb->update($wpdb->prefix . 'osec_events', ['exception_rules' => ''], ['post_id' => $post_id]);
        $controller->clear_notice_if_done();
        self::assertSame([], $this->notices());
    }

    public function test_action_needs_the_capability()
    {
        global $osec_app;

        $post_id = $this->create_broken_event();
        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        $_REQUEST['_wpnonce'] = wp_create_nonce(ExclusionRepairController::ACTION);

        $this->expectException(WPDieException::class);
        try {
            ExclusionRepairController::factory($osec_app)->handle_repair();
        } finally {
            self::assertStringContainsString('EXDATE', (string)(new Event($osec_app, $post_id))->get('exception_rules'));
            self::assertStringContainsString('FREQ', (string)(new Event($osec_app, $post_id))->get('exception_rules'));
        }
    }

    public function test_action_needs_the_nonce()
    {
        global $osec_app;

        $this->create_broken_event();
        $_REQUEST['_wpnonce'] = 'wrong';

        $this->expectException(WPDieException::class);
        ExclusionRepairController::factory($osec_app)->handle_repair();
    }

    public function test_action_repairs_and_reports()
    {
        global $osec_app;

        $post_id    = $this->create_broken_event();
        $controller = ExclusionRepairController::factory($osec_app);
        $controller->maybe_check();
        $_REQUEST['_wpnonce'] = wp_create_nonce(ExclusionRepairController::ACTION);
        $stop = static function () {
            throw new RuntimeException('redirect');
        };
        add_filter('wp_redirect', $stop);

        try {
            $controller->handle_repair();
            self::fail('Expected a redirect.');
        } catch (RuntimeException $redirect) {
            self::assertSame('redirect', $redirect->getMessage());
        } finally {
            remove_filter('wp_redirect', $stop);
        }

        self::assertSame('EXDATE=20260112T000000Z', (new Event($osec_app, $post_id))->get('exception_rules'));
        $messages = array_column($this->notices(), 'message');
        self::assertCount(1, $messages, 'The offer is gone, the result is there.');
        self::assertStringContainsString('Repaired 1 event.', $messages[0]);
    }

    /**
     * @return array Stored notices by key.
     */
    private function notices(): array
    {
        global $osec_app;

        return $osec_app->options->get(NotificationAdmin::OPTION_KEY, [])['_messages'] ?? [];
    }

    /**
     * @return int Post ID of a series in the shape 1.1.15 to 1.2.0 stored.
     */
    private function create_broken_event(): int
    {
        global $osec_app, $wpdb;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                'end'              => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                'allday'           => 0,
                'timezone_name'    => 'Europe/Berlin',
                'recurrence_rules' => 'FREQ=WEEKLY;COUNT=4',
            ]
        ))->save(false);
        $wpdb->update(
            $wpdb->prefix . 'osec_events',
            [
                'exception_rules' => 'FREQ=WEEKLY;COUNT=4;EXDATE=20260112T000000Z',
                'exception_dates' => '20260112T000000Z',
            ],
            ['post_id' => $post_id]
        );

        return $post_id;
    }
}
