<?php

namespace Osec\Tests\Unit\App\Model;

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * The HTML description (X-ALT-DESC), exported unless the feed is requested with no_html=true, links to the event.
 *
 * Since the event URL moved into IcsImportExportParser::export_event_url(), the link read an undefined variable:
 * a PHP warning on every default feed and an empty link in every HTML description.
 *
 * @group ics
 */
class IcsExportHtmlDescriptionTest extends TestBase
{
    public function test_html_description_links_to_the_event()
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => 'Concert',
            'post_content' => 'An evening of music.',
        ]);
        (new Event($osec_app, [
            'post_id'       => $post_id,
            'post'          => get_post($post_id),
            'start'         => new DT('2027-03-01 19:00:00', 'Europe/Berlin'),
            'end'           => new DT('2027-03-01 21:00:00', 'Europe/Berlin'),
            'allday'        => 0,
            'timezone_name' => 'Europe/Berlin',
        ]))->save(false);

        $ics = IcsImportExportParser::factory($osec_app)->export(
            [
                'events'                    => [new Event($osec_app, $post_id)],
                'do_not_export_as_calendar' => false,
            ],
            ['no_html' => false]
        );
        $calendar = new Vcalendar();
        $calendar->parse($ics);
        $events = array_values(array_filter(
            $calendar->getComponents(),
            static fn ($component) => $component instanceof Vevent
        ));
        $this->assertCount(1, $events);
        $html = $events[0]->getXprop('X-ALT-DESC')[1];

        // iCalcreator writes the attribute quotes as single quotes.
        $this->assertMatchesRegularExpression(
            '/<a href=["\']' . preg_quote(esc_url(get_permalink($post_id)), '/') . '["\']>/',
            $html
        );
    }
}
