<?php

namespace Unit\App\Model;

use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @group ics
 * Sample test case.
 */
class IcsImportExportParserFullyFledgedEventTest extends TestBase
{
    /**
     * Simple feed test
     */
    public function test_fully_fledged_event_ics()
    {
        global $osec_app;
        $DATA = [
            'events_in_db' => [],
            'feed' =>
                (object)[
                    'feed_id'              => '4',
                    'feed_url'             => 'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/fully-fledged-event.ics',
                    'feed_name'            => 'fully-fledged-event.ics',
                    'feed_category'        => '',
                    'feed_tags'            => '',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '1',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '1',
                ],
            'comment_status' => 'closed',
            'do_show_map'    => true,
            'source'         => file_get_contents(__DIR__ . '/ical_feeds/fully-fledged-event.ics'),
        ];
        $value = IcsImportExportParser::factory($osec_app)->import($DATA);

        $this->assertEquals(
            1,
            $value['count'],
            'Number of imported events'
        );

        // Verify number of instances
        $event_id = (int) EventSearch::factory($osec_app)->get_event_ids_for_feed($DATA['feed']->feed_url)[0];
        $event = new Event($osec_app, $event_id);

        $this->assertEquals(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;WKST=MO',
            $event->get('recurrence_rules'),
            'recurrence_rules'
        );
        $this->assertEquals(
            'FREQ=WEEKLY;INTERVAL=4;BYDAY=WE;WKST=MO',
            $event->get('exception_rules'),
            'exception_rules'
        );
        $this->assertEquals(
            'Europe/Berlin',
            $event->get('timezone_name'),
            'timezone_name'
        );
        $this->assertEquals(
            52.520828,
            $event->get('latitude'),
            'latitude'
        );
        $this->assertEquals(
            13.409421,
            $event->get('longitude'),
            'longitude'
        );
        $this->assertEquals(
            'Panoramastraße 1a, Mitte, 10178 Berlin, Deutschland',
            $event->get('address'),
            'address'
        );
        $this->assertEquals(
            false,
            $event->get('allday'),
            'allday'
        );
        $this->assertEquals(
            'Venue name',
            $event->get('venue'),
            'venue'
        );
        $this->assertEquals(
            true,
            $event->get('show_map'),
            'show_map'
        );
        $this->assertEquals(
            '10€',
            $event->get('cost'),
            'cost'
        );
        $this->assertEquals(
            false,
            $event->get('is_free'),
            'is_free'
        );
        $this->assertEquals(
            'fully-fledged_event@ddev-wordpress.ddev.site',
            $event->get('ical_uid'),
            'ical_uid'
        );
        $this->assertEquals(
            'Marry Poppins',
            $event->get('contact_name'),
            'contact_name'
        );
        $this->assertEquals(
            '+1586909808098',
            $event->get('contact_phone'),
            'contact_phone'
        );
        $this->assertEquals(
            'fern@sehturm.de',
            $event->get('contact_email'),
            'contact_email'
        );
        $this->assertEquals(
            'http://donkeymedia.eu',
            $event->get('contact_url'),
            'contact_url'
        );
        $this->assertEquals(
            'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/fully-fledged-event.ics',
            $event->get('ical_feed_url'),
            'ical_feed_url'
        );
        $start = $event->get('start');
        $this->assertEquals(
            '20260610T224900',
            $start->format('Ymd\THis', 'Europe/Berlin'),
            'start'
        );

        $end = $event->get('end');
        $this->assertEquals(
            '20260610T234900',
            $end->format('Ymd\THis', 'Europe/Berlin'),
            'end'
        );

        // TODO DATE VERIFICATIONS
        //   Regarding timezones, the "always_use_calendar_timezone" setting.
        //   and the Feed->import_timezone setting.
    }
}
