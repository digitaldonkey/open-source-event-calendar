<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * A text longer than its column is shortened, instead of failing the save - and with
 * it a whole feed import ("Error saving Post Data").
 *
 * @group event
 */
class EventFieldLengthTest extends TestBase
{
    public function test_long_values_are_shortened_to_the_column()
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $event   = new Event($osec_app, [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT('2026-11-01 10:00:00', 'Europe/Berlin'),
            'end'              => new DT('2026-11-01 11:00:00', 'Europe/Berlin'),
            'allday'           => 0,
            'timezone_name'    => 'Europe/Berlin',
            'venue'            => str_repeat('Ü', 300),
            'contact_phone'    => 'Tel: +1-919-555-1234 (office, ask for Jane)',
            'recurrence_rules' => '',
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => '',
        ]);
        $event->save(false);

        $saved = new Event($osec_app, $post_id);
        $this->assertSame(str_repeat('Ü', 255), $saved->get('venue'));
        $this->assertSame('Tel: +1-919-555-1234 (office, as', $saved->get('contact_phone'));
    }

    /**
     * A CONTACT part with a digit counts as phone; a long one broke the import.
     */
    public function test_feed_with_a_long_contact_is_imported()
    {
        global $osec_app;

        $url = 'https://example.org/long-contact.ics';
        IcsImportExportParser::factory($osec_app)->import([
            'events_in_db'   => [],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\nBEGIN:VEVENT\r\n" .
                "UID:long-contact@example.org\r\nDTSTAMP:20260901T100000Z\r\nDTSTART:20261015T100000Z\r\n" .
                "DTEND:20261015T110000Z\r\nSUMMARY:Long contact\r\n" .
                "CONTACT:Jane Smith\\, Tel: +1-919-555-1234 (office\\, 3rd floor)\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
            'feed'           => (object)[
                'feed_id'              => '1',
                'feed_url'             => $url,
                'feed_name'            => 'long contact',
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

        $this->assertCount(1, EventSearch::factory($osec_app)->get_event_ids_for_feed($url));
    }
}
