<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\InstanceRegenerator;
use Osec\Tests\Utilities\TestBase;

/**
 * Rebuilding instances on demand, behind `wp osec event regenerate`.
 *
 * @group event
 * @group recurrence
 */
class InstanceRegeneratorTest extends TestBase
{
    private const TIMEZONE = 'Europe/Berlin';

    public function test_regenerate_rebuilds_deleted_instances()
    {
        global $osec_app;

        $post_id = $this->create_event('FREQ=DAILY;COUNT=5');
        $this->assertSame(5, $this->instance_count($post_id));

        $osec_app->db->delete(OSEC_DB__INSTANCES, ['post_id' => $post_id], ['%d']);
        $this->assertSame(0, $this->instance_count($post_id));

        $result = InstanceRegenerator::factory($osec_app)->regenerate([$post_id]);

        $this->assertSame([$post_id], $result['processed']);
        $this->assertSame([], $result['failed']);
        $this->assertSame(5, $this->instance_count($post_id));
    }

    public function test_resave_rebuilds_deleted_instances()
    {
        global $osec_app;

        $post_id = $this->create_event('FREQ=DAILY;COUNT=3');
        $osec_app->db->delete(OSEC_DB__INSTANCES, ['post_id' => $post_id], ['%d']);

        $result = InstanceRegenerator::factory($osec_app)->regenerate([$post_id], true);

        $this->assertSame([$post_id], $result['processed']);
        $this->assertSame(3, $this->instance_count($post_id));
    }

    /**
     * A failing event is reported and does not stop the others.
     */
    public function test_unknown_event_fails_without_stopping_the_run()
    {
        global $osec_app;

        $post_id = $this->create_event('FREQ=DAILY;COUNT=2');

        $result = InstanceRegenerator::factory($osec_app)->regenerate([999999, $post_id]);

        $this->assertSame([$post_id], $result['processed']);
        $this->assertArrayHasKey(999999, $result['failed']);
        $this->assertSame([$post_id], InstanceRegenerator::factory($osec_app)->existing([999999, $post_id]));
    }

    /**
     * A rule stored by an older version is reported with its event.
     */
    public function test_invalid_stored_rule_is_reported_as_warning()
    {
        global $osec_app;

        $post_id = $this->create_event('');
        $osec_app->db->update(
            $osec_app->db->get_table_name(OSEC_DB__EVENTS),
            ['recurrence_rules' => 'FREQ=YEARLY;BYMONTH=13'],
            ['post_id' => $post_id],
            ['%s'],
            ['%d']
        );

        $result = InstanceRegenerator::factory($osec_app)->regenerate([$post_id]);

        $this->assertSame([$post_id], $result['processed']);
        $this->assertCount(1, $result['warnings'][$post_id]);
        $this->assertStringContainsString('BYMONTH=13', $result['warnings'][$post_id][0]);
        $this->assertFalse(
            has_action('osec_recurrence_rule_invalid'),
            'The listeners must not outlive the run.'
        );
    }

    public function test_batches_cover_every_event_once_in_order()
    {
        global $osec_app;

        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            $ids[] = $this->create_event('');
        }
        $regenerator = InstanceRegenerator::factory($osec_app);

        $seen    = [];
        $batches = 0;
        foreach ($regenerator->batches(null, 2) as $batch) {
            $this->assertLessThanOrEqual(2, count($batch));
            array_push($seen, ...$batch);
            ++$batches;
        }
        $this->assertSame($seen, array_values(array_unique($seen)), 'No event twice.');
        $this->assertSame($seen, $this->sorted($seen), 'Ascending.');
        $this->assertEmpty(array_diff($ids, $seen), 'Every event.');
        $this->assertSame(count($seen), $regenerator->count(null));

        $after = $ids[2];
        $rest  = iterator_to_array($this->flatten($regenerator->batches(null, 2, $after)), false);
        $this->assertSame(array_values(array_filter($seen, fn($id) => $id > $after)), $rest, 'Resumes after.');
    }

    public function test_batches_of_given_ids_are_unique_and_sorted()
    {
        global $osec_app;

        $batches = iterator_to_array(
            InstanceRegenerator::factory($osec_app)->batches([5, 3, 5, 9, 1], 2, 1),
            false
        );

        $this->assertSame([[3, 5], [9]], $batches);
    }

    public function test_feed_post_ids()
    {
        global $osec_app;

        $url = 'https://example.org/feed.ics';
        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            ['feed_url' => $url, 'feed_name' => 'Feed', 'feed_category' => '', 'feed_tags' => ''],
        );
        $feed_id  = (int)$osec_app->db->get_insert_id();
        $imported = $this->create_event('');
        $this->create_event('');
        $osec_app->db->update(
            $osec_app->db->get_table_name(OSEC_DB__EVENTS),
            ['ical_feed_url' => $url],
            ['post_id' => $imported]
        );

        $regenerator = InstanceRegenerator::factory($osec_app);

        $this->assertSame([$imported], $regenerator->get_feed_post_ids($feed_id));
        $this->assertNull($regenerator->get_feed_post_ids(999999));
    }

    /**
     * Rows of a post deleted without the TrashController cleanup.
     */
    public function test_orphans_are_found_and_pruned()
    {
        global $osec_app;

        $kept           = $this->create_event('FREQ=DAILY;COUNT=2');
        $without_post   = $this->create_event('FREQ=DAILY;COUNT=2');
        $without_event  = $this->create_event('FREQ=DAILY;COUNT=2');
        $osec_app->db->delete('posts', ['ID' => $without_post], ['%d']);
        $osec_app->db->delete(OSEC_DB__EVENTS, ['post_id' => $without_event], ['%d']);

        $regenerator = InstanceRegenerator::factory($osec_app);
        $orphans     = $regenerator->find_orphans();

        $this->assertSame([$without_post], $orphans['events']);
        $this->assertSame([$without_event], $orphans['instances']);
        $this->assertSame(
            ['events' => [], 'instances' => []],
            $regenerator->find_orphans([$kept]),
            'Scoped to the given IDs.'
        );

        $regenerator->prune_orphans($orphans);

        $this->assertSame(['events' => [], 'instances' => []], $regenerator->find_orphans());
        $this->assertSame(0, $this->instance_count($without_post));
        $this->assertSame(0, $this->instance_count($without_event));
        $this->assertSame([], $regenerator->existing([$without_post]));
        $this->assertSame(2, $this->instance_count($kept), 'Other events are untouched.');
    }

    private function create_event(string $rrule): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $event   = new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT('2026-01-05 09:00:00', self::TIMEZONE),
                'end'              => new DT('2026-01-05 10:00:00', self::TIMEZONE),
                'allday'           => 0,
                'timezone_name'    => self::TIMEZONE,
                'recurrence_rules' => $rrule,
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        );
        $event->save(false);

        return $post_id;
    }

    private function instance_count(int $post_id): int
    {
        global $osec_app;

        return (int)$osec_app->db->get_var(
            $osec_app->db->prepare(
                'SELECT COUNT(*) FROM ' . $osec_app->db->get_table_name(OSEC_DB__INSTANCES) . ' WHERE post_id = %d',
                $post_id
            )
        );
    }

    private function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }

    private function flatten(iterable $batches): \Generator
    {
        foreach ($batches as $batch) {
            yield from $batch;
        }
    }
}
