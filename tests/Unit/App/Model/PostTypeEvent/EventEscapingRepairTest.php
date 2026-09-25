<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventEscapingRepair;
use Osec\Tests\Utilities\TestBase;

/**
 * Repair of fields stored escaped by versions up to 1.1.14.
 *
 * The corrupt values are the ones a save through the old editor produced:
 * esc_attr() -> Twig escape -> browser decode -> sanitize_*().
 *
 * @group event
 * @group escaping
 */
class EventEscapingRepairTest extends TestBase
{
    /**
     * @dataProvider provide_text
     */
    public function test_decode_text(string $stored, string $expected)
    {
        $this->assertSame($expected, EventEscapingRepair::decode_text($stored));
    }

    public function provide_text(): array
    {
        return [
            'ampersand'           => ['D&amp;D Bar', 'D&D Bar'],
            'quotes'              => ['O&#039;Brien &quot;Pub&quot;', 'O\'Brien "Pub"'],
            'short apostrophe'    => ['O&#39;Brien', 'O\'Brien'],
            'angle brackets'      => ['a &lt; b &gt; c', 'a < b > c'],
            'url'                 => ['https://example.com/buy?event=1&amp;ref=cal', 'https://example.com/buy?event=1&ref=cal'],
            'one level only'      => ['Tom &amp;amp; Jerry', 'Tom &amp; Jerry'],
            'other entities stay' => ['Caf&eacute; &copy;', 'Caf&eacute; &copy;'],
            'clean'               => ['D&D Bar', 'D&D Bar'],
        ];
    }

    /**
     * sanitize_email() strips the `;`, so each save grew the chain.
     *
     * @dataProvider provide_email
     */
    public function test_decode_email(string $stored, string $expected)
    {
        $this->assertSame($expected, EventEscapingRepair::decode_email($stored));
    }

    public function provide_email(): array
    {
        return [
            'ampersand, 1 save'   => ['a&ampb@example.com', 'a&b@example.com'],
            'ampersand, 3 saves'  => ['a&ampampampb@example.com', 'a&b@example.com'],
            'apostrophe, 1 save'  => ['o&#039brien@example.com', 'o\'brien@example.com'],
            'apostrophe, 3 saves' => ['o&ampamp#039brien@example.com', 'o\'brien@example.com'],
            'clean'               => ['o\'brien&co@example.com', 'o\'brien&co@example.com'],
        ];
    }

    public function test_find_and_repair_events_and_feeds()
    {
        global $osec_app;

        $db      = $osec_app->db;
        $corrupt = $this->create_event([
            'venue'         => 'D&amp;D Bar',
            'address'       => 'Tom &amp; Jerry Street 1',
            'ticket_url'    => 'https://example.com/buy?event=1&amp;ref=cal',
            'contact_email' => 'o&amp#039brien@example.com',
        ]);
        $clean   = $this->create_event([
            'venue'         => 'D&D Bar',
            'contact_email' => 'o\'brien@example.com',
        ]);
        $db->insert(
            OSEC_DB__FEEDS,
            [
                'feed_url'      => 'https://example.com/cal.ics?a=1&amp;b=2',
                'feed_name'     => 'https://example.com/cal.ics?a=1&amp;b=2',
                'feed_category' => '',
                'feed_tags'     => '',
            ]
        );
        $feed_id = (int)$db->get_insert_id();

        $repair  = EventEscapingRepair::factory($osec_app);
        $changes = $repair->find_affected();

        $this->assertSame(
            [
                ['events', $corrupt, 'venue', 'D&D Bar'],
                ['events', $corrupt, 'address', 'Tom & Jerry Street 1'],
                ['events', $corrupt, 'ticket_url', 'https://example.com/buy?event=1&ref=cal'],
                ['events', $corrupt, 'contact_email', 'o\'brien@example.com'],
                ['feeds', $feed_id, 'feed_url', 'https://example.com/cal.ics?a=1&b=2'],
                ['feeds', $feed_id, 'feed_name', 'https://example.com/cal.ics?a=1&b=2'],
            ],
            array_map(fn($c) => [$c['table'], $c['id'], $c['column'], $c['after']], $changes),
            'The clean event is not listed.'
        );

        $this->assertSame(2, $repair->repair($changes), 'One event row and one feed row.');
        $this->assertSame([], $repair->find_affected());

        $event = new Event($osec_app, $corrupt);
        $this->assertSame('D&D Bar', $event->get('venue'));
        $this->assertSame('o\'brien@example.com', $event->get('contact_email'));
        $this->assertSame('D&D Bar', (new Event($osec_app, $clean))->get('venue'));
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
