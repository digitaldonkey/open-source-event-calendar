<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\Tests\Utilities\HostileInput;
use Osec\Tests\Utilities\TestBase;

/**
 * No hostile input reaches the database as a tag.
 *
 * Covers the event save path, saved as an author (no unfiltered_html).
 *
 * @group event
 * @group escaping
 * @group security
 */
class EventSaveHostileInputTest extends TestBase
{
    use HostileInput;

    private const TEXT_FIELDS  = [
        'venue', 'address', 'city', 'province', 'postal_code', 'country',
        'cost', 'contact_name', 'contact_phone',
    ];
    private const URL_FIELDS   = ['ticket_url', 'contact_url'];
    private const EMAIL_FIELDS = ['contact_email'];

    public function tear_down()
    {
        $_REQUEST = [];
        parent::tear_down();
    }

    public function provide_field_values(): array
    {
        $data = [];
        foreach (array_merge(self::TEXT_FIELDS, self::URL_FIELDS, self::EMAIL_FIELDS) as $field) {
            foreach (self::hostile_values() as $label => $value) {
                $data["{$field}: {$label}"] = [$field, $value];
            }
        }

        return $data;
    }

    /**
     * Saved twice: the second save submits what the editor shows after the first.
     *
     * @dataProvider provide_field_values
     */
    public function test_save_stores_no_tag(string $field, string $value)
    {
        global $osec_app;

        $post_id = $this->create_event();
        wp_set_current_user(self::factory()->user->create(['role' => 'author']));

        $this->submit($post_id, $field, $value);
        $first = (new Event($osec_app, $post_id))->get($field);
        $this->assertStored($field, $first, 'First save.');

        $this->submit($post_id, $field, (string)$first);
        $this->assertStored($field, (new Event($osec_app, $post_id))->get($field), 'Second save.');
    }

    /**
     * Percent-encoded octets survive the save (they did not with sanitize_text_field()).
     */
    public function test_save_keeps_percent_encoded_urls()
    {
        global $osec_app;

        $url     = 'https://example.com/buy?q=M%C3%BCnchen%20Tickets&ref=1';
        $post_id = $this->create_event();
        foreach (self::URL_FIELDS as $field) {
            $this->submit($post_id, $field, $url);
            $this->assertSame($url, (new Event($osec_app, $post_id))->get($field), $field);
        }
    }

    public function test_lone_less_than_is_stable()
    {
        global $osec_app;

        $post_id = $this->create_event();
        $this->submit($post_id, 'venue', 'a<b');
        $stored = (new Event($osec_app, $post_id))->get('venue');
        $this->submit($post_id, 'venue', $stored);

        $this->assertSame($stored, (new Event($osec_app, $post_id))->get('venue'));
    }

    private function assertStored(string $field, mixed $stored, string $message): void
    {
        if (in_array($field, self::URL_FIELDS, true)) {
            self::assertSafeUrl($stored, "{$field}: {$message}");
        } else {
            self::assertNoTag($stored, "{$field}: {$message}");
        }
    }

    private function submit(int $post_id, string $field, string $value): void
    {
        global $osec_app;

        $_REQUEST = wp_slash([
            EventEditing::NONCE_NAME => wp_create_nonce(EventEditing::NONCE_ACTION),
            'osec_start_time'        => '2026-01-05 09:00:00',
            'osec_end_time'          => '2026-01-05 10:00:00',
            'osec_timezone_name'     => 'Europe/Berlin',
            'osec_' . $field         => $value,
        ]);
        EventEditing::factory($osec_app)->save_post($post_id, get_post($post_id), true);
        $_REQUEST = [];
    }

    private function create_event(): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event(
            $osec_app,
            [
                'post_id'       => $post_id,
                'post'          => get_post($post_id),
                'start'         => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                'end'           => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                'allday'        => 0,
                'timezone_name' => 'Europe/Berlin',
            ]
        ))->save(false);

        return $post_id;
    }
}
