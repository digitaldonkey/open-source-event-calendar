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

    public function test_server_ca_list_is_used_when_enabled()
    {
        global $osec_app;

        $osec_app->settings->set('feeds_trust_server_ca', true);
        $seen = $this->fetch();
        $osec_app->settings->set('feeds_trust_server_ca', false);

        $this->assertTrue($seen['sslverify'], 'verification stays on');
        $this->assertFileIsReadable($seen['sslcertificates']);
        $this->assertStringContainsString(
            file_get_contents(ABSPATH . WPINC . '/certificates/ca-bundle.crt'),
            file_get_contents($seen['sslcertificates'])
        );
    }

    public function test_wordpress_ca_list_by_default()
    {
        $seen = $this->fetch();

        $this->assertTrue($seen['sslverify']);
        $this->assertArrayNotHasKey('osec_trusted_ca', $seen);
        $this->assertStringEndsWith('wp-includes/certificates/ca-bundle.crt', $seen['sslcertificates']);
    }

    /**
     * cURL error 60 points to the setting while it is off.
     */
    public function test_certificate_error_suggests_the_setting()
    {
        global $osec_app;

        $result = $this->fetch(
            new \WP_Error(
                'http_request_failed',
                'cURL error 60: SSL certificate problem: unable to get local issuer certificate'
            )
        );

        $this->assertStringContainsString("Trust this server's CA certificates for feeds", $result);
    }

    /**
     * Fetches a feed with a stubbed HTTP request.
     *
     * @return array|string The request arguments, or the result message when $response is given.
     */
    private function fetch(?\WP_Error $response = null): array|string
    {
        global $osec_app;

        $seen    = null;
        $capture = function ($pre, $args, $url) use (&$seen, $response) {
            if (self::FEED_URL !== $url) {
                return $pre;
            }
            $seen = $args;

            return $response ?? new \WP_Error('http_request_failed', 'stubbed');
        };
        add_filter('pre_http_request', $capture, 10, 3);
        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            ['feed_url' => self::FEED_URL, 'feed_name' => 'Feed', 'feed_category' => '', 'feed_tags' => ''],
        );
        $result = FeedsController::factory($osec_app)->process_ics_feed_update((int) $osec_app->db->get_insert_id());
        remove_filter('pre_http_request', $capture, 10);

        return $response ? wp_json_encode($result) : $seen;
    }
}
