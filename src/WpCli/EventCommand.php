<?php

namespace Osec\WpCli;

use Osec\App\Model\PostTypeEvent\InstanceRegenerator;
use Osec\Bootstrap\App;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Manages calendar events.
 */
class EventCommand extends \WP_CLI_Command
{
    use PrintsAdminNotices;

    private App $app;

    public function __construct(?App $app = null)
    {
        global $osec_app;
        $this->app = $app ?? $osec_app;
    }

    /**
     * Rebuilds the recurrence instances of events.
     *
     * Instances are only written when an event is saved, so a fix to the recurrence
     * generator does not reach events already stored until they are regenerated.
     * Rows left behind by deleted event posts are removed on the way.
     *
     * ## OPTIONS
     *
     * [<post_id>...]
     * : Event post IDs. Omit to process all events.
     *
     * [--feed=<feed_id>]
     * : Only events imported from this feed. See `wp osec feed list`.
     *
     * [--resave]
     * : Save each event like the editor does, instead of only rebuilding its instances.
     * Also drops recurrence rules the generator cannot use, and fires all save hooks.
     *
     * [--batch-size=<number>]
     * : Events per batch. The object cache is cleared after each batch.
     * ---
     * default: 500
     * ---
     *
     * [--start-after=<post_id>]
     * : Skip events up to and including this post ID. Every batch line prints the value
     * to resume an interrupted run with.
     * ---
     * default: 0
     * ---
     *
     * [--dry-run]
     * : Report what would be processed and removed, without writing.
     *
     * [--format=<format>]
     * : Output of --dry-run.
     * ---
     * default: summary
     * options:
     *   - summary
     *   - ids
     * ---
     *
     * [--yes]
     * : Do not ask for confirmation when processing all events.
     *
     * ## EXAMPLES
     *
     *     # Regenerate all events.
     *     $ wp osec event regenerate --yes
     *
     *     # Regenerate two events.
     *     $ wp osec event regenerate 123 456
     *
     *     # Resume an interrupted run.
     *     $ wp osec event regenerate --yes --start-after=4711
     *
     *     # On every site of a multisite network.
     *     $ wp site list --field=url | xargs -I{} wp --url={} osec event regenerate --yes
     *
     * @when after_wp_load
     */
    public function regenerate($args, $assoc_args)
    {
        $feed_id     = Utils\get_flag_value($assoc_args, 'feed');
        $resave      = (bool)Utils\get_flag_value($assoc_args, 'resave', false);
        $dry_run     = (bool)Utils\get_flag_value($assoc_args, 'dry-run', false);
        $batch_size  = (int)Utils\get_flag_value($assoc_args, 'batch-size', InstanceRegenerator::DEFAULT_BATCH_SIZE);
        $start_after = (int)Utils\get_flag_value($assoc_args, 'start-after', 0);
        $format      = Utils\get_flag_value($assoc_args, 'format', 'summary');

        if ($batch_size < 1) {
            WP_CLI::error('--batch-size must be a positive number.');
        }
        if ($args && null !== $feed_id) {
            WP_CLI::error('Pass either post IDs or --feed, not both.');
        }

        $regenerator = InstanceRegenerator::factory($this->app);
        $post_ids    = null;
        if ($args) {
            $post_ids = $this->positive_ids($args, 'post ID');
        } elseif (null !== $feed_id) {
            $post_ids = $regenerator->get_feed_post_ids($this->positive_ids([$feed_id], 'feed ID')[0]);
            if (null === $post_ids) {
                WP_CLI::error("Feed {$feed_id} does not exist.");
            }
        }

        $orphans = $regenerator->find_orphans($post_ids);
        $unknown = [];
        if (null !== $post_ids) {
            $existing = $regenerator->existing($post_ids);
            $unknown  = array_diff($post_ids, $existing, $orphans['instances']);
            // Rows of deleted posts are removed, not regenerated.
            $post_ids = array_values(array_diff($existing, $orphans['events']));
        }
        foreach ($unknown as $post_id) {
            WP_CLI::warning("Event {$post_id} does not exist.");
        }

        if ($dry_run) {
            $this->report_dry_run($regenerator, $post_ids, $start_after, $orphans, $format);

            return;
        }
        if (null === $post_ids) {
            $total = $regenerator->count(null, $start_after) - count($orphans['events']);
            WP_CLI::confirm("Regenerate the instances of all {$total} events?", $assoc_args);
        }

        $this->print_admin_notices(function () use (
            $regenerator,
            $orphans,
            $post_ids,
            $batch_size,
            $start_after,
            $resave,
            $unknown
        ) {
            $this->prune($regenerator, $orphans);
            $this->run_batches($regenerator, $post_ids, $batch_size, $start_after, $resave, count($unknown));
        });
    }

