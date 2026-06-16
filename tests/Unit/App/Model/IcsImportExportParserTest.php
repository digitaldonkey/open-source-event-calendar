<?php

namespace Osec\Tests\Unit\App\Model;

use Kigkonsult\Icalcreator\IcalInterface;
use Kigkonsult\Icalcreator\Vcalendar;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Exception\ImportExportParseException;
use Osec\Tests\Utilities\TestBase;
use PHPUnit\Util\InvalidDataSetException;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @group ics
 * Sample test case.
 */
class IcsImportExportParserTest extends TestBase
{
    /**
     * Simple feed test
     */
    public function test_simple_occurrences_ics()
    {
        global $osec_app;
        $DATA = [
            'events_in_db' => [],
            'feed' =>
                (object)[
                    'feed_id'              => '4',
                    'feed_url'             => 'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/simple_reoccurrences.ics',
                    'feed_name'            => 'Most Simple reoccurrences',
                    'feed_category'        => '',
                    'feed_tags'            => '',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '0',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '1',
                ],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => file_get_contents(__DIR__ . '/ical_feeds/simple_reoccurrences.ics'),
        ];
        $value = IcsImportExportParser::factory($osec_app)->import($DATA);
        $this->assertEquals(1, $value['count']);

        // Verify number of instances
        $event_id = (int) EventSearch::factory($osec_app)->get_event_ids_for_feed($DATA['feed']->feed_url)[0];
        $time_zone = Timezones::factory($osec_app)->get_default_timezone();
        $inserted = EventSearch::factory($osec_app)->get_events_between(
            new DT(strtotime('10 July 2024 00:00:00 ' . $time_zone), $time_zone),
            new DT(strtotime('25 September 2024 00:00:00 ' . $time_zone), $time_zone),
            ['post_ids' => [$event_id]]
        );
        $this->assertEquals(6, count($inserted));

        // TODO DATE VERIFICATIONS
        //   Regarding timezones, the "always_use_calendar_timezone" setting.
        //   and the Feed->import_timezone setting.
    }

    /**
     * Reoccurrence and overrides.
     */
    public function test_simple_occurrences_with_oveeride_ics()
    {
        global $osec_app;
        $DATA  = [
            'events_in_db'   => [],
            'feed'           =>
                (object)[
                    'feed_id'              => '3',
                    'feed_url'             => 'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/simple_occurrences_with_overide.ics',
                    'feed_name'            => 'simple_occurrences_with_overide.ics',
                    'feed_category'        => '',
                    'feed_tags'            => '',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '0',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '1',
                    'import_post_status'   => 'publish',
                ],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => file_get_contents(__DIR__ . '/ical_feeds/simple_occurrences_with_overide.ics'),
        ];
        $value = IcsImportExportParser::factory($osec_app)->import($DATA);
        $this->assertEquals(2, $value['count']);

        // Verify number of instances
        $event_id  = (int)EventSearch::factory($osec_app)->get_event_ids_for_feed($DATA['feed']->feed_url)[0];
        $time_zone = Timezones::factory($osec_app)->get_default_timezone();
        $inserted  = EventSearch::factory($osec_app)->get_events_between(
            new DT(strtotime('06 January 2025 00:00:00 ' . $time_zone), $time_zone),
            new DT(strtotime('03 February 2025 23:59:59 ' . $time_zone), $time_zone),
            ['post_ids' => [$event_id]]
        );
        $this->assertEquals(4, count($inserted));

        // TODO DATE VERIFICATIONS
        //   Regarding timezones, the "always_use_calendar_timezone" setting.
        //   and the Feed->import_timezone setting.
    }


    public function test_process_ical_with_many_props()
    {
        global $osec_app;
        $DATA = [
            'events_in_db' => [],
            'feed' =>
                (object)[
                    'feed_id'              => '2',
                    'feed_url'             => 'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/tests/Unit/App/Model/ical_feeds/feed_2.ics',
                    'feed_name'            => 'A lot if AI generated test data',
                    'feed_category'        => '',
                    'feed_tags'            => '',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '0',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '1',
                    'import_post_status'   => 'publish',
                ],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => file_get_contents(__DIR__ . '/ical_feeds/feed_2.ics'),
        ];
        $value = IcsImportExportParser::factory($osec_app)->import($DATA);
        $this->assertEquals(23, $value['count']);
    }


