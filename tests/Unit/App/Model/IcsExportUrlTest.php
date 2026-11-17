<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Controller\ImportExportController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * The event URL in the ICS feed.
 *
 * The event permalink uses the translated rewrite slug as typed ("/événement/" in French). iCalcreator accepts only
 * ASCII URIs and threw, uncaught, so the whole feed was a PHP fatal error for every subscriber.
 *
 * @group ics
 */
class IcsExportUrlTest extends TestBase
{
    /**
     * Permalinks by post ID; other events keep their real one.
     */
    private array $permalinks = [];

    public function set_up()
    {
        global $osec_app;
        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        add_filter('post_type_link', [$this, 'permalink'], 10, 2);
    }

    public function tear_down()
    {
        remove_filter('post_type_link', [$this, 'permalink'], 10);
        parent::tear_down();
    }

    /**
     * post_type_link filter: the permalink the test asks for.
     */
    public function permalink(string $link, \WP_Post $post): string
    {
        return $this->permalinks[$post->ID] ?? $link;
    }

    public function test_non_ascii_path_is_exported_percent_encoded()
    {
        $event = $this->create_event('Fête');
        $this->permalinks[$event->get('post_id')] = 'https://example.org/événement/fête-d-été/?q=é';

        $ics = $this->export([$event]);

        $this->assertStringContainsString(
            'URL:https://example.org/%C3%A9v%C3%A9nement/f%C3%AAte-d-%C3%A9t%C3%A9/?q=%C3%A9',
            $this->unfold($ics)
        );
    }

    public function test_non_ascii_image_url_is_exported_percent_encoded()
    {
        $event = $this->create_event('Fête', '<img src="https://example.org/bilder/Café-été.jpg" alt="" />');

        $ics = $this->unfold($this->export([$event]));

        $this->assertStringContainsString('ATTACH:https://example.org/bilder/Caf%C3%A9-%C3%A9t%C3%A9.jpg', $ics);
    }

    public function test_url_iCalcreator_refuses_is_left_out_and_reported()
    {
        $reported = [];
        $listener = function ($url, $message) use (&$reported) {
            $reported[] = $url;
        };
        add_action('osec_event_url_not_exportable', $listener, 10, 3);

        $broken = $this->create_event('Lesung');
        $valid  = $this->create_event('Konzert');
        $this->permalinks[$broken->get('post_id')] = 'https://bücher.example/event/lesung/';

        try {
            $ics = $this->export([$broken, $valid]);
        } finally {
            remove_action('osec_event_url_not_exportable', $listener, 10);
        }

        $this->assertStringContainsString('SUMMARY:Lesung', $ics, 'The event is exported.');
        $this->assertStringContainsString('SUMMARY:Konzert', $ics);
        $this->assertSame(['https://bücher.example/event/lesung/'], $reported);
        $this->assertStringNotContainsString('bücher', $ics, 'The refused URL is left out.');
        $this->assertStringContainsString('URL:' . get_permalink($valid->get('post_id')), $this->unfold($ics));
    }

    /**
     * A URL Iri cannot parse (its uri is false, not null) went to iCalcreator as '' instead of being refused.
     */
    public function test_unparseable_url_is_left_out_and_reported()
    {
        $reported = [];
        $listener = function ($url) use (&$reported) {
            $reported[] = $url;
        };
        add_action('osec_event_url_not_exportable', $listener, 10, 3);

        $event = $this->create_event('Lesung');
        $this->permalinks[$event->get('post_id')] = '://';

        try {
            $ics = $this->unfold($this->export([$event]));
        } finally {
            remove_action('osec_event_url_not_exportable', $listener, 10);
        }

        $this->assertStringContainsString('SUMMARY:Lesung', $ics);
        $this->assertDoesNotMatchRegularExpression('/^URL:/m', $ics, 'No URL line, also no empty one.');
        $this->assertSame(['://'], $reported);
    }

    public function test_left_out_url_is_reported_to_admins()
    {
        global $osec_app;

        $event = $this->create_event('Lesung <b>');
        $this->permalinks[$event->get('post_id')] = 'https://bücher.example/event/<lesung>/';

        $ics = (new ImportExportController($osec_app, ['ics'], []))->export_events('ics', [
            'events'                    => [$event],
            'do_not_export_as_calendar' => false,
        ]);

        $this->assertStringContainsString('SUMMARY:Lesung', $ics);
        $messages = $osec_app->options->get(NotificationAdmin::OPTION_KEY)['_messages'] ?? [];
        $this->assertCount(1, $messages);
        $message = reset($messages)['message'];
        $this->assertStringContainsString('"Lesung &lt;b&gt;"', $message);
        $this->assertStringContainsString('https://bücher.example/event/&lt;lesung&gt;/', $message);
        $this->assertFalse(has_action('osec_event_url_not_exportable'), 'The listener is removed.');
    }

    /**
     * @param  Event[]  $events
     */
    private function export(array $events): string
    {
        global $osec_app;

        return IcsImportExportParser::factory($osec_app)->export([
            'events'                    => $events,
            'do_not_export_as_calendar' => false,
        ]);
    }

    /**
     * Undoes iCalendar line folding (RFC 5545 3.1).
     */
    private function unfold(string $ics): string
    {
        return preg_replace('/\r?\n[ \t]/', '', $ics);
    }

    private function create_event(string $title, string $content = ''): Event
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'   => OSEC_POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_content' => $content,
        ]);
        $event   = new Event($osec_app, [
            'post_id'       => $post_id,
            'post'          => get_post($post_id),
            'start'         => new DT('2027-03-01 09:00:00', 'Europe/Berlin'),
            'end'           => new DT('2027-03-01 10:00:00', 'Europe/Berlin'),
            'allday'        => 0,
            'timezone_name' => 'Europe/Berlin',
        ]);
        $event->save(false);

        return new Event($osec_app, $post_id);
    }
}