    /**
     * Regenerates batch by batch, printing one line per batch.
     */
    private function run_batches(
        InstanceRegenerator $regenerator,
        ?array $post_ids,
        int $batch_size,
        int $start_after,
        bool $resave,
        int $unknown
    ): void {
        $total    = $regenerator->count($post_ids, $start_after);
        $done     = 0;
        $failures = 0;
        $number   = 0;
        foreach ($regenerator->batches($post_ids, $batch_size, $start_after) as $batch) {
            $result = $regenerator->regenerate($batch, $resave);

            foreach ($result['warnings'] as $post_id => $messages) {
                foreach ($messages as $message) {
                    WP_CLI::warning("Event {$post_id}: {$message}");
                }
            }
            foreach ($result['failed'] as $post_id => $message) {
                $message = html_entity_decode(wp_strip_all_tags($message), ENT_QUOTES);
                WP_CLI::warning("Event {$post_id} failed: {$message}");
            }
            $done     += count($batch);
            $failures += count($result['failed']);
            $last      = end($batch);

            Utils\wp_clear_object_cache();
            WP_CLI::log(
                sprintf(
                    'Batch %d: %d/%d events, last post_id %d, %s memory - resume with --start-after=%d',
                    ++$number,
                    $done,
                    $total,
                    $last,
                    size_format(memory_get_usage(true)),
                    $last
                )
            );
        }

        if (0 === $done + $unknown) {
            WP_CLI::success('No events to regenerate.');

            return;
        }
        Utils\report_batch_operation_results(
            'event',
            'regenerate',
            $done + $unknown,
            $done - $failures,
            $failures + $unknown
        );
    }

    /**
     * Removes rows of deleted event posts and says what went.
     *
     * @param  InstanceRegenerator  $regenerator
     * @param  array  $orphans  From InstanceRegenerator::find_orphans().
     */
    private function prune(InstanceRegenerator $regenerator, array $orphans): void
    {
        if (! $orphans['events'] && ! $orphans['instances']) {
            return;
        }
        $regenerator->prune_orphans($orphans);
        WP_CLI::log(
            sprintf(
                'Removed %d event rows without a post and the instances of %d missing events.',
                count($orphans['events']),
                count($orphans['instances'])
            )
        );
    }

    private function report_dry_run(
        InstanceRegenerator $regenerator,
        ?array $post_ids,
        int $start_after,
        array $orphans,
        string $format
    ): void {
        $ids = [];
        foreach ($regenerator->batches($post_ids, InstanceRegenerator::DEFAULT_BATCH_SIZE, $start_after) as $batch) {
            array_push($ids, ...array_diff($batch, $orphans['events']));
        }
        if ('ids' === $format) {
            WP_CLI::line(implode(' ', $ids));

            return;
        }

        WP_CLI::log(
            $ids
                ? sprintf('Would regenerate %d events, post_id %d to %d.', count($ids), reset($ids), end($ids))
                : 'No events to regenerate.'
        );
        WP_CLI::log(
            sprintf(
                'Would remove %d event rows without a post (%s) and the instances of %d missing events (%s).',
                count($orphans['events']),
                $this->short_list($orphans['events']),
                count($orphans['instances']),
                $this->short_list($orphans['instances'])
            )
        );
    }

    /**
     * @param  int[]  $ids  IDs.
     *
     * @return string The first ten IDs, '-' for none.
     */
    private function short_list(array $ids): string
    {
        if (! $ids) {
            return '-';
        }

        return implode(' ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? ' ...' : '');
    }

    /**
     * @param  array  $values  Raw arguments.
     * @param  string  $noun  What they are, for the error.
     *
     * @return int[] Positive integers.
     */
    private function positive_ids(array $values, string $noun): array
    {
        $ids = [];
        foreach ($values as $value) {
            if (! ctype_digit((string)$value) || (int)$value < 1) {
                WP_CLI::error("Invalid {$noun}: {$value}");
            }
            $ids[] = (int)$value;
        }

        return $ids;
    }
}
