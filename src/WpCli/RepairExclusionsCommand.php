<?php

namespace Osec\WpCli;

use Osec\App\Controller\ExclusionRepairController;
use Osec\App\Model\PostTypeEvent\EventExclusionRepair;
use Osec\Bootstrap\App;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Repairs events whose exclusion dates were merged into the repeat rule.
 */
class RepairExclusionsCommand extends \WP_CLI_Command
{
    use ConfirmsAll;

    private App $app;

    public function __construct(?App $app = null)
    {
        global $osec_app;
        $this->app = $app ?? $osec_app;
    }

    /**
     * Brings back the dates of repeating events that collapsed to their first date.
     *
     * Versions 1.1.15 to 1.2.0 merged a custom exclusion date into the repeat rule, so the
     * exclusion matched the whole series. An event also heals on its next save in the editor.
     *
     * This cannot be undone. If you already added missing dates by hand, they will show twice,
     * so check the --dry-run list first. Events with an unusable rule or dates are skipped.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : List the events that would change, without writing.
     *
     * [--format=<format>]
     * : Output of --dry-run.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - count
     * ---
     *
     * [--yes|y]
     * : Do not ask for confirmation. Short: -y
     *
     * ## EXAMPLES
     *
     *     # Show what would change.
     *     $ wp osec repair-exclusions --dry-run
     *
     *     # Repair.
     *     $ wp osec repair-exclusions --yes
     *
     *     # On every site of a multisite network.
     *     $ wp site list --field=url | xargs -I{} wp --url={} osec repair-exclusions --yes
     *
     * @when after_wp_load
     */
    public function __invoke($args, $assoc_args)
    {
        $this->accept_yes_alias($args, $assoc_args);
        $dry_run = (bool)Utils\get_flag_value($assoc_args, 'dry-run', false);
        $format  = Utils\get_flag_value($assoc_args, 'format', 'table');

        $repair = EventExclusionRepair::factory($this->app);
        $events = $repair->find_affected();

        if ($dry_run) {
            Utils\format_items(
                $format,
                $events,
                ['post_id', 'title', 'status', 'dates', 'has_children', 'repairable']
            );

            return;
        }
        if (! $events) {
            WP_CLI::success('Nothing to repair.');

            return;
        }
        $this->confirm_all('Repair ' . count($events) . ' events?', $assoc_args);
        $result = $repair->repair($events);
        ExclusionRepairController::factory($this->app)->refresh_notice();

        if ($result['skipped']) {
            WP_CLI::warning('Skipped, repeat rule or dates not usable: ' . implode(', ', $result['skipped']));
        }
        if ($result['failed']) {
            WP_CLI::warning('Failed: ' . implode(', ', $result['failed']));
        }
        WP_CLI::success(sprintf('Repaired %d events.', $result['repaired']));
    }
}
