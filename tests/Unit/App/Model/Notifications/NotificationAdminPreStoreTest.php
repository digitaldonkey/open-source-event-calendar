<?php

namespace Osec\Tests\Unit\App\Model\Notifications;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;

/**
 * The osec_admin_notification_pre_store filter, which the WP-CLI commands use to
 * print notices instead of storing them.
 *
 * @group notifications
 */
class NotificationAdminPreStoreTest extends TestBase
{
    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
    }

    public function test_a_non_null_return_short_circuits_storing()
    {
        global $osec_app;

        $seen     = [];
        $listener = function ($pre, $message, $class) use (&$seen) {
            $seen[] = [$message, $class];

            return 'handled';
        };
        add_filter('osec_admin_notification_pre_store', $listener, 10, 3);
        $returned = NotificationAdmin::factory($osec_app)->store('Feed failed', 'error');
        remove_filter('osec_admin_notification_pre_store', $listener, 10);

        $this->assertSame('handled', $returned);
        $this->assertSame([['Feed failed', 'error']], $seen);
        $this->assertSame([], $this->stored_messages());
    }

    public function test_null_stores_as_usual()
    {
        global $osec_app;

        $listener = fn($pre) => $pre;
        add_filter('osec_admin_notification_pre_store', $listener);
        NotificationAdmin::factory($osec_app)->store('Feed failed', 'error');
        remove_filter('osec_admin_notification_pre_store', $listener);

        $this->assertSame(['Feed failed'], array_column($this->stored_messages(), 'message'));
    }

    private function stored_messages(): array
    {
        global $osec_app;

        return array_values($osec_app->options->get(NotificationAdmin::OPTION_KEY, [])['_messages'] ?? []);
    }
}
