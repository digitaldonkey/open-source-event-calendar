<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\IcsImportExportParser;
use Osec\Tests\Utilities\TestBase;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @group ics
 * Sample test case.
 */
class IcsImportExportParserTest extends TestBase
{
    public function test_process_ical_source()
    {
        global $osec_app;
        $DATA = [
            'events_in_db'   =>
                [
                    59  => 0,
                    60  => 1,
                    61  => 2,
                    62  => 3,
                    63  => 4,
                    64  => 5,
                    65  => 6,
                    66  => 7,
                    67  => 8,
                    68  => 9,
                    69  => 10,
                    70  => 11,
                    71  => 12,
                    72  => 13,
                    73  => 14,
                    74  => 15,
                    75  => 16,
                    76  => 17,
                    77  => 18,
                    78  => 19,
                    79  => 20,
                    80  => 21,
                    81  => 22,
                    82  => 23,
                    83  => 24,
                    84  => 25,
                    85  => 26,
                    86  => 27,
                    87  => 28,
                    88  => 29,
                    89  => 30,
                    90  => 31,
                    91  => 32,
                    92  => 33,
                    93  => 34,
                    94  => 35,
                    95  => 36,
                    96  => 37,
                    97  => 38,
                    98  => 39,
                    99  => 40,
                    100 => 41,
                    101 => 42,
                    102 => 43,
                    103 => 44,
                ],
            'feed'           =>
                (object)[
                    'feed_id'              => '1',
                    'feed_url'             => 'https://ics.calendarlabs.com/641/64bc8358/FIFA_Womens_World_Cup.ics',
                    'feed_name'            => 'FIFA Womens World Cup',
                    'feed_category'        => '3',
                    'feed_tags'            => '',
                    'comments_enabled'     => '0',
                    'map_display_enabled'  => '0',
                    'keep_tags_categories' => '0',
                    'keep_old_events'      => '0',
                    'import_timezone'      => '0',
                ],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Calendar Labs//Calendar 1.0//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:FIFA Womens World Cup
X-WR-TIMEZONE:UTC
BEGIN:VEVENT
SUMMARY:New Zealand (Women) - Norway (Women)
DTSTART:20230720T070000Z
DTEND:20230720T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facefc61688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Australia (Women) - Ireland (Women)
DTSTART:20230720T100000Z
DTEND:20230720T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf0091688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Nigeria (Women) - Canada (Women)
DTSTART:20230721T023000Z
DTEND:20230721T041500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf0381688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Philippines (Women) - Switzerland (Women)
DTSTART:20230721T050000Z
DTEND:20230721T064500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf0651688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Spain (Women) - Costa Rica (Women)
DTSTART:20230721T073000Z
DTEND:20230721T091500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf0901688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:United States (Women) - Vietnam (Women)
DTSTART:20230722T010000Z
DTEND:20230722T024500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf0c71688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Zambia (Women) - Japan (Women)
DTSTART:20230722T070000Z
DTEND:20230722T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf0f51688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:England (Women) - Haiti (Women)
DTSTART:20230722T093000Z
DTEND:20230722T111500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf1221688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Denmark (Women) - China (Women)
DTSTART:20230722T120000Z
DTEND:20230722T134500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf1511688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Sweden (Women) - South Africa (Women)
DTSTART:20230723T050000Z
DTEND:20230723T064500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf17e1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Netherlands (Women) - Portugal (Women)
DTSTART:20230723T073000Z
DTEND:20230723T091500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf1bb1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:France (Women) - Jamaica (Women)
DTSTART:20230723T100000Z
DTEND:20230723T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf1ec1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Italy (Women) - Argentina (Women)
DTSTART:20230724T060000Z
DTEND:20230724T074500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf21c1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Germany (Women) - Morocco (Women)
DTSTART:20230724T083000Z
DTEND:20230724T101500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf24d1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Brazil (Women) - Panama (Women)
DTSTART:20230724T110000Z
DTEND:20230724T124500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf2881688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Colombia (Women) - South Korea (Women)
DTSTART:20230725T020000Z
DTEND:20230725T034500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf2ba1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:New Zealand (Women) - Philippines (Women)
DTSTART:20230725T053000Z
DTEND:20230725T071500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf2ec1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Switzerland (Women) - Norway (Women)
DTSTART:20230725T080000Z
DTEND:20230725T094500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf3201688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Japan (Women) - Costa Rica (Women)
DTSTART:20230726T050000Z
DTEND:20230726T064500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf3531688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Spain (Women) - Zambia (Women)
DTSTART:20230726T073000Z
DTEND:20230726T091500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf3871688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Canada (Women) - Ireland (Women)
DTSTART:20230726T120000Z
DTEND:20230726T134500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf3ce1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:United States (Women) - Netherlands (Women)
DTSTART:20230727T010000Z
DTEND:20230727T024500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf4121688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Portugal (Women) - Vietnam (Women)
DTSTART:20230727T073000Z
DTEND:20230727T091500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf4481688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Australia (Women) - Nigeria (Women)
DTSTART:20230727T100000Z
DTEND:20230727T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf47e1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Argentina (Women) - South Africa (Women)
DTSTART:20230728T000000Z
DTEND:20230728T014500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf4b61688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:England (Women) - Denmark (Women)
DTSTART:20230728T083000Z
DTEND:20230728T101500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf4ed1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:China (Women) - Haiti (Women)
DTSTART:20230728T110000Z
DTEND:20230728T124500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf5261688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Sweden (Women) - Italy (Women)
DTSTART:20230729T073000Z
DTEND:20230729T091500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf5611688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:France (Women) - Brazil (Women)
DTSTART:20230729T100000Z
DTEND:20230729T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf59a1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Panama (Women) - Jamaica (Women)
DTSTART:20230729T123000Z
DTEND:20230729T141500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf5d51688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:South Korea (Women) - Morocco (Women)
DTSTART:20230730T043000Z
DTEND:20230730T061500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf6251688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Norway (Women) - Philippines (Women)
DTSTART:20230730T070000Z
DTEND:20230730T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf6611688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Switzerland (Women) - New Zealand (Women)
DTSTART:20230730T070000Z
DTEND:20230730T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf69d1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Germany (Women) - Colombia (Women)
DTSTART:20230730T093000Z
DTEND:20230730T111500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf6da1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Japan (Women) - Spain (Women)
DTSTART:20230731T070000Z
DTEND:20230731T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf7171688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Costa Rica (Women) - Zambia (Women)
DTSTART:20230731T070000Z
DTEND:20230731T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf7551688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Ireland (Women) - Nigeria (Women)
DTSTART:20230731T100000Z
DTEND:20230731T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf7931688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Canada (Women) - Australia (Women)
DTSTART:20230731T100000Z
DTEND:20230731T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf7d21688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Portugal (Women) - United States (Women)
DTSTART:20230801T070000Z
DTEND:20230801T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf8121688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Vietnam (Women) - Netherlands (Women)
DTSTART:20230801T070000Z
DTEND:20230801T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf8671688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Haiti (Women) - Denmark (Women)
DTSTART:20230801T110000Z
DTEND:20230801T124500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf8a91688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:China (Women) - England (Women)
DTSTART:20230801T110000Z
DTEND:20230801T124500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf8ec1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:South Africa (Women) - Italy (Women)
DTSTART:20230802T070000Z
DTEND:20230802T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf92e1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Argentina (Women) - Sweden (Women)
DTSTART:20230802T070000Z
DTEND:20230802T084500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf9871688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Panama (Women) - France (Women)
DTSTART:20230802T100000Z
DTEND:20230802T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facf9cb1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Jamaica (Women) - Brazil (Women)
DTSTART:20230802T100000Z
DTEND:20230802T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfa0e1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Morocco (Women) - Colombia (Women)
DTSTART:20230803T100000Z
DTEND:20230803T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfa521688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:South Korea (Women) - Germany (Women)
DTSTART:20230803T100000Z
DTEND:20230803T114500Z
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfa981688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Round of 16 Women\'s WC
DTSTART;VALUE=DATE:20230805
DTEND;VALUE=DATE:20230806
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfb111688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Quarter finals Women\'s WC
DTSTART;VALUE=DATE:20230811
DTEND;VALUE=DATE:20230812
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfb7c1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Semi-final Women\'s WC
DTSTART;VALUE=DATE:20230815
DTEND;VALUE=DATE:20230816
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfbde1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:3rd place Women\'s WC
DTSTART;VALUE=DATE:20230819
DTEND;VALUE=DATE:20230820
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfc3f1688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
BEGIN:VEVENT
SUMMARY:Final Women\'s WC
DTSTART;VALUE=DATE:20230820
DTEND;VALUE=DATE:20230821
LOCATION:
DESCRIPTION:Discover and subscribe to popular calendars at https://calendarlabs.com/ical-calendar\\n\\n Like us on Facebook: http://fb.com/calendarlabs to get updates
UID:64a406facfca11688471290@calendarlabs.com
DTSTAMP:20230704T064810Z
STATUS:CONFIRMED
TRANSP:TRANSPARENT
SEQUENCE:0
END:VEVENT
END:VCALENDAR',
        ];
        // if (!defined('WP_SITEURL')) {
        // define('WP_SITEURL', 'https://ddev-wordpress.ddev.site');
        // }
        $value = IcsImportExportParser::factory($osec_app)->import($DATA);
        $this->assertEquals(53, $value['count']);
    }
}
