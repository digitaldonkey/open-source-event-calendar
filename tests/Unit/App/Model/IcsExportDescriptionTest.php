<?php

namespace Osec\Tests\Unit\App\Model;

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * Encoded text in event content must not turn into tags before DESCRIPTION is stripped.
 *
 * @group ics
 */
class IcsExportDescriptionTest extends TestBase
{
    /**
     * Angle brackets written as text survive the plain-text export.
     */
    public function test_description_preserves_encoded_angle_brackets()
    {
        $event = $this->export('<p>Ages &lt;18 welcome, 5 &gt; 3 today.</p>');

        self::assertSame('Ages <18 welcome, 5 > 3 today.', $event->getDescription());
    }

    /**
     * Actual formatting is removed while entities become plain text.
     */
    public function test_description_strips_markup_before_decoding_entities()
    {
        $event = $this->export('<p><strong>Fish &amp; Chips</strong> for &lt;guests&gt;.</p>');

        self::assertSame('Fish & Chips for <guests>.', $event->getDescription());
    }

    /**
     * An included excerpt follows the same plain-text conversion.
     */
    public function test_description_decodes_the_included_excerpt()
    {
        $event = $this->export('<p>Bring a guest.</p>', 'Ages &lt;18 &amp; families.');

        self::assertStringContainsString('Ages <18 & families.', $event->getDescription());
        self::assertStringContainsString('Bring a guest.', $event->getDescription());
    }

    /**
     * HTML keeps its text encoded so the response sanitizer cannot mistake it for a tag.
     */
    public function test_html_description_keeps_encoded_text_and_markup()
    {
        $event = $this->export('<p><strong>Ages &lt;18</strong> welcome, 5 &gt; 3 today.</p>');
        $html = $event->getXprop('X-ALT-DESC');

        self::assertIsArray($html);
        self::assertStringContainsString('<strong>Ages &lt;18</strong>', $html[1]);
        self::assertStringContainsString('5 &gt; 3 today.', $html[1]);
    }

    /**
     * Export filtered event content and read the resulting VEVENT through iCalcreator.
     *
     * @param string $content Event content.
     * @param string $excerpt Optional event excerpt.
     *
     * @return Vevent Exported event.
     */
    private function export(string $content, string $excerpt = ''): Vevent
    {
        global $osec_app;

        $previous = $osec_app->settings->get('feature_use_excerpt');
        $osec_app->settings->set('feature_use_excerpt', '' !== $excerpt);
        try {
            $post_id = self::factory()->post->create([
                'post_type'    => OSEC_POST_TYPE,
                'post_status'  => 'publish',
                'post_title'   => 'Description export',
                'post_content' => $content,
                'post_excerpt' => $excerpt,
            ]);
            $event = new Event($osec_app, [
                'post_id'       => $post_id,
                'post'          => get_post($post_id),
                'start'         => new DT('2026-11-20 18:00:00', 'UTC'),
                'end'           => new DT('2026-11-20 20:00:00', 'UTC'),
                'allday'        => 0,
                'timezone_name' => 'UTC',
            ]);
            self::assertSame($excerpt, $event->get('post')->post_excerpt);
            $ics = IcsImportExportParser::factory($osec_app)->export([
                'events'                    => [$event],
                'do_not_export_as_calendar' => false,
            ], ['no_html' => false]);
        } finally {
            $osec_app->settings->set('feature_use_excerpt', $previous);
        }

        $calendar = new Vcalendar();
        $calendar->parse($ics);
        $events = array_values(array_filter(
            $calendar->getComponents(),
            static fn ($component) => $component instanceof Vevent
        ));
        self::assertCount(1, $events);

        return clone $events[0];
    }
}
