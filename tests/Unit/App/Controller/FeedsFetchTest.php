<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FeedsController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;

/**
 * How a feed is fetched.
 *
 * @group feeds
 */
class FeedsFetchTest extends TestBase
{
    private const FEED_URL = 'https://example.org/fetch.ics';

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
    }

    /**
     * Without certificate checks anyone on the network path could inject events.
     */
    public function test_certificates_are_verified()
    {
        global $osec_app;

        $seen    = null;
        $capture = function ($pre, $args, $url) use (&$seen) {
            if (self::FEED_URL !== $url) {
                return $pre;
            }
            $seen = $args;

            return new \WP_Error('http_request_failed', 'stubbed');
        };
        add_filter('pre_http_request', $capture, 10, 3);

        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            ['feed_url' => self::FEED_URL, 'feed_name' => 'Feed', 'feed_category' => '', 'feed_tags' => ''],
        );
        FeedsController::factory($osec_app)->process_ics_feed_update((int)$osec_app->db->get_insert_id());
        remove_filter('pre_http_request', $capture, 10);

        $this->assertIsArray($seen, 'The feed was requested.');
        $this->assertTrue($seen['sslverify']);
    }
}
