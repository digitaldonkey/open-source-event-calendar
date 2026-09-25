<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\EscapingRepairController;
use Osec\App\Controller\FeedsController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;
use Osec\Twig\TwigExtension;

/**
 * Admin side of the escaping repair, and the feed list it repairs.
 *
 * @group escaping
 */
class EscapingRepairControllerTest extends TestBase
{
    private const FEED_URL = 'https://example.com/cal.ics?a=1&b=2';

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->options->delete(EscapingRepairController::OPTION);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    public function test_notice_once_per_version_and_cleared_after_repair()
    {
        global $osec_app;

        $this->insert_feed('https://example.com/cal.ics?a=1&amp;b=2');
        $controller = EscapingRepairController::factory($osec_app);

        $controller->maybe_notify();
        $messages = $this->stored_messages();
        $this->assertCount(1, $messages);
        $notice = reset($messages);
        $this->assertStringContainsString('2 event or feed fields', $notice['message'], 'feed_url and feed_name.');
        $this->assertTrue($notice['persistent']);

        // Dismissed: the next admin request of the same version does not bring it back.
        NotificationAdmin::factory($osec_app)->remove($notice['msg_key']);
        $controller->maybe_notify();
        $this->assertSame([], $this->stored_messages());

        // New version, the notice is back until the repair clears it.
        $osec_app->options->set(EscapingRepairController::OPTION, ['version' => '0.0.1', 'message' => null]);
        $controller->maybe_notify();
        $this->assertCount(1, $this->stored_messages());
        $controller->clear_notice();
        $this->assertSame([], $this->stored_messages());
    }

    public function test_no_notice_without_corrupt_fields()
    {
        global $osec_app;

        $this->insert_feed(self::FEED_URL);
        EscapingRepairController::factory($osec_app)->maybe_notify();

        $this->assertSame([], $this->stored_messages());
        $this->assertSame(OSEC_VERSION, $osec_app->options->get(EscapingRepairController::OPTION)['version']);
    }

    public function test_no_check_without_capability()
    {
        global $osec_app;

        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        EscapingRepairController::factory($osec_app)->maybe_notify();

        $this->assertNull($osec_app->options->get(EscapingRepairController::OPTION));
    }

    /**
     * The feed list escapes once, so "Edit feed" re-submits the stored URL.
     */
    public function test_feed_row_escapes_once()
    {
        global $osec_app;

        $this->insert_feed(self::FEED_URL);
        $html = FeedsController::factory($osec_app)->getRows();

        $this->assertStringContainsString('value="https://example.com/cal.ics?a=1&amp;b=2"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function test_dismiss_notice_removes_a_persistent_notice()
    {
        global $osec_app;

        $notification = NotificationAdmin::factory($osec_app);
        $notification->store('<p>Keep me</p>', 'updated', 0, [NotificationAdmin::RCPT_ADMIN], true);
        $notification->store('<p>Dismiss me</p>', 'error', 1, [NotificationAdmin::RCPT_ADMIN], true);

        $_POST['key'] = NotificationAdmin::message_key('<p>Dismiss me</p>', 'error', 1, true);
        NotificationAdmin::factory($osec_app)->dismiss_notice();
        unset($_POST['key']);

        $this->assertSame(['<p>Keep me</p>'], array_column($this->stored_messages(), 'message'));
        $stored = $osec_app->options->get(NotificationAdmin::OPTION_KEY);
        $this->assertCount(1, $stored[NotificationAdmin::RCPT_ADMIN]);
    }

    /**
     * @dataProvider provide_urls
     */
    public function test_esc_url_twig_filter(mixed $url, string $expected)
    {
        $this->assertSame($expected, TwigExtension::esc_url($url));
    }

    public function provide_urls(): array
    {
        return [
            'query string' => ['https://example.com/?a=1&b=2', 'https://example.com/?a=1&#038;b=2'],
            'javascript'   => ['javascript:alert(1)', ''],
            'null'         => [null, ''],
        ];
    }

    private function insert_feed(string $url): void
    {
        global $osec_app;

        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            ['feed_url' => $url, 'feed_name' => $url, 'feed_category' => '', 'feed_tags' => '']
        );
    }

    /**
     * @return array[] Stored notification entities.
     */
    private function stored_messages(): array
    {
        global $osec_app;

        return $osec_app->options->get(NotificationAdmin::OPTION_KEY, null)['_messages'] ?? [];
    }
}
