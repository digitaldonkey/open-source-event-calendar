<?php

namespace Osec\Tests\Unit\App\Model\Notifications;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;
use WPDieException;

/**
 * "Got it – dismiss this" removes a persistent notice, for users who may.
 *
 * @group notifications
 * @group security
 */
class NotificationAdminDismissTest extends TestBase
{
    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $notification = NotificationAdmin::factory($osec_app);
        $notification->store('<p>Keep me</p>', 'updated', 0, [NotificationAdmin::RCPT_ADMIN], true);
        $notification->store('<p>Dismiss me</p>', 'error', 0, [NotificationAdmin::RCPT_ADMIN], true);

        // As in admin-ajax.php. Outside AJAX check_ajax_referer() calls die(),
        // which would end PHPUnit with exit code 0.
        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', fn() => [$this, 'throw_die']);
    }

    public function throw_die(mixed $message): void
    {
        throw new WPDieException((string)$message);
    }

    public function tear_down()
    {
        remove_all_filters('wp_doing_ajax');
        remove_all_filters('wp_die_ajax_handler');
        $_POST    = [];
        $_REQUEST = [];
        parent::tear_down();
    }

    public function test_admin_dismisses_one_notice()
    {
        global $osec_app;

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->post($this->key_of('<p>Dismiss me</p>'), wp_create_nonce(NotificationAdmin::DISMISS_ACTION));

        NotificationAdmin::factory($osec_app)->dismiss_notice();

        $this->assertSame(['<p>Keep me</p>'], array_column($this->stored()['_messages'], 'message'));
        $this->assertCount(1, $this->stored()[NotificationAdmin::RCPT_ADMIN]);
    }

    public function test_without_valid_nonce_nothing_is_removed()
    {
        global $osec_app;

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->post($this->key_of('<p>Dismiss me</p>'), 'invalid');

        try {
            NotificationAdmin::factory($osec_app)->dismiss_notice();
            $this->fail('Expected wp_die().');
        } catch (WPDieException) {
            $this->assertCount(2, $this->stored()['_messages']);
        }
    }

    public function test_user_without_capability_cannot_dismiss()
    {
        global $osec_app;

        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        $this->post($this->key_of('<p>Dismiss me</p>'), wp_create_nonce(NotificationAdmin::DISMISS_ACTION));

        try {
            NotificationAdmin::factory($osec_app)->dismiss_notice();
            $this->fail('Expected wp_die().');
        } catch (WPDieException) {
            $this->assertCount(2, $this->stored()['_messages']);
        }
    }

    public function test_button_only_for_users_who_may_dismiss()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $html = $this->render();
        $this->assertStringContainsString('ai1ec-dismissable', $html);
        $this->assertMatchesRegularExpression('/data-nonce="[0-9a-f]{10}"/', $html);

        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        $this->assertStringNotContainsString('ai1ec-dismissable', $this->render());
    }

    private function post(string $key, string $nonce): void
    {
        $_POST    = ['key' => $key, 'nonce' => $nonce];
        $_REQUEST = $_POST;
    }

    private function key_of(string $message): string
    {
        foreach ($this->stored()['_messages'] as $key => $entity) {
            if ($entity['message'] === $message) {
                return $key;
            }
        }
        $this->fail('Message not stored: ' . $message);
    }

    private function stored(): array
    {
        global $osec_app;

        return $osec_app->options->get(NotificationAdmin::OPTION_KEY);
    }

    /**
     * Stored notices as printed on an events screen.
     */
    private function render(): string
    {
        global $osec_app;

        set_current_screen('edit-' . OSEC_POST_TYPE);
        $_GET['post_type'] = OSEC_POST_TYPE;
        // BootstrapController registers the dispatch in admin requests only.
        $send = [NotificationAdmin::factory($osec_app), 'send'];
        add_action('admin_notices', $send);
        ob_start();
        do_action('admin_notices');
        remove_action('admin_notices', $send);
        unset($_GET['post_type']);

        return (string)ob_get_clean();
    }
}
