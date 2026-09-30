<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * The UID an event is exported with.
 *
 * Event::save() stores it, so the export, which anyone can fetch, only reads.
 *
 * @group ics
 */
class IcsExportUidTest extends TestBase
{
    public function test_save_stores_the_uid_of_a_new_post()
    {
        $event   = $this->new_event(['post' => $this->post_args('New post')]);
        $post_id = $event->save();

        $this->assertSame($this->expected_uid($post_id), $this->stored_uid($post_id));
    }

    public function test_save_stores_the_uid_of_an_existing_post()
    {
        $post_id = self::factory()->post->create($this->post_args('Existing post'));
        $this->new_event(['post_id' => $post_id, 'post' => get_post($post_id)])->save();

        $this->assertSame($this->expected_uid($post_id), $this->stored_uid($post_id));
    }

    public function test_save_keeps_a_uid_from_a_feed()
    {
        $event   = $this->new_event(['post' => $this->post_args('Imported'), 'ical_uid' => 'feed-uid@example.com']);
        $post_id = $event->save();

        $this->assertSame('feed-uid@example.com', $this->stored_uid($post_id));
    }

    public function test_export_does_not_write_an_event_without_a_stored_uid()
    {
        global $osec_app, $wpdb;

        $post_id = $this->new_event([
            'post'             => $this->post_args('Legacy event'),
            'recurrence_rules' => 'FREQ=DAILY;COUNT=3',
        ])->save();
        $wpdb->update($osec_app->db->get_table_name(OSEC_DB__EVENTS), ['ical_uid' => ''], ['post_id' => $post_id]);
        $instances = $this->instance_ids($post_id);

        $saves = 0;
        add_action('osec_pre_save_event', function () use (&$saves) {
            ++$saves;
        });

        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events'                    => [new Event($osec_app, $post_id)],
            'do_not_export_as_calendar' => false,
        ]);

        $this->assertStringContainsString('UID:' . $this->expected_uid($post_id), $ics);
        $this->assertSame(0, $saves, 'The export does not save the event.');
        $this->assertEmpty($this->stored_uid($post_id), 'The UID is not written.');
        $this->assertSame($instances, $this->instance_ids($post_id));
    }

    protected function new_event(array $data): Event
    {
        global $osec_app;

        $start = new DT('2026-07-06 09:00:00', 'UTC');

        return new Event(
            $osec_app,
            $data + [
                'start'         => $start,
                'end'           => new DT('2026-07-06 10:00:00', 'UTC'),
                'allday'        => false,
                'instant_event' => false,
                'timezone_name' => 'UTC',
            ]
        );
    }

    protected function post_args(string $title): array
    {
        return [
            'post_status' => 'publish',
            'post_type'   => OSEC_POST_TYPE,
            'post_title'  => $title,
        ];
    }

    protected function expected_uid(int $post_id): string
    {
        $site_url = wp_parse_url(get_site_url());

        return 'OSEC-' . $post_id . '@' . $site_url['host'] . ($site_url['path'] ?? '');
    }

    protected function stored_uid(int $post_id): ?string
    {
        global $osec_app, $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                'SELECT ical_uid FROM %i WHERE post_id = %d',
                $osec_app->db->get_table_name(OSEC_DB__EVENTS),
                $post_id
            )
        );
    }

    protected function instance_ids(int $post_id): array
    {
        global $osec_app, $wpdb;

        return $wpdb->get_col(
            $wpdb->prepare(
                'SELECT id FROM %i WHERE post_id = %d ORDER BY id',
                $osec_app->db->get_table_name(OSEC_DB__INSTANCES),
                $post_id
            )
        );
    }
}
