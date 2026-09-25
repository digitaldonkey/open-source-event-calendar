<?php

namespace Osec\Tests\Unit\View;

use DOMDocument;
use DOMXPath;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\App\View\Admin\AdminPageAddEvent;
use Osec\App\View\Event\EventContactView;
use Osec\App\View\Event\EventLocationView;
use Osec\Tests\Utilities\TestBase;

/**
 * Values are escaped exactly once, and a save round trip keeps them.
 *
 * Up to 1.1.14 the views escaped in PHP and Twig escaped again, so every save
 * of the editor stored `D&amp;D` for `D&D`. The browser decoding is done with
 * DOMDocument, which decodes attribute entities like a browser does.
 *
 * @group event
 * @group escaping
 */
class EventEditorEscapingTest extends TestBase
{
    /**
     * Form fields => stored value, each with a character HTML escapes.
     */
    private const FIELDS = [
        'venue'         => 'D&D Bar',
        'address'       => 'Tom & Jerry Street 1',
        'city'          => 'Val d\'Isère',
        'province'      => 'Upper "Hills"',
        'postal_code'   => '1&2',
        'country'       => 'Bosnia & Herzegovina',
        'cost'          => '5 € "early bird"',
        'ticket_url'    => 'https://example.com/buy?event=1&ref=calendar',
        'contact_name'  => 'Ben & Jerry',
        'contact_phone' => '+1 555 & 4',
        'contact_email' => 'o\'brien&co@example.com',
        'contact_url'   => 'https://example.com/?a=1&b=2',
    ];

    public function tear_down()
    {
        $_REQUEST = [];
        parent::tear_down();
    }

    /**
     * One test only: AdminPageAddEvent::get_event() caches the event in a
     * static variable for the rest of the process.
     */
    public function test_editor_escapes_once_and_round_trips()
    {
        global $osec_app;

        $post_id = $this->create_event(self::FIELDS);
        $GLOBALS['post'] = get_post($post_id);
        setup_postdata($GLOBALS['post']);

        $page = AdminPageAddEvent::factory($osec_app);
        $html = $this->capture(fn() => $page->meta_box_location())
                . $this->capture(fn() => $page->meta_box_tickets())
                . $this->capture(fn() => $page->meta_box_organizer());

        $this->assertStringContainsString('value="D&amp;D Bar"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html, 'Escaped exactly once.');

        $submitted = $this->form_values($html);
        foreach (self::FIELDS as $field => $value) {
            $this->assertSame($value, $submitted['osec_' . $field] ?? null, "The browser shows {$field} as stored.");
        }

        $this->submit($post_id, $submitted);
        $event = new Event($osec_app, $post_id);
        foreach (self::FIELDS as $field => $value) {
            $this->assertSame($value, $event->get($field), "Saving keeps {$field}.");
        }
    }

    /**
     * Values an old version stored escaped are repaired by the next save.
     */
    public function test_save_heals_old_double_escaping()
    {
        global $osec_app;

        $post_id = $this->create_event([]);
        $this->submit($post_id, [
            'osec_venue'         => 'D&amp;D Bar',
            'osec_address'       => 'a<b',
            'osec_ticket_url'    => 'https://example.com/buy?event=1&amp;ref=calendar',
            'osec_contact_name'  => 'Ben &amp; Jerry',
            'osec_contact_email' => 'o&amp#039brien@example.com',
        ]);

        $event = new Event($osec_app, $post_id);
        $this->assertSame('D&D Bar', $event->get('venue'));
        $this->assertSame('a<b', $event->get('address'), 'A lone < is not stored as &lt;.');
        $this->assertSame('https://example.com/buy?event=1&ref=calendar', $event->get('ticket_url'));
        $this->assertSame('Ben & Jerry', $event->get('contact_name'));
        $this->assertSame('o\'brien@example.com', $event->get('contact_email'));
    }

    public function test_frontend_contact_escapes_once()
    {
        global $osec_app;

        $event = new Event($osec_app, $this->create_event(self::FIELDS));
        $html  = EventContactView::factory($osec_app)->get_contact_html($event);

        $this->assertStringContainsString('Ben &amp; Jerry', $html);
        $this->assertStringContainsString('href="https://example.com/?a=1&#038;b=2"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;#038;', $html);
    }

    public function test_frontend_contact_url_drops_unsafe_protocol()
    {
        global $osec_app;

        $event = new Event($osec_app, $this->create_event(['contact_url' => 'javascript:alert(1)']));
        $html  = EventContactView::factory($osec_app)->get_contact_html($event);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_frontend_location_escapes_once()
    {
        global $osec_app;

        $event = new Event(
            $osec_app,
            $this->create_event(self::FIELDS + ['show_map' => 1, 'latitude' => 52.5, 'longitude' => 13.4])
        );
        $location = EventLocationView::factory($osec_app);
        $html     = $location->get_location($event) . $location->get_map_public_view($event);

        $this->assertStringContainsString('D&amp;D Bar', $html);
        $this->assertStringContainsString('data-venue="D&amp;D Bar"', $html);
        $this->assertStringContainsString('data-attribution="&amp;copy; &lt;a href=', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    /**
     * @param  callable  $render  Echoes HTML.
     */
    private function capture(callable $render): string
    {
        ob_start();
        $render();

        return (string)ob_get_clean();
    }

    /**
     * Values of all named inputs, decoded like a browser.
     *
     * @return string[] Name => value.
     */
    private function form_values(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
        libxml_clear_errors();
        $values = [];
        foreach ((new DOMXPath($dom))->query('//input[@name]') as $input) {
            $unchecked = in_array($input->getAttribute('type'), ['checkbox', 'radio'], true)
                         && ! $input->hasAttribute('checked');
            if ($unchecked) {
                continue;
            }
            $values[$input->getAttribute('name')] = $input->getAttribute('value');
        }

        return $values;
    }

    /**
     * Saves the event like a submit of the editor.
     *
     * @param  string[]  $fields  Request parameters.
     */
    private function submit(int $post_id, array $fields): void
    {
        global $osec_app;

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $_REQUEST = wp_slash($fields + [
            EventEditing::NONCE_NAME => wp_create_nonce(EventEditing::NONCE_ACTION),
            'osec_start_time'        => '2026-01-05 09:00:00',
            'osec_end_time'          => '2026-01-05 10:00:00',
            'osec_timezone_name'     => 'Europe/Berlin',
        ]);
        EventEditing::factory($osec_app)->save_post($post_id, get_post($post_id), true);
    }

    /**
     * @param  array  $fields  Event fields, stored as given.
     *
     * @return int Post ID.
     */
    private function create_event(array $fields): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $event   = new Event(
            $osec_app,
            [
                'post_id'       => $post_id,
                'post'          => get_post($post_id),
                'start'         => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                'end'           => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                'allday'        => 0,
                'timezone_name' => 'Europe/Berlin',
            ] + $fields
        );
        $event->save(false);

        return $post_id;
    }
}
