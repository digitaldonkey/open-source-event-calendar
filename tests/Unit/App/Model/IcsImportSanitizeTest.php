<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * Feed content is untrusted: script in SUMMARY or DESCRIPTION must not be stored, whoever
 * runs the import - an administrator refreshing the feed has unfiltered_html, so WordPress
 * would not filter it on save.
 *
 * @group feeds
 */
class IcsImportSanitizeTest extends TestBase
{
    private const FEED_URL = 'https://example.org/sanitize.ics';

    public static function users(): array
    {
        return [
            'administrator' => ['administrator'],
            'no user (cron)' => [null],
        ];
    }

    /**
     * @dataProvider users
     */
    public function test_script_from_a_feed_is_not_stored(?string $role)
    {
        wp_set_current_user($role ? self::factory()->user->create(['role' => $role]) : 0);

        $post = $this->import(
            'SUMMARY:Fish & Chips <img src=x onerror=alert(1)>',
            'DESCRIPTION:<p class="lead">Live & loud</p><a href="https://example.org/" onclick="alert(2)">tickets</a><script>alert(3)</script>'
        );

        // Without a user WordPress' own save filters also turn "&" into "&amp;".
        $this->assertSame('Fish & Chips', trim(html_entity_decode($post->post_title)));
        $this->assertStringNotContainsString('<', $post->post_title);
        $this->assertStringNotContainsString('<script', $post->post_content);
        $this->assertStringNotContainsString('onclick', $post->post_content);
        $this->assertStringContainsString('<a href="https://example.org/">tickets</a>', $post->post_content);
        $this->assertStringContainsString('<p class="lead">', $post->post_content);
    }

    public function test_plain_text_description_keeps_its_line_breaks()
    {
        $post = $this->import('SUMMARY:Plain', 'DESCRIPTION:first line\\nsecond line');

        $this->assertSame("first line\nsecond line", $post->post_content);
    }

    private function import(string $summary, string $description): \WP_Post
    {
        global $osec_app;

        IcsImportExportParser::factory($osec_app)->import([
            'events_in_db'   => [],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\n" .
                "BEGIN:VEVENT\r\nUID:sanitize@example.org\r\nDTSTAMP:20260901T100000Z\r\n" .
                "DTSTART:20261015T100000Z\r\nDTEND:20261015T110000Z\r\n{$summary}\r\n{$description}\r\n" .
                "END:VEVENT\r\nEND:VCALENDAR\r\n",
            'feed'           => (object)[
                'feed_id'              => '1',
                'feed_url'             => self::FEED_URL,
                'feed_name'            => 'sanitize',
                'feed_category'        => '',
                'feed_tags'            => '',
                'hide_cost'            => '0',
                'comments_enabled'     => '0',
                'map_display_enabled'  => '0',
                'keep_tags_categories' => '1',
                'keep_old_events'      => '0',
                'import_timezone'      => '0',
                'import_post_status'   => 'publish',
            ],
        ]);
        $ids = EventSearch::factory($osec_app)->get_event_ids_for_feed(self::FEED_URL);
        $this->assertCount(1, $ids);

        return get_post((int)$ids[0]);
    }
}
