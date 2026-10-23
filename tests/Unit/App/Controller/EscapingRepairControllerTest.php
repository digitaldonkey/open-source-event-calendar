<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\EscapingRepairController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;

/**
 * Admin notice of the escaping repair.
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

    public function test_notice_for_admins_until_dismissed_or_repaired()
    {
        global $osec_app;

        $this->insert_feed('https://example.com/cal.ics?a=1&amp;b=2');
        $controller = EscapingRepairController::factory($osec_app);
        $controller->maybe_check();

        $html = $this->render_notice();
        $this->assertStringContainsString('2 event or feed fields', $html, 'feed_url and feed_name.');
        $this->assertStringContainsString('page=osec-repair-escaping', $html);
        $this->assertStringContainsString('action=' . EscapingRepairController::ACTION_DISMISS, $html);
        $this->assertSame([], $this->stored_messages(), 'Not stored for all users.');

        // Dismissed for this admin only.
        $this->dismiss(OSEC_VERSION);
        $this->assertSame('', $this->render_notice());
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->assertNotSame('', $this->render_notice(), 'Another admin still gets it.');

        // Repaired: gone for everyone.
        $controller->clear_notice();
        $this->assertSame('', $this->render_notice());
    }

    public function test_dismissed_in_an_older_version_shows_again()
    {
        global $osec_app;

        $this->insert_feed('https://example.com/cal.ics?a=1&amp;b=2');
        EscapingRepairController::factory($osec_app)->maybe_check();

        $this->dismiss('0.0.1');
        $this->assertNotSame('', $this->render_notice());
    }

    /**
     * Editors can edit events but not run the repair: no notice, no check.
     */
    public function test_no_notice_and_no_check_for_editors()
    {
        global $osec_app;

        $this->insert_feed('https://example.com/cal.ics?a=1&amp;b=2');
        EscapingRepairController::factory($osec_app)->maybe_check();

        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        $this->assertSame('', $this->render_notice());

        $osec_app->options->delete(EscapingRepairController::OPTION);
        EscapingRepairController::factory($osec_app)->maybe_check();
        $this->assertNull($osec_app->options->get(EscapingRepairController::OPTION));
    }

    public function test_no_notice_without_corrupt_fields()
    {
        global $osec_app;

        $this->insert_feed(self::FEED_URL);
        EscapingRepairController::factory($osec_app)->maybe_check();

        $this->assertSame('', $this->render_notice());
        $this->assertSame(OSEC_VERSION, $osec_app->options->get(EscapingRepairController::OPTION)['version']);
    }

    /**
     * Notice output on the Plugins screen, where importance 1 notices show.
     */
    private function render_notice(): string
    {
        global $osec_app;

        set_current_screen('plugins');
        ob_start();
        EscapingRepairController::factory($osec_app)->render_notice();

        return trim((string)ob_get_clean());
    }

    private function dismiss(string $version): void
    {
        update_user_meta(get_current_user_id(), EscapingRepairController::USER_META_DISMISSED, $version);
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
