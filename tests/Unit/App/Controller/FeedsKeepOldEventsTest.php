<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FeedsController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;

/**
 * "On refresh, preserve previously imported events that are missing from the
 * feed" (keep_old_events). Unchecked, a refresh deletes them.
 *
 * $wpdb returns the flag as the string "0", which a strict comparison against
 * the int 0 never matched, so from 1.0.7 events were never deleted.
 *
 * @group feeds
 */
class FeedsKeepOldEventsTest extends TestBase
{
    private const FEED_URL = 'https://example.org/keep-old-events.ics';

    private string $body = '';

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        add_filter('pre_http_request', [$this, 'serve_feed'], 10, 3);
    }

    public function tear_down()
    {
        remove_filter('pre_http_request', [$this, 'serve_feed'], 10);
        parent::tear_down();
    }

    public function serve_feed($pre, $args, $url)
    {
        if (self::FEED_URL !== $url) {
            return $pre;
        }

        return [
            'headers'  => [],
            'body'     => $this->body,
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    }

    public function test_events_missing_from_the_feed_are_deleted()
    {
        [$kept, $dropped] = $this->import_then_drop_one(0);

        $this->assertNotNull(get_post($kept), 'Still in the feed.');
        $this->assertNull(get_post($dropped), 'Gone from the feed, so deleted.');
    }

    public function test_events_missing_from_the_feed_are_kept_when_asked()
    {
        [$kept, $dropped] = $this->import_then_drop_one(1);

        $this->assertNotNull(get_post($kept));
        $this->assertNotNull(get_post($dropped), 'Preserved by keep_old_events.');
    }

    /**
     * Imports two events, then refreshes with only the first.
     *
     * Calls process_ics_feed_update() rather than update_ics(): the import lock
     * commits, which would end the transaction the test framework rolls back.
     *
     * @return int[] Post IDs of the event kept in the feed and the one dropped.
     */
    private function import_then_drop_one(int $keep_old_events): array
    {
        global $osec_app;

        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            [
                'feed_url'        => self::FEED_URL,
                'feed_name'       => 'Feed',
                'feed_category'   => '',
                'feed_tags'       => '',
                'keep_old_events' => $keep_old_events,
            ],
        );
        $feed_id = (int)$osec_app->db->get_insert_id();
        $feeds   = FeedsController::factory($osec_app);

        $this->body = $this->calendar(['kept', 'dropped']);
        $this->assertFalse($feeds->process_ics_feed_update($feed_id)['data']['error']);
        $kept    = $this->post_id('kept');
        $dropped = $this->post_id('dropped');
        $this->assertNotNull(get_post($dropped));

        $this->body = $this->calendar(['kept']);
        $this->assertFalse($feeds->process_ics_feed_update($feed_id)['data']['error']);

        return [$kept, $dropped];
    }

    private function calendar(array $uids): string
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\n";
        foreach ($uids as $i => $uid) {
            $day  = 10 + $i;
            $ics .= "BEGIN:VEVENT\r\nUID:{$uid}@example.org\r\nDTSTAMP:20260901T000000Z\r\n" .
                    "DTSTART:202610{$day}T090000Z\r\nDTEND:202610{$day}T100000Z\r\nSUMMARY:Event {$uid}\r\n" .
                    "END:VEVENT\r\n";
        }

        return $ics . "END:VCALENDAR\r\n";
    }

    private function post_id(string $uid): int
    {
        global $osec_app;

        $post_id = (int)$osec_app->db->get_var(
            $osec_app->db->prepare(
                'SELECT post_id FROM ' . $osec_app->db->get_table_name(OSEC_DB__EVENTS) .
                ' WHERE ical_feed_url = %s AND ical_uid = %s',
                self::FEED_URL,
                $uid . '@example.org'
            )
        );
        $this->assertGreaterThan(0, $post_id, "Event {$uid} was imported.");

        return $post_id;
    }
}
