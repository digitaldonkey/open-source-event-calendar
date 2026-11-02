<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * The ICS export runs without a login, so it leaves out what the event page hides: the cost with "Hide event
 * cost", and coordinates with the map off. A feed made of such an export still imports.
 *
 * @group ics
 */
class IcsExportHiddenValuesTest extends TestBase
{
    public function test_hidden_cost_and_coordinates_are_left_out()
    {
        $ics = $this->export(true);

        self::assertStringNotContainsString('X-COST', $ics);
        self::assertStringNotContainsString('99 EUR', $ics);
        self::assertStringNotContainsString('GEO:', $ics);
        self::assertStringContainsString('SUMMARY:Hidden values', $ics, 'The event itself is exported.');
    }

    public function test_shown_cost_and_coordinates_are_exported()
    {
        $ics = $this->export(false);

        self::assertStringContainsString('X-COST:99 EUR', $ics);
        self::assertStringContainsString('GEO:', $ics);
    }

    public function test_export_without_the_hidden_values_still_imports()
    {
        global $osec_app, $wpdb;

        $ics = $this->export(true);
        $wpdb->query("DELETE FROM {$wpdb->prefix}osec_events");

        IcsImportExportParser::factory($osec_app)->import([
            'events_in_db'   => [],
            'comment_status' => 'closed',
            'do_show_map'    => 0,
            'source'         => $ics,
            'feed'           => (object)[
                'feed_id'              => '1',
                'feed_url'             => 'https://example.org/hidden.ics',
                'feed_name'            => 'hidden',
                'feed_category'        => '',
                'feed_tags'            => '',
                'hide_cost'            => '0',
                'comments_enabled'     => '0',
                'map_display_enabled'  => '0',
                'keep_tags_categories' => '1',
                'keep_old_events'      => '0',
                'import_timezone'      => '0',
                'import_post_status'   => 'publish',
            ],
        ]);

        $post_id = (int)$wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}osec_events LIMIT 1");
        self::assertGreaterThan(0, $post_id, 'The event is imported.');
        $event = new Event($osec_app, $post_id);
        self::assertSame('Hidden values', get_the_title($post_id));
        self::assertEmpty($event->get('cost'));
        self::assertEmpty((float)$event->get('latitude'));
    }

    /**
     * Exports one event with cost and coordinates.
     *
     * @param  bool  $hide  Whether the event hides its cost and map.
     *
     * @return string ICS text.
     */
    private function export(bool $hide): string
    {
        global $osec_app;

        $post_id = self::factory()->post->create(
            ['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Hidden values']
        );
        $event   = new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT('2027-03-01 09:00:00', 'Europe/Berlin'),
                'end'              => new DT('2027-03-01 10:00:00', 'Europe/Berlin'),
                'allday'           => 0,
                'timezone_name'    => 'Europe/Berlin',
                'latitude'         => 1.5,
                'longitude'        => 2.5,
                'show_map'         => $hide ? 0 : 1,
                'show_coordinates' => 1,
            ]
        );
        $event->set('cost', '99 EUR');
        $event->set('is_free', false);
        $event->set('hide_cost', $hide);
        $event->save(false);

        return IcsImportExportParser::factory($osec_app)->export([
            'events'                    => [new Event($osec_app, $post_id)],
            'do_not_export_as_calendar' => false,
        ]);
    }
}
