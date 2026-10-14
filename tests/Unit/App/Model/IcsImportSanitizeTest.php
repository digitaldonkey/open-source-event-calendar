<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\View\Event\EventSingleView;
use Osec\Tests\Utilities\HostileInput;
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
    use HostileInput;

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

    public static function hostile(): array
    {
        $values = [];
        foreach (self::hostile_values() as $label => $value) {
            // Feed text escapes "," and ";" (RFC 5545 3.3.11).
            $values[$label] = [addcslashes($value, ',;')];
        }

        return $values;
    }

    /**
     * Location, contact, organizer, cost and ticket URL are printed unescaped by the frontend
     * rendering (twig.js), so a feed must not be able to store markup or a script URL in them.
     *
     * @dataProvider hostile
     */
    public function test_event_fields_from_a_feed_are_plain_text(string $value)
    {
        $event = $this->import_event([
            'LOCATION:' . $value,
            'CONTACT:' . $value . '\\;' . $value . ' 1\\;' . $value . '@example.org\\;javascript://x%0Aalert(1)',
            'ORGANIZER;CN="' . str_replace('"', '', $value) . '":mailto:o@example.org',
            'X-COST:' . $value,
            'X-TICKETS-URL:' . $value,
        ]);

        foreach (['venue', 'address', 'contact_name', 'contact_phone', 'contact_email', 'cost'] as $field) {
            self::assertNoTag($event->get($field), $field);
        }
        foreach (['contact_url', 'ticket_url'] as $field) {
            self::assertSafeUrl($event->get($field), $field);
        }
    }

    public function test_venue_keeps_ampersands_and_quotes()
    {
        $event = $this->import_event(['LOCATION:D&D "Hall"']);

        $this->assertSame('D&D "Hall"', $event->get('venue'));
    }

    public function test_address_keeps_line_breaks()
    {
        $event = $this->import_event(['LOCATION:Main St 1\\nBerlin\\, Germany']);

        $this->assertSame("Main St 1\nBerlin, Germany", $event->get('address'));
    }

    public function test_safe_ticket_and_contact_urls_are_kept()
    {
        $event = $this->import_event([
            'CONTACT:Jane\\;https://example.org/contact?a=1&b=2',
            'X-TICKETS-URL:https://example.org/tickets?a=1&b=2',
        ]);

        $this->assertSame('https://example.org/contact?a=1&b=2', $event->get('contact_url'));
        $this->assertSame('https://example.org/tickets?a=1&b=2', $event->get('ticket_url'));
    }

    public function test_source_url_is_imported_and_linked()
    {
        global $osec_app;

        $event = $this->import_event(['URL:https://example.org/event?a=1&b=2']);

        $this->assertSame('https://example.org/event?a=1&b=2', $event->get('ical_source_url'));
        $this->assertStringContainsString(
            'href="https://example.org/event?a=1&#038;b=2"',
            EventSingleView::factory($osec_app)->get_footer($event)
        );
    }

    private function import_event(array $lines): Event
    {
        global $osec_app;

        $post = $this->import('SUMMARY:Fields', 'DESCRIPTION:x', $lines);

        return new Event($osec_app, $post->ID);
    }

    private function import(string $summary, string $description, array $lines = []): \WP_Post
    {
        global $osec_app;

        $extra = implode('', array_map(fn($line) => $line . "\r\n", $lines));

        IcsImportExportParser::factory($osec_app)->import([
            'events_in_db'   => [],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\n" .
                "BEGIN:VEVENT\r\nUID:sanitize@example.org\r\nDTSTAMP:20260901T100000Z\r\n" .
                "DTSTART:20261015T100000Z\r\nDTEND:20261015T110000Z\r\n{$summary}\r\n{$description}\r\n{$extra}" .
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