    /**
     * Detailed test.
     *
     * This is an test using a JSON data for exact validation.
     * The content of test_date_processing_results.json results
     * was generated from source after manual verification.
     *   @see src/App/Model/IcsImportExportParser.php:780
     *        and uncomment echo json_encode($debug, JSON_PRETTY_PRINT) below.
     *
     * @dataProvider date_processing_JsonResultsProvider
     * @source data is: ical_feeds/feed_2.ics
     *
     * @param string $dataset_id Helps with result data.
     * @param string $ical_feed @see ./ical_feeds/feed_2.ics
     * @param string $local_timezone Default time zone
     * @param bool $override_UTC_TZ Ui setting for ICS import
     * @param ?string $x_wr_timezone Calendar timezone or null
     * @param ?string $override_timezone Usually we use $x_wr_timezone ?? $local_timezone
     *                                   but there is a filter hook available, so could be anything.
     */
    public function test_date_processing(
        string $dataset_id,
        string $ical_feed,
        string $local_timezone,
        bool $override_UTC_TZ,
        ?string $x_wr_timezone,
        ?string $override_timezone,
        array $expected
    ) {
        global    $osec_app;
        $unique = md5($ical_feed);
        $cal    = Vcalendar::factory([IcalInterface::UNIQUE_ID => $unique]);
        $DO_ASSERT = true;

        if ($cal->parse($ical_feed)) {
            $events = array_filter(
                $cal->getComponents(),
                fn($c) => $c instanceof \Kigkonsult\Icalcreator\Vevent
            );

            $this->assertEquals(
                23,
                count($events),
                'Event count matches expected'
            );

            $debug = [];
            foreach (array_values($events) as $e) {
                $eventDateData = IcsImportExportParser::factory($osec_app)->process_event_date_fields(
                    $e, // is_a \Kigkonsult\Icalcreator\Vevent
                    $local_timezone, // String
                    $override_UTC_TZ, // Bool
                    $x_wr_timezone, // Calendar timezone
                    $override_timezone, // String
                );


                // Verify result availability.
                $uid = $eventDateData['uid'];
                if (! isset($expected[$uid])) {
                    throw new InvalidDataSetException(esc_html('Event ' . $uid . ' does not exist in dataset'));
                }
                $expected_result = $expected[$uid];
                $test_id = $uid . ' @ dataset: ' . $dataset_id . ' ';

                // We need to verify these. Rest in $eventDateData is for debug.
                /* @var DT $start Please ignore this text */
                $start = $eventDateData['start'];
                /* @var DT $end Please ignore this text */
                $end = $eventDateData['end'];
                $allday = $eventDateData['allday'];
                $instant_event = $eventDateData['instant_event'];

                // Start
                if ($DO_ASSERT) {
                    $this->assertEquals(
                        $expected_result['startval_localized'],
                        $start->format('Y-m-d H:i:s', $local_timezone),
                        $test_id . 'START value localized with $local_timezone'
                    );
                    $this->assertEquals(
                        $expected_result['startval_UTC'],
                        $start->format_to_gmt(),
                        $test_id . 'START value timestamp'
                    );
                    $this->assertEquals(
                        $expected_result['startval_TZ'],
                        $start->get_timezone(),
                        $test_id . 'START value timezone'
                    );
                    $this->assertEquals(
                        $expected_result['start_is_local_timezone'],
                        $start->get_timezone() === $local_timezone,
                        $test_id . 'START is local timezone'
                    );

                    // Timezone Match
                    $this->assertEquals(
                        $start->get_timezone(),
                        $end->get_timezone(),
                        $test_id . 'Timezones must match'
                    );

                    // Props
                    $this->assertEquals(
                        $expected_result['allday'],
                        $allday,
                        $test_id . 'Is all day'
                    );
                    $this->assertEquals(
                        $expected_result['instant_event'],
                        $instant_event,
                        $test_id . 'Is instant event'
                    );

                    // End
                    $this->assertEquals(
                        $expected_result['endval_localized'],
                        $end->format('Y-m-d H:i:s', $local_timezone),
                        $test_id . 'END value localized with $local_timezone'
                    );
                    $this->assertEquals(
                        $expected_result['endval_UTC'],
                        $end->format_to_gmt(),
                        $test_id . 'END value timestamp'
                    );
                }
                // Prepare printable debug data.
                unset($eventDateData['end']);
                unset($eventDateData['end']);
                $debug[$dataset_id][$uid] = $eventDateData;
            }
        } else {
            throw new ImportExportParseException('The passed string is not a valid ics feed');
        }
        // With a little commenting in and out you can copy
        // The following print to test_date_processing_results.json
        // after verifying your dataset.
        // echo json_encode($debug, JSON_PRETTY_PRINT);
    }

