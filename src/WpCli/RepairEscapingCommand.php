<?php

namespace Osec\WpCli;

use Osec\App\Controller\EscapingRepairController;
use Osec\App\Model\PostTypeEvent\EventEscapingRepair;
use Osec\Bootstrap\App;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Repairs event and feed fields stored with HTML entities.
 */
class RepairEscapingCommand extends \WP_CLI_Command
{
    use ConfirmsAll;

    private App $app;

    public function __construct(?App $app = null)
    {
        global $osec_app;
        $this->app = $app ?? $osec_app;
    }

    /**
     * Decodes event and feed fields that earlier versions stored escaped.
     *
     * Up to 1.1.14 saving an event stored `D&amp;D` for `D&D` in venue, address,
     * cost, ticket URL and contact fields; editing a feed did the same to its URL.
     * Saving an event or feed now repairs it; this repairs all of them at once.
     *
     * A value typed as the text `&amp;` on purpose loses one level too, so check
     * the --dry-run list first.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : List the fields that would change, without writing.
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
     *     $ wp osec repair-escaping --dry-run
     *
     *     # Repair.
     *     $ wp osec repair-escaping --yes
     *
     *     # On every site of a multisite network.
     *     $ wp site list --field=url | xargs -I{} wp --url={} osec repair-escaping --yes
     *
     * @when after_wp_load
     */
    public function __invoke($args, $assoc_args)
    {
        $this->accept_yes_alias($args, $assoc_args);
        $dry_run = (bool)Utils\get_flag_value($assoc_args, 'dry-run', false);
        $format  = Utils\get_flag_value($assoc_args, 'format', 'table');

        $repair  = EventEscapingRepair::factory($this->app);
        $changes = $repair->find_affected();

        if ($dry_run) {
            Utils\format_items($format, $changes, ['table', 'id', 'column', 'before', 'after']);

            return;
        }
        if (! $changes) {
            WP_CLI::success('Nothing to repair.');

            return;
        }
        $this->confirm_all('Repair ' . count($changes) . ' fields?', $assoc_args);
        $rows = $repair->repair($changes);
        EscapingRepairController::factory($this->app)->clear_notice();
        WP_CLI::success(sprintf('Repaired %d fields in %d rows.', count($changes), $rows));
    }
}
