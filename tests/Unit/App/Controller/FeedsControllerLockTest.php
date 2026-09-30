<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FeedsController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;
use RuntimeException;

/**
 * The import lock of FeedsController::update_ics(), behind cron, the admin
 * button and `wp osec feed update`.
 *
 * @group feeds
 */
class FeedsControllerLockTest extends TestBase
{
    private const FEED_URL = 'https://example.org/feed_recurrence.ics';

    private int $feed_id;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            ['feed_url' => self::FEED_URL, 'feed_name' => 'Feed', 'feed_category' => '', 'feed_tags' => ''],
        );
        $this->feed_id = (int)$osec_app->db->get_insert_id();
        add_filter('pre_http_request', [$this, 'serve_fixture'], 10, 3);
    }

    /**
     * ExecutionLimitController::acquire() commits, which ends the transaction
     * the test framework rolls back. Clean up after the rollback and commit it.
     */
    public function tear_down()
    {
        global $osec_app, $wpdb;

        remove_filter('pre_http_request', [$this, 'serve_fixture'], 10);
        parent::tear_down();

        $db = $osec_app->db;
        foreach (EventSearch::factory($osec_app)->get_event_ids_for_feed(self::FEED_URL) as $post_id) {
            wp_delete_post((int)$post_id, true);
            $db->delete(OSEC_DB__EVENTS, ['post_id' => $post_id], ['%d']);
            $db->delete(OSEC_DB__INSTANCES, ['post_id' => $post_id], ['%d']);
        }
        $db->delete(OSEC_DB__FEEDS, ['feed_url' => self::FEED_URL], ['%s']);
        delete_option('osec_xlock_ics_import_' . $this->feed_id);
        $wpdb->query('COMMIT');
    }

    public function serve_fixture($pre, $args, $url)
    {
        if (self::FEED_URL !== $url) {
            return $pre;
        }

        return [
            'headers'  => [],
            'body'     => file_get_contents(__DIR__ . '/../Model/ical_feeds/feed_recurrence.ics'),
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    }

    public function test_update_imports_and_releases_the_lock()
    {
        global $osec_app;

        $feeds  = FeedsController::factory($osec_app);
        $result = $feeds->update_ics($this->feed_id)['data'];

        $this->assertFalse($result['error'], $result['message']);
        $this->assertNotEmpty(EventSearch::factory($osec_app)->get_event_ids_for_feed(self::FEED_URL));
        $this->assertNull($feeds->get_import_lock($this->feed_id));
    }

    /**
     * D1: a process that does not get the lock must not free it for others.
     */
    public function test_a_failed_acquire_keeps_the_holders_lock()
    {
        global $osec_app;

        $this->hold_lock();
        $feeds = FeedsController::factory($osec_app);

        $result = $feeds->update_ics($this->feed_id)['data'];

        $this->assertTrue($result['error']);
        $this->assertStringContainsString('Another import process', $result['message']);
        $this->assertSame(4711, $feeds->get_import_lock($this->feed_id)['pid'], 'Still held by the first process.');
        $this->assertSame([], EventSearch::factory($osec_app)->get_event_ids_for_feed(self::FEED_URL));
    }

    public function test_force_breaks_a_held_lock()
    {
        global $osec_app;

        $this->hold_lock();
        $feeds = FeedsController::factory($osec_app);

        $result = $feeds->update_ics($this->feed_id, true)['data'];

        $this->assertFalse($result['error'], $result['message']);
        $this->assertNotEmpty(EventSearch::factory($osec_app)->get_event_ids_for_feed(self::FEED_URL));
        $this->assertNull($feeds->get_import_lock($this->feed_id));
    }

    /**
     * D3: an exception the import does not catch must not leave the feed locked.
     */
    public function test_an_exception_releases_the_lock()
    {
        global $osec_app;

        $throw = function () {
            throw new RuntimeException('Import broke');
        };
        add_action('osec_ics_before_import', $throw);
        $feeds = FeedsController::factory($osec_app);

        try {
            $feeds->update_ics($this->feed_id);
            $this->fail('The exception reaches the caller.');
        } catch (RuntimeException $e) {
            $this->assertSame('Import broke', $e->getMessage());
        } finally {
            remove_action('osec_ics_before_import', $throw);
        }

        $this->assertNull($feeds->get_import_lock($this->feed_id));
    }

    /**
     * The parser is shared within a process, so it must not carry the overrides
     * of one import into the next ("There must be only one parent.").
     */
    public function test_a_feed_with_overrides_imports_twice_in_one_process()
    {
        global $osec_app;

        $feeds = FeedsController::factory($osec_app);

        $this->assertFalse($feeds->update_ics($this->feed_id)['data']['error']);
        $this->assertFalse($feeds->update_ics($this->feed_id)['data']['error']);
    }

    public function test_get_feeds()
    {
        global $osec_app;

        $feeds = FeedsController::factory($osec_app);

        $this->assertSame([(string)$this->feed_id], wp_list_pluck($feeds->get_feeds([$this->feed_id]), 'feed_id'));
        $this->assertSame([], $feeds->get_feeds([999999]));
        $this->assertContains((string)$this->feed_id, wp_list_pluck($feeds->get_feeds(), 'feed_id'));
    }

    private function hold_lock(): void
    {
        update_option(
            'osec_xlock_ics_import_' . $this->feed_id,
            wp_json_encode(['time' => time(), 'pid' => 4711]),
            false
        );
    }
}
