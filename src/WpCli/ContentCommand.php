<?php

namespace Osec\WpCli;

use Osec\Bootstrap\App;
use Osec\Exception\Exception;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Generates test content for reviews, screenshots and regression tests.
 *
 *     wp osec content generate --dataset=<file> [--anchor=<date>] [--set=<set>] [--theme=<name>] [--site-settings]
 *         [--manifest=<file>]
 *
 * The dataset (tests/data/content/dataset.json in the plugin repository, not shipped) is described in
 * tests/data/content/README.md. The logic lives in ContentGenerator.
 */
class ContentCommand extends \WP_CLI_Command
{
    private App $app;

    /**
     * @param  App|null  $app  Defaults to the global $osec_app.
     */
    public function __construct(?App $app = null)
    {
        global $osec_app;
        $this->app = $app ?? $osec_app;
    }

    /**
     * Fills a site with the events, categories, tags, feeds and pages of a dataset file.
     *
     * Replaces what an earlier run created (all of it is marked) and deletes nothing else. Refuses a site with calendar
     * events, categories, tags or feeds it did not create. Sets the calendar page, the calendar theme and the
     * dataset's calendar settings.
     *
     * ## OPTIONS
     *
     * --dataset=<file>
     * : Dataset JSON; images are read from the images/ folder next to it.
     *
     * [--anchor=<date>]
     * : Base week of every dataset date, moved to its Monday 00:00 (site timezone). Default: the week with 15 May of
     * next year. `today` for the current week, or a date such as 2027-05-17.
     *
     * [--set=<set>]
     * : Which events to create; feed events always.
     * ---
     * default: all
     * options:
     *   - all
     *   - showcase
     * ---
     *
     * [--theme=<name>]
     * : Calendar theme instead of the dataset's (see `wp osec theme list`).
     *
     * [--site-settings]
     * : Also set the WordPress options of the dataset: site title, timezone, date and time format, start of week.
     *
     * [--manifest=<file>]
     * : Write the manifest (anchor, timezone, page paths, event IDs, titles and starts by key) to this file instead
     * of printing it.
     *
     * ## EXAMPLES
     *
     *     $ wp osec content generate --dataset=tests/data/content/dataset.json --site-settings
     *     $ wp osec content generate --dataset=tests/data/content/dataset.json --set=showcase --anchor=2027-05-17
     *
     * @when after_wp_load
     */
    public function generate($args, $assoc_args)
    {
        $options = [
            'anchor'        => (string) Utils\get_flag_value($assoc_args, 'anchor', ''),
            'set'           => (string) Utils\get_flag_value($assoc_args, 'set', 'all'),
            'theme'         => (string) Utils\get_flag_value($assoc_args, 'theme', ''),
            'site_settings' => (bool) Utils\get_flag_value($assoc_args, 'site-settings', false),
        ];
        try {
            $manifest = ContentGenerator::from_file(
                $this->app,
                $assoc_args['dataset'],
                $options,
                fn(string $message) => WP_CLI::log($message)
            )->generate();
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
        $json = (string) wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (isset($assoc_args['manifest'])) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI output file.
            if (false === file_put_contents($assoc_args['manifest'], $json . "\n")) {
                WP_CLI::error("Cannot write {$assoc_args['manifest']}.");
            }
            WP_CLI::success("Manifest written to {$assoc_args['manifest']}.");

            return;
        }
        WP_CLI::line($json);
    }
}
