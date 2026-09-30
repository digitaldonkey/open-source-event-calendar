<?php

namespace Osec\App\Model\PostTypeEvent;

use Generator;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Rebuilds event instances on demand.
 *
 * Instances are only written by Event::save(), so a fix to the generator does
 * not reach events already stored. This walks stored events in keyset batches,
 * so any number of them can be processed with flat memory, and removes rows
 * left behind by deletions that skipped the TrashController cleanup.
 *
 * @since 1.1.15
 */
class InstanceRegenerator extends OsecBaseClass
{
    public const DEFAULT_BATCH_SIZE = 500;

    /**
     * Post IDs of the events imported from a feed.
     *
     * @param  int  $feed_id  Feed ID.
     *
     * @return int[]|null Post IDs, null if there is no such feed.
     */
    public function get_feed_post_ids(int $feed_id): ?array
    {
        $db       = $this->app->db;
        $feed_url = $db->get_var(
            $db->prepare(
                'SELECT feed_url FROM ' . $db->get_table_name(OSEC_DB__FEEDS) . ' WHERE feed_id = %d',
                $feed_id
            )
        );
        if (null === $feed_url) {
            return null;
        }

        return array_map('intval', EventSearch::factory($this->app)->get_event_ids_for_feed($feed_url));
    }

    /**
     * The given post IDs that have an event row.
     *
     * @param  int[]  $post_ids  Post IDs.
     *
     * @return int[] Existing ones, ascending.
     */
    public function existing(array $post_ids): array
    {
        if (! $post_ids) {
            return [];
        }
        $db   = $this->app->db;
        $list = implode(',', array_map('absint', $post_ids));

        return array_map(
            'intval',
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- absint-secured.
            $db->get_col(
                'SELECT post_id FROM ' . $db->get_table_name(OSEC_DB__EVENTS) .
                " WHERE post_id IN ({$list}) ORDER BY post_id"
            )
        );
    }

    /**
     * Number of events a run would process.
     *
     * @param  int[]|null  $post_ids  Limit to these, null for all events.
     * @param  int  $start_after  Skip post IDs up to and including this one.
     *
     * @return int Count.
     */
    public function count(?array $post_ids, int $start_after = 0): int
    {
        if (null !== $post_ids) {
            return count($this->filter_ids($post_ids, $start_after));
        }
        $db = $this->app->db;

        return (int)$db->get_var(
            $db->prepare(
                'SELECT COUNT(*) FROM ' . $db->get_table_name(OSEC_DB__EVENTS) . ' WHERE post_id > %d',
                $start_after
            )
        );
    }

    /**
     * Post IDs of stored events, in ascending batches.
     *
     * Paged by the last post ID seen rather than by offset, so rows removed
     * during the run do not shift the pages.
     *
     * @param  int[]|null  $post_ids  Limit to these, null for all events.
     * @param  int  $batch_size  Post IDs per batch.
     * @param  int  $start_after  Skip post IDs up to and including this one.
     *
     * @return Generator<int[]> Batches of post IDs.
     */
    public function batches(
        ?array $post_ids,
        int $batch_size = self::DEFAULT_BATCH_SIZE,
        int $start_after = 0
    ): Generator {
        $batch_size = max(1, $batch_size);
        if (null !== $post_ids) {
            yield from array_chunk($this->filter_ids($post_ids, $start_after), $batch_size);

            return;
        }

        $db    = $this->app->db;
        $table = $db->get_table_name(OSEC_DB__EVENTS);
        $last  = $start_after;
        do {
            $batch = array_map(
                'intval',
                $db->get_col(
                    $db->prepare(
                        "SELECT post_id FROM {$table} WHERE post_id > %d ORDER BY post_id LIMIT %d",
                        $last,
                        $batch_size
                    )
                )
            );
            $found = count($batch);
            if ($found) {
                $last = end($batch);
                yield $batch;
            }
        } while ($found === $batch_size);
    }

