<?php

namespace Osec\WpCli;

use Osec\App\Controller\FeedsController;
use Osec\Bootstrap\App;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Manages iCalendar feed subscriptions.
 */
class FeedCommand extends \WP_CLI_Command
{
    use PrintsAdminNotices;

    private App $app;

    public function __construct(?App $app = null)
    {
        global $osec_app;
        $this->app = $app ?? $osec_app;
    }

    /**
     * Lists the subscribed feeds.
     *
     * ## OPTIONS
     *
     * [--fields=<fields>]
     * : Limit the output to specific fields.
     * ---
     * default: feed_id,feed_name,feed_url,keep_old_events
     * ---
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - yaml
     *   - ids
     *   - count
     * ---
     *
     * ## AVAILABLE FIELDS
     *
     * feed_id, feed_url, feed_name, feed_category, feed_tags, comments_enabled,
     * map_display_enabled, keep_tags_categories, keep_old_events, import_post_status,
     * import_timezone
     *
     * ## EXAMPLES
     *
     *     $ wp osec feed list
     *     $ wp osec feed list --format=ids
     *
     * @subcommand list
     * @when after_wp_load
     */
    public function list_($args, $assoc_args)
    {
        $feeds  = FeedsController::factory($this->app)->get_feeds();
        $format = Utils\get_flag_value($assoc_args, 'format', 'table');
        if ('ids' === $format) {
            WP_CLI::line(implode(' ', wp_list_pluck($feeds, 'feed_id')));

            return;
        }
        Utils\format_items($format, $feeds, Utils\get_flag_value($assoc_args, 'fields'));
    }

    /**
     * Imports feeds now, instead of waiting for the scheduled import.
     *
     * Problems are printed here and not stored as admin notices.
     *
     * ## OPTIONS
     *
     * [<feed_id>...]
     * : Feed IDs, see `wp osec feed list`. Omit to update all feeds.
     *
     * [--force]
     * : Break the import lock of a feed. Only use it after a crashed import: forcing
     * while another import of the same feed really runs can create duplicate events.
     *
     * [--dry-run]
     * : List the feeds that would be updated, without fetching them.
     *
     * [--yes]
     * : Do not ask for confirmation when updating all feeds.
     *
     * ## EXAMPLES
     *
     *     # Update all feeds.
     *     $ wp osec feed update --yes
     *
     *     # Update one feed whose last import crashed.
     *     $ wp osec feed update 3 --force
     *
     * @when after_wp_load
     */
    public function update($args, $assoc_args)
    {
        $force   = (bool)Utils\get_flag_value($assoc_args, 'force', false);
        $dry_run = (bool)Utils\get_flag_value($assoc_args, 'dry-run', false);
        $feeds   = FeedsController::factory($this->app);

        $requested = [];
        foreach ($args as $arg) {
            if (! ctype_digit((string)$arg) || (int)$arg < 1) {
                WP_CLI::error("Invalid feed ID: {$arg}");
            }
            $requested[] = (int)$arg;
        }
        $requested = array_values(array_unique($requested));

        $rows  = $feeds->get_feeds($requested);
        $known = array_map('intval', wp_list_pluck($rows, 'feed_id'));
        $total = $requested ? count($requested) : count($rows);

        $failures = 0;
        foreach (array_diff($requested, $known) as $unknown) {
            WP_CLI::warning("Feed {$unknown} does not exist.");
            ++$failures;
        }

        if ($dry_run) {
            if (! $rows) {
                WP_CLI::log('No feeds to update.');
            }
            foreach ($rows as $row) {
                WP_CLI::log("Would update feed {$row->feed_id}: {$row->feed_url}");
            }

            return;
        }
        if (! $requested) {
            WP_CLI::confirm('Update all ' . count($rows) . ' feeds?', $assoc_args);
        }

        // The result message already carries what a notice would say.
        $failures += $this->print_admin_notices(function () use ($feeds, $rows, $force) {
            $failed = 0;
            foreach ($rows as $row) {
                $feed_id = (int)$row->feed_id;
                if ($force) {
                    $this->report_forced_lock($feeds, $feed_id);
                }
                try {
                    $result = $feeds->update_ics($feed_id, $force)['data'];
                } catch (\Throwable $e) {
                    $result = [
                        'error'   => true,
                        'message' => $e->getMessage(),
                    ];
                }
                $message = html_entity_decode(wp_strip_all_tags((string)$result['message']), ENT_QUOTES);
                $message = trim(preg_replace('/\s+/', ' ', $message));
                if (! empty($result['error'])) {
                    WP_CLI::warning("Feed {$feed_id} ({$row->feed_url}): {$message}");
                    ++$failed;
                } else {
                    WP_CLI::log("Feed {$feed_id} ({$row->feed_url}): {$message}");
                }
                Utils\wp_clear_object_cache();
            }

            return $failed;
        }, false);

        if (0 === $total) {
            WP_CLI::success('No feeds to update.');

            return;
        }
        Utils\report_batch_operation_results('feed', 'update', $total, $total - $failures, $failures);
    }

    private function report_forced_lock(FeedsController $feeds, int $feed_id): void
    {
        $lock = $feeds->get_import_lock($feed_id);
        if (null === $lock) {
            return;
        }
        WP_CLI::warning(
            sprintf(
                'Feed %d was locked %s ago by PID %d, forcing.',
                $feed_id,
                human_time_diff($lock['time']),
                $lock['pid']
            )
        );
    }
}