    /**
     * Test data for test_date_processing.
     *
     * @return \Generator
     */
    public static function date_processing_JsonResultsProvider(): \Generator
    {
        // Crafted ical source file wich should cover all date variants.
        $ical_feed = file_get_contents(__DIR__ . '/ical_feeds/feed_2.ics');
        // Manually verified results. Feel free to add your cases.
        $results_json = file_get_contents(__DIR__ . '/ical_feeds/test_date_processing_results.json');
        $expected = json_decode($results_json, true);

        $datasets = [];
        $datasets[] = [
            'dataset_id' => 'manually_verified',
            'ical_feed' => $ical_feed,
            'local_timezone' => 'Europe/Berlin',
            'override_UTC_TZ' => false,
            'x_wr_timezone' => 'Europe/Berlin',
            'override_timezone' => 'Europe/Berlin',
            'expected' => $expected['manually_verified'],
        ];
        // Should override non Floating (local time) and all UTC
        //  Which are all events without a $event_timezone param.
        $datasets[] = [
            'dataset_id' => 'override_UTC_TZ',
            'ical_feed' => $ical_feed,
            'local_timezone' => 'Europe/Berlin',
            'override_UTC_TZ' => true,
            'x_wr_timezone' => 'Europe/Berlin',
            'override_timezone' => 'Indian/Kerguelen',
            'expected' => $expected['override_UTC_TZ'],
        ];
        // Floating time and Allday Events use local TZ or x_wr_timezone if available.
        // So overriding it with Indian/Kerguelen should clter these Timezones.
        $datasets[] = [
            'dataset_id' => 'x_wr_timezone_no_override',
            'ical_feed' => $ical_feed,
            'local_timezone' => 'Europe/Berlin',
            'override_UTC_TZ' => false,
            'x_wr_timezone' => 'Indian/Kerguelen',
            'override_timezone' => null,
            'expected' => $expected['x_wr_timezone_no_override'],
        ];
        // Now Berlin Dates (x_wr_timezone) are "not local".
        $datasets[] = [
            'dataset_id' => 'local_timezone_shanghai',
            'ical_feed' => $ical_feed,
            'local_timezone' => 'Asia/Shanghai',
            'override_UTC_TZ' => false,
            'x_wr_timezone' => 'Europe/Berlin',
            'override_timezone' => null,
            'expected' => $expected['local_timezone_shanghai'],
        ];
        yield from $datasets;
    }

    /**
     * The first test.
     */
    public function test_process_ical_source()
    {
        global $osec_app;
        $DATA = [
            'events_in_db'   => [],
            'feed'           =>
                (object)[
                    'feed_id'              => '1',
                    'feed_url'             => 'https://ics.calendarlabs.com/641/64bc8358/FIFA_Womens_World_Cup.ics',
                    'feed_name'            => 'FIFA Womens World Cup',
                    'feed_category'        => '',
                    'feed_tags'            => '',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '0',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '0',
                    'import_post_status'   => 'publish',
                ],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => file_get_contents(__DIR__ . '/ical_feeds/feed_1.ics'),
        ];
        $value = IcsImportExportParser::factory($osec_app)->import($DATA);
        $this->assertEquals(53, $value['count']);
    }
}
