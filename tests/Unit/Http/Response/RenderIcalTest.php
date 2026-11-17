<?php

namespace Osec\Tests\Unit\Http\Response;

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Http\Response\RenderIcal;
use Osec\Tests\Utilities\TestBase;

/**
 * HTML is sanitized before serialization; rendering preserves iCalendar text and folds.
 *
 * @group ics
 */
class RenderIcalTest extends TestBase
{
    /**
     * Plain-text properties must not acquire HTML entities in the response.
     */
    public function test_response_preserves_serialized_text()
    {
        $ics = $this->export('<p>Fish &amp; Chips.</p>');
        $rendered = $this->render($ics);

        self::assertStringContainsString('SUMMARY:Fish & Chips night', $rendered);
        self::assertStringContainsString('LOCATION:University & Pleasant Street', $rendered);
        self::assertSame($ics, $rendered);
    }

    /**
     * HTML attributes can cross folds without changing CRLF or the 75-octet limit.
     */
    public function test_response_preserves_folds_inside_html_attributes()
    {
        $ics = $this->export('<a href="https://example.org/' . str_repeat('tickets', 20)
            . '" title="' . str_repeat('Fish and chips ', 20) . '">Get tickets</a>');
        $rendered = $this->render($ics);

        self::assertStringContainsString("\r\n ", $rendered);
        self::assertSame(0, preg_match('/(?<!\r)\n|\r(?!\n)/', $rendered));
        foreach (explode("\r\n", $rendered) as $line) {
            self::assertLessThanOrEqual(75, strlen($line), $line);
        }
        self::assertSame($ics, $rendered);
    }

    /**
     * Untrusted HTML is filtered before it reaches the serialized X-ALT-DESC value.
     */
    public function test_html_description_is_sanitized_before_serialization()
    {
        $ics = $this->export('<p onclick="alert(1)"><strong>Welcome</strong> '
            . '<a href="javascript:alert(1)" title="Tickets">unsafe</a> '
            . '<a href="https://example.org/tickets" title="Tickets">safe</a>'
            . '<script>alert(2)</script></p>');
        $calendar = new Vcalendar();
        $calendar->parse($ics);
        $events = array_values(array_filter(
            $calendar->getComponents(),
            static fn ($component) => $component instanceof Vevent
        ));
        self::assertCount(1, $events);
        $html = $events[0]->getXprop('X-ALT-DESC')[1];

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<strong>Welcome</strong>', $html);
        self::assertStringContainsString('https://example.org/tickets', $html);
        self::assertStringNotContainsString('onclick', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('DOCTYPE_PLACEHOLDER', $html);
    }

    /**
     * Export a synthetic event, including content supplied by an extension filter.
     *
     * @param string $content Filtered HTML content.
     *
     * @return string Serialized calendar.
     */
    private function export(string $content): string
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'   => OSEC_POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => 'Fish & Chips night',
        ]);
        $event = new Event($osec_app, [
            'post_id'       => $post_id,
            'post'          => get_post($post_id),
            'start'         => new DT('2026-11-20 18:00:00', 'UTC'),
            'end'           => new DT('2026-11-20 20:00:00', 'UTC'),
            'allday'        => 0,
            'timezone_name' => 'UTC',
            'venue'         => 'University & Pleasant Street',
        ]);
        $filter = static fn () => $content;
        add_filter('osec_the_content', $filter);
        try {
            return IcsImportExportParser::factory($osec_app)->export([
                'events'                    => [$event],
                'do_not_export_as_calendar' => false,
            ], ['no_html' => false]);
        } finally {
            remove_filter('osec_the_content', $filter);
        }
    }

    /**
     * Capture the native output method without headers or process termination.
     *
     * @param string $ics Serialized calendar.
     *
     * @return string Rendered calendar.
     */
    private function render(string $ics): string
    {
        global $osec_app;

        $method = self::getPrivateMethod(RenderIcal::class, 'render_calendar');
        ob_start();
        try {
            $method->invoke(new RenderIcal($osec_app), $ics);
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
