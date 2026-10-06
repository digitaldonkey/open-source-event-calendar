<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * A value longer than its column failed the save ("Error saving Post Data"), and with it a whole
 * feed import. Free text is shortened, other values are left out, both are reported; an imported
 * event whose UID does not fit is refused.
 *
 * @group event
 */
class EventFieldLengthTest extends TestBase
{
    /** @var array<int, array{string, int, bool}> */
    private array $reported = [];

    private $listener;

    public function set_up()
    {
        parent::set_up();
        $this->reported = [];
        $this->listener = function ($column, $length, $shortened) {
            $this->reported[] = [$column, $length, $shortened];
        };
        add_action('osec_event_value_too_long', $this->listener, 10, 3);
    }

    public function tear_down()
    {
        remove_action('osec_event_value_too_long', $this->listener, 10);
        parent::tear_down();
    }

    public function test_columns_have_the_agreed_lengths()
    {
        $expected = [
            'venue'          => 768,
            'address'        => 768,
            'contact_phone'  => 32,
            'postal_code'    => 32,
            'contact_email'  => 254,
            'contact_url'    => 768,
            'ticket_url'     => 768,
            'ical_organizer' => 768,
            'ical_contact'   => 768,
            'ical_uid'       => 768,
            'timezone_name'  => 50,
        ];
        foreach ($expected as $column => $length) {
            $this->assertSame($length, Event::column_length($column), $column);
        }
        $this->assertNull(Event::column_length('recurrence_rules'), 'longtext has no character length');
    }

    public function test_long_free_text_is_shortened_and_reported()
    {
        global $osec_app;

        $post_id = $this->save_event([
            'venue'         => str_repeat('Ü', 800),
            'contact_phone' => 'Tel: +1-919-555-1234 (office, ask for Jane)',
        ]);

        $saved = new Event($osec_app, $post_id);
        $this->assertSame(str_repeat('Ü', 768), $saved->get('venue'));
        $this->assertSame('Tel: +1-919-555-1234 (office, as', $saved->get('contact_phone'));
        $this->assertSame([['venue', 768, true], ['contact_phone', 32, true]], $this->reported);
    }

    public function test_too_long_url_is_not_stored_and_reported()
    {
        global $osec_app;

        $post_id = $this->save_event([
            'ticket_url'    => 'https://example.org/?q=' . str_repeat('a', 800),
            'contact_email' => 'jane@example.org',
        ]);

        $saved = new Event($osec_app, $post_id);
        $this->assertEmpty($saved->get('ticket_url'), 'a shortened URL would be a broken link');
        $this->assertSame('jane@example.org', $saved->get('contact_email'));
        $this->assertSame([['ticket_url', 768, false]], $this->reported);
    }

    public function test_values_that_fit_are_not_reported()
    {
        $this->save_event(['venue' => str_repeat('Ü', 768), 'ticket_url' => 'https://example.org/tickets']);

        $this->assertSame([], $this->reported);
    }

    /**
     * A CONTACT part with digits counted as phone, however long; it broke the import.
     */
    public function test_feed_contact_text_with_digits_is_kept_as_name()
    {
        global $osec_app;

        $url    = 'https://example.org/long-contact.ics';
        $output = $this->import($url, $this->vevent(
            'long-contact@example.org',
            "CONTACT:Jane Smith\\, Tel: +1-919-555-1234 (office\\, 3rd floor)\r\n"
        ));

        $ids = EventSearch::factory($osec_app)->get_event_ids_for_feed($url);
        $this->assertCount(1, $ids);
        $event = new Event($osec_app, (int) reset($ids));
        $this->assertSame('Jane Smith, Tel: +1-919-555-1234 (office, 3rd floor)', $event->get('contact_name'));
        $this->assertEmpty($event->get('contact_phone'));
        $this->assertSame([], $output['messages']);
    }

    public function test_feed_phone_number_is_still_detected()
    {
        global $osec_app;

        $url = 'https://example.org/phone.ics';
        $this->import($url, $this->vevent('phone@example.org', "CONTACT:Jane Smith;+1-919-555-1234\r\n"));

        $ids   = EventSearch::factory($osec_app)->get_event_ids_for_feed($url);
        $event = new Event($osec_app, (int) reset($ids));
        $this->assertSame('Jane Smith', $event->get('contact_name'));
        $this->assertSame('+1-919-555-1234', $event->get('contact_phone'));
    }

    /**
     * Shortened, the UID would never match again and every re-import would duplicate the event.
     */
    public function test_feed_event_with_a_too_long_uid_is_refused_and_reported()
    {
        global $osec_app;

        $url    = 'https://example.org/long-uid.ics';
        $output = $this->import(
            $url,
            $this->vevent(str_repeat('u', 769) . '@example.org', '') .
            $this->vevent('short@example.org', '', '20261016T100000Z')
        );

        $ids = EventSearch::factory($osec_app)->get_event_ids_for_feed($url);
        $this->assertCount(1, $ids, 'the rest of the feed is imported');
        $this->assertSame('short@example.org', (new Event($osec_app, (int) reset($ids)))->get('ical_uid'));
        $this->assertCount(1, $output['messages']);
        $this->assertStringContainsString('768', $output['messages'][0]);
    }

    public function test_feed_uid_longer_than_255_characters_is_imported()
    {
        global $osec_app;

        $uid = str_repeat('u', 400) . '@example.org';
        $url = 'https://example.org/uid-400.ics';
        $this->import($url, $this->vevent($uid, ''));
        $this->import($url, $this->vevent($uid, ''));

        $ids = EventSearch::factory($osec_app)->get_event_ids_for_feed($url);
        $this->assertCount(1, $ids, 're-import matches the stored UID');
        $this->assertSame($uid, (new Event($osec_app, (int) reset($ids)))->get('ical_uid'));
    }

    public function test_feed_value_that_does_not_fit_is_reported_in_the_result()
    {
        $output = $this->import(
            'https://example.org/long-ticket.ics',
            $this->vevent('ticket@example.org', 'X-TICKETS-URL:https://example.org/?q=' . str_repeat('a', 800) . "\r\n")
        );

        $this->assertSame([['ticket_url', 768, false]], $this->reported);
        $this->assertCount(1, $output['messages']);
    }

    private function save_event(array $fields): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $event   = new Event($osec_app, $fields + [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT('2026-11-01 10:00:00', 'Europe/Berlin'),
            'end'              => new DT('2026-11-01 11:00:00', 'Europe/Berlin'),
            'allday'           => 0,
            'timezone_name'    => 'Europe/Berlin',
            'recurrence_rules' => '',
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => '',
        ]);
        $event->save(false);

        return $post_id;
    }

    private function vevent(string $uid, string $extra, string $start = '20261015T100000Z'): string
    {
        $end = substr($start, 0, 9) . '11' . substr($start, 11);

        return "BEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTAMP:20260901T100000Z\r\nDTSTART:{$start}\r\n" .
            "DTEND:{$end}\r\nSUMMARY:Field length\r\n{$extra}END:VEVENT\r\n";
    }

    private function import(string $url, string $vevents): array
    {
        global $osec_app;

        return IcsImportExportParser::factory($osec_app)->import([
            'events_in_db'   => [],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\n{$vevents}END:VCALENDAR\r\n",
            'feed'           => (object) [
                'feed_id'              => '1',
                'feed_url'             => $url,
                'feed_name'            => 'field length',
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
    }
}
