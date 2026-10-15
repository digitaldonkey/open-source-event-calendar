<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * An imported event without a contact name takes the ORGANIZER's.
 *
 * The fallback never ran since b9c458b3: its condition, `! (isset($x) || empty($x))`, is always false.
 *
 * @group feeds
 */
class IcsOrganizerContactTest extends TestBase
{
    public function test_organizer_name_is_the_contact_name()
    {
        $event = $this->import_one('ORGANIZER;CN=Kulturverein:mailto:info@verein.de');

        $this->assertSame('Kulturverein', $event->get('contact_name'));
    }

    public function test_organizer_address_is_the_contact_name_without_a_name()
    {
        $event = $this->import_one('ORGANIZER:mailto:info@verein.de');

        $this->assertSame('info@verein.de', $event->get('contact_name'));
    }

    public function test_contact_name_wins_over_the_organizer()
    {
        $event = $this->import_one("ORGANIZER;CN=Kulturverein:mailto:info@verein.de\r\nCONTACT:Jane Smith");

        $this->assertSame('Jane Smith', $event->get('contact_name'));
    }

    public function test_contact_without_a_name_falls_back_to_the_organizer()
    {
        $event = $this->import_one("ORGANIZER;CN=Kulturverein:mailto:info@verein.de\r\nCONTACT:jane@example.org");

        $this->assertSame('Kulturverein', $event->get('contact_name'));
        $this->assertSame('jane@example.org', $event->get('contact_email'));
    }

    private function import_one(string $lines): Event
    {
        global $osec_app;

        $url = 'https://example.org/organizer-' . md5($lines) . '.ics';
        IcsImportExportParser::factory($osec_app)->import([
            'events_in_db'   => [],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\nBEGIN:VEVENT\r\n" .
                "UID:organizer@example.org\r\nDTSTAMP:20260901T100000Z\r\nDTSTART:20261015T100000Z\r\n" .
                "DTEND:20261015T110000Z\r\nSUMMARY:Organizer\r\n{$lines}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
            'feed'           => (object) [
                'feed_id'              => '1',
                'feed_url'             => $url,
                'feed_name'            => 'organizer',
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
        $ids = EventSearch::factory($osec_app)->get_event_ids_for_feed($url);
        $this->assertCount(1, $ids);

        return new Event($osec_app, (int) reset($ids));
    }
}