    /**
     * Rebuilds the instances of the given events.
     *
     * @param  int[]  $post_ids  Event post IDs.
     * @param  bool  $resave  Save the whole event like the editor does, instead of
     *                        only rebuilding its instances.
     *
     * @return array{processed: int[], failed: array<int, string>, warnings: array<int, string[]>}
     *   Failed and warnings are keyed by post ID.
     */
    public function regenerate(array $post_ids, bool $resave = false): array
    {
        $result  = [
            'processed' => [],
            'failed'    => [],
            'warnings'  => [],
        ];
        $current = 0;

        $on_truncated = function ($rrule, $limit) use (&$result, &$current) {
            $result['warnings'][$current][] = sprintf(
                'Recurrence "%s" stopped after %d instances.',
                $rrule,
                $limit
            );
        };
        $on_invalid   = function ($rrule, $message) use (&$result, &$current) {
            $result['warnings'][$current][] = sprintf(
                'Recurrence "%s" is not usable: %s',
                $rrule,
                $message
            );
        };
        add_action('osec_recurrence_truncated', $on_truncated, 10, 2);
        add_action('osec_recurrence_rule_invalid', $on_invalid, 10, 2);
        if ($resave) {
            wp_defer_term_counting(true);
        }

        try {
            $instances = EventInstance::factory($this->app);
            foreach ($post_ids as $post_id) {
                $current = (int)$post_id;
                try {
                    $event = new Event($this->app, $current);
                    if ($resave) {
                        $event->save(true);
                    } else {
                        $instances->recreate($event);
                    }
                    $result['processed'][] = $current;
                } catch (\Throwable $e) {
                    $result['failed'][$current] = $e->getMessage();
                }
            }
        } finally {
            remove_action('osec_recurrence_truncated', $on_truncated, 10);
            remove_action('osec_recurrence_rule_invalid', $on_invalid, 10);
            if ($resave) {
                wp_defer_term_counting(false);
            }
        }

        return $result;
    }

    /**
     * Rows left behind by a deleted event post.
     *
     * An event row whose post is gone cannot be shown, edited or restored, and
     * instance rows without an event row are derived data. Both remain when a post
     * is deleted without the TrashController cleanup.
     *
     * @param  int[]|null  $post_ids  Limit to these, null for all.
     *
     * @return array{events: int[], instances: int[]} Post IDs of event rows without
     *   a post, and of instance rows without an event row.
     */
    public function find_orphans(?array $post_ids = null): array
    {
        $db        = $this->app->db;
        $events    = $db->get_table_name(OSEC_DB__EVENTS);
        $instances = $db->get_table_name(OSEC_DB__INSTANCES);
        $posts     = $db->get_table_name('posts');

        if ([] === $post_ids) {
            return [
                'events'    => [],
                'instances' => [],
            ];
        }
        $in_events    = '';
        $in_instances = '';
        if (null !== $post_ids) {
            $list         = implode(',', array_map('absint', $post_ids));
            $in_events    = " AND e.post_id IN ({$list})";
            $in_instances = " AND i.post_id IN ({$list})";
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names and absint-secured IDs.
        $orphaned_events    = $db->get_col(
            "SELECT e.post_id FROM {$events} e LEFT JOIN {$posts} p ON p.ID = e.post_id
                WHERE p.ID IS NULL{$in_events} ORDER BY e.post_id"
        );
        $orphaned_instances = $db->get_col(
            "SELECT DISTINCT i.post_id FROM {$instances} i LEFT JOIN {$events} e ON e.post_id = i.post_id
                WHERE e.post_id IS NULL{$in_instances} ORDER BY i.post_id"
        );
        // phpcs:enable

        return [
            'events'    => array_map('intval', $orphaned_events),
            'instances' => array_map('intval', $orphaned_instances),
        ];
    }

    /**
     * Deletes the rows find_orphans() reported, with their instances.
     *
     * @param  array{events: int[], instances: int[]}  $orphans  From find_orphans().
     *
     * @return void
     */
    public function prune_orphans(array $orphans): void
    {
        $db        = $this->app->db;
        $instances = EventInstance::factory($this->app);
        foreach ($orphans['events'] as $post_id) {
            $db->delete(OSEC_DB__EVENTS, ['post_id' => $post_id], ['%d']);
            $instances->clean($post_id);
        }
        foreach ($orphans['instances'] as $post_id) {
            $instances->clean($post_id);
        }
    }

    /**
     * Unique, positive, ascending IDs above $start_after.
     *
     * @param  int[]  $post_ids  Post IDs.
     * @param  int  $start_after  Lower bound, exclusive.
     *
     * @return int[] Filtered IDs.
     */
    protected function filter_ids(array $post_ids, int $start_after): array
    {
        $post_ids = array_unique(array_map('intval', $post_ids));
        $post_ids = array_filter($post_ids, fn($id) => $id > $start_after);
        sort($post_ids);

        return $post_ids;
    }
}
