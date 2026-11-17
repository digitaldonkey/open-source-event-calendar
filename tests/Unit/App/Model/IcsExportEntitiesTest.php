<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Http\Response\RenderIcal;
use Osec\Tests\Utilities\TestBase;

/**
 * The ICS feed is text/calendar, not HTML: "&" stays "&". RenderIcal ran the whole feed through wp_kses(), so every
 * subscribed calendar showed "Tom &amp; Jerry", and category names (stored HTML-encoded) even "Arts &amp;amp\;".
 *
 * @group ics
 */
class IcsExportEntitiesTest extends TestBase
{
    public function test_feed_text_has_no_entities()
    {
        global $osec_app;

        $post_id  = self::factory()->post->create([
            'post_type'   => OSEC_POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => 'Tom & Jerry',
        ]);
        $category = wp_insert_term('Arts & Culture', 'osec_events_categories');
        $tag      = wp_insert_term('Jazz & Blues', 'osec_events_tags');
        wp_set_object_terms($post_id, [$category['term_id']], 'osec_events_categories');
        wp_set_object_terms($post_id, [$tag['term_id']], 'osec_events_tags');
        $event = new Event($osec_app, [
            'post_id'       => $post_id,
            'post'          => get_post($post_id),
            'start'         => new DT('2027-03-01 09:00:00', 'Europe/Berlin'),
            'end'           => new DT('2027-03-01 10:00:00', 'Europe/Berlin'),
            'allday'        => 0,
            'timezone_name' => 'Europe/Berlin',
            'venue'         => 'Café & Bar',
            'contact_name'  => 'Mia & Mo',
            'ticket_url'    => 'https://example.org/tickets?a=1&b=2',
        ]);
        $event->save(false);

        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events'                    => [new Event($osec_app, $post_id)],
            'do_not_export_as_calendar' => false,
        ]);
        $renderer = new class ($osec_app) extends RenderIcal {
            public function output(string $ics): string
            {
                ob_start();
                $this->render_calendar($ics);

                return (string)ob_get_clean();
            }
        };
        $feed = $renderer->output($ics);

        $this->assertStringContainsString('SUMMARY:Tom & Jerry', $feed);
        $this->assertMatchesRegularExpression('/^CATEGORIES[^:]*:Arts & Culture\r?$/m', $feed);
        $this->assertMatchesRegularExpression('/^X-TAGS[^:]*:Jazz & Blues\r?$/m', $feed);
        $this->assertStringContainsString('LOCATION:Café & Bar', $feed);
        $this->assertStringContainsString('X-TICKETS-URL:https://example.org/tickets?a=1&b=2', $feed);
        $this->assertDoesNotMatchRegularExpression('/&(amp|quot|lt|gt|#\d+);/', $feed);
    }

    /**
     * The single-event export names the calendar after the site. WordPress stores title and tagline HTML-encoded
     * (esc_html() on save): "Bob&#039;s Calendar" showed as the calendar's name.
     */
    public function test_calendar_name_and_description_are_plain_text()
    {
        global $osec_app;

        update_option('blogname', 'Bob\'s Calendar & <b>more</b>');
        // No double quotes: iCalcreator writes every " in iCalendar text as ' (library behaviour).
        update_option('blogdescription', 'Jazz & <i>live</i>');
        $this->assertSame('Bob&#039;s Calendar &amp; &lt;b&gt;more&lt;/b&gt;', get_option('blogname'), 'stored encoded');

        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events'                    => [],
            'do_not_export_as_calendar' => true,
        ]);

        $this->assertStringContainsString('X-WR-CALNAME:Bob\'s Calendar & <b>more</b>', $ics);
        $this->assertStringContainsString('X-WR-CALDESC:Jazz & <i>live</i>', $ics);
    }
}
