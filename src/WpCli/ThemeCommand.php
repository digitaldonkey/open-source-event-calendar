<?php

namespace Osec\WpCli;

use Osec\Bootstrap\App;
use Osec\Exception\InvalidArgumentException;
use Osec\Theme\ThemeFinder;
use Osec\Theme\ThemeLoader;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Lists and activates calendar themes (Calendar → Themes in wp-admin).
 *
 *     wp osec theme list [--fields=<fields>] [--format=<format>]
 *     wp osec theme activate <name>
 *
 * Activating works like the admin Themes page: the saved Theme Options of the previous theme are deleted
 * and the CSS is compiled by the next request that loads WordPress.
 */
class ThemeCommand extends \WP_CLI_Command
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
     * Lists the calendar themes.
     *
     * ## OPTIONS
     *
     * [--fields=<fields>]
     * : Limit the output to specific fields.
     * ---
     * default: name,status,title,version
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
     *   - count
     * ---
     *
     * ## AVAILABLE FIELDS
     *
     * name, status, title, version, path
     *
     * ## EXAMPLES
     *
     *     $ wp osec theme list
     *     $ wp osec theme list --fields=name,path --format=csv
     *
     * @subcommand list
     * @when after_wp_load
     */
    public function list_($args, $assoc_args)
    {
        Utils\format_items(
            Utils\get_flag_value($assoc_args, 'format', 'table'),
            $this->get_rows(),
            Utils\get_flag_value($assoc_args, 'fields')
        );
    }

    /**
     * Activates a calendar theme.
     *
     * Deletes the saved Theme Options, as switching in wp-admin does.
     *
     * ## OPTIONS
     *
     * <name>
     * : Theme name, as `wp osec theme list` shows it.
     *
     * ## EXAMPLES
     *
     *     $ wp osec theme activate plana
     *     Success: Switched to the 'plana' calendar theme.
     *
     * @when after_wp_load
     */
    public function activate($args, $assoc_args)
    {
        $name    = $args[0];
        $current = ThemeLoader::factory($this->app)->get_current_theme();
        if ($name === ($current['stylesheet'] ?? null)) {
            WP_CLI::warning("The '$name' calendar theme is already active.");

            return;
        }
        try {
            ThemeLoader::factory($this->app)->switch_to($name);
        } catch (InvalidArgumentException $e) {
            WP_CLI::error("The '$name' calendar theme could not be found.");
        }
        WP_CLI::success("Switched to the '$name' calendar theme.");
    }

    /**
     * The themes of the admin Themes list (themes with errors left out), sorted by name.
     *
     * @return array[] Rows with name, status (active|inactive), title, version, path.
     */
    protected function get_rows(): array
    {
        $current = ThemeLoader::factory($this->app)->get_current_theme();
        $rows    = [];
        foreach (ThemeFinder::factory($this->app)->filter_themes() as $name => $theme) {
            $rows[] = [
                'name'    => $name,
                'status'  => $name === ($current['stylesheet'] ?? null) ? 'active' : 'inactive',
                'title'   => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'path'    => $theme->get_stylesheet_directory(),
            ];
        }
        usort($rows, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $rows;
    }
}
