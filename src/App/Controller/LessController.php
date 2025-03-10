<?php

namespace Osec\App\Controller;

use Less_Parser;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Exception\FileNotFoundException;
use Osec\Http\Response\ResponseHelper;
use Osec\Theme\ThemeHashMap;
use Osec\Theme\ThemeLoader;

/**
 * Class that handles Less related functions.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Frontend
 * @replaces Ai1ec_Less_Lessphp
 */
class LessController extends OsecBaseClass
{
    public const DB_KEY_FOR_LESS_VARIABLES = 'osec_less_variables';

    private Less_Parser $lessc;

    private array $files;

    private string $parsed_css;

    private string $default_theme_url;

    /*  @var array $variables Variables used for compilation. */
    private array $variables;

    /**
     * @param  App  $app
     * @param  string  $default_theme_url
     */
    public function __construct(
        App $app,
        string $default_theme_url = OSEC_DEFAULT_THEME_URL
    ) {
        parent::__construct($app);

        // @see https://lesscss.org/usage/#less-options-lint;
        $this->lessc = new Less_Parser(
            [
                'compress'     => ! OSEC_DEBUG,
                'sourceMap'    => OSEC_DEBUG,
                'relativeUrls' => true,
                'math'         => 'always',
                // 'sourceMapBasepath'   =>'/var/www/html',
            ]
        );

        $this->default_theme_url = $this->sanitize_default_theme_url($default_theme_url);
        $this->parsed_css        = '';
        $this->variables         = [];
        $this->files             = ['style.less', 'event.less', 'calendar.less'];
    }

    /**
     * Tries to fix the double url as of AIOEC-882
     *
     * @param  string  $url
     *
     * @return string
     */
    public function sanitize_default_theme_url($url)
    {
        $pos_http  = strrpos($url, 'http://');
        $pos_https = strrpos($url, 'https://');
        // if there are two http
        if (0 !== $pos_http) {
            // cut of the first one
            $url = substr($url, $pos_http);
        } elseif (0 !== $pos_https) {
            $url = substr($url, $pos_https);
        }

        return $url;
    }

    /**
     * Parse all the Less files resolving the dependencies.
     *
     * @param  bool  $compile_core  If set to true, it forces compilation of core
     *  CSS only, suitable for shipping.
     *
     * @return string
     * @throws Exception
     * @throws FileNotFoundException|Exception
     */
    public function parse_less_files(array $variables = null, $compile_core = true): string
    {
        // If no variables are passed, initialize from DB, config file, and
        // extension injections in one call.
        if (empty($variables)) {
            $variables = $this->get_saved_variables(false);
        }
        // convert the variables to key / value
        $variables = $this->convert_less_variables_for_parsing($variables);

        /**
         * Inject additional constants from extensions
         *
         * Alter Less variables after convert the variables to key / value
         *
         * @since 1.0
         *
         * @param  array  $variables  Array of Less variables
         */
        $variables = apply_filters('osec_less_constants', $variables);

        // Use these variables for hashmap purposes.
        $this->variables = $variables;

        // Load the static variables defined in the theme's variables.less file.
        $staticVars = $this->load_static_theme_variables();
        $this->lessc->parseFile(
            $staticVars->get_name(),
            $this->abs_path_to_url($staticVars->get_name())
        );

        /**
         * Allow extensions to add their own LESS files
         *
         * @since 1.0
         *
         * @param  array  $variables  Array of Less variables.
         *
         * @return array
         */
        $this->files   = apply_filters('osec_less_files', $this->files);
        $this->files[] = 'override.less';

        // Find out the active theme URL.
        $theme = $this->app->options->get('osec_current_theme');

        // IMPORT DIRS
        $this->lessc->SetImportDirs(
            [
                /**
                 * Callback - Trying to map dependencies.
                 */
                function ($path) use ($theme) {
                    if (substr($path, 0, 10) === 'bootstrap/') {
                        return [$theme['theme_root'] . '/vortex/less/' . $path, null];
                    }

                    if (file_exists($theme['theme_dir'] . '/less/' . $path)) {
                        return [$theme['theme_dir'] . '/less/' . $path, null];
                    }

                    if (file_exists($theme['theme_root'] . '/vortex/less/' . $path)) {
                        return [$theme['theme_root'] . '/vortex/less/' . $path, null];
                    }

                    return [$theme['theme_dir'] . '/less/' . $path, null];
                },
            ]
        );
        $import_dirs = [];
        foreach ($this->files as $file) {
            $file_to_parse = null;
            try {
                // Get the filename following our fallback convention
                $file_to_parse = ThemeLoader::factory($this->app)
                                            ->get_file($file);
            } catch (Exception $e) {
                // We let child themes override styles of Vortex.
                // So there is no fallback for override and we can continue.
                if ($file !== 'override.less') {
                    throw $e;
                } else {
                    // It's an override, skip it.
                    continue;
                }
            }
            // We prepend the unparsed variables.less file we got earlier.
            // We do this as we do not import that anymore in the less files.
            $this->lessc->parseFile($file_to_parse->get_name(), $this->abs_path_to_url($file_to_parse->get_name()));
        }

        $variables['fontdir']         = '~"' . ResponseHelper::remove_protocols($theme['theme_url']) . '/font"';
        $variables['fontdir_default'] = '~"' . ResponseHelper::remove_protocols($this->default_theme_url) . 'font"';
        $variables['imgdir']          = '~"' . ResponseHelper::remove_protocols($theme['theme_url']) . '/img"';
        $variables['imgdir_default']  = '~"' . ResponseHelper::remove_protocols($this->default_theme_url) . 'img"';
        if (true === $compile_core) {
            $variables['fontdir']         = '~"../font"';
            $variables['fontdir_default'] = '~"../font"';
            $variables['imgdir']          = '~"../img"';
            $variables['imgdir_default']  = '~"../img"';
        }
        try {
            $this->lessc->ModifyVars($variables);
            $this->parsed_css = $this->lessc->getCss();
        } catch (Exception $err) {
            throw $err;
        }

        // Replace font placeholders
        $this->parsed_css = preg_replace_callback(
            '/__BASE64_FONT_([a-zA-Z0-9]+)_(\S+)__/m',
            $this->load_font_base64(...),
            $this->parsed_css
        );

        return $this->parsed_css;
    }

    /**
     * Gets the saved variables from the database, and make sure all variables
     * are set correctly as required by config file and any extensions. Also
     * adds translations of variable descriptions as required at runtime.
     *
     * @param $with_description bool Whether to return variables with translated
     *   descriptions
     *
     * @return array
     */
    public function get_saved_variables($with_description = true): array
    {
        // We don't store description in options table, so find it in current config
        // file. Variables from extensions are already injected during this call.
        $variables_from_config = $this->get_less_variable_data_from_config_file();

        // Fetch current variable settings from options table.
        $variables = $this->app->options->get(
            self::DB_KEY_FOR_LESS_VARIABLES,
            []
        );

        // Generate default variable array from the config file, and union these
        // with any saved variables to make sure all required variables are set.
        $variables += $variables_from_config;

        // Add the description at runtime so that it can be translated.
        foreach ($variables as $name => $attrs) {
            // Also filter out any legacy variables that are no longer found in
            // current config file (exceptions thrown if this is not handled here).
            if ( ! isset($variables_from_config[$name])) {
                unset($variables[$name]);
            } elseif ($with_description && isset($variables_from_config[$name]['description']) ) {
                // If description is requested and is available in config file, use it.
                $variables[$name]['description'] =
                    $variables_from_config[$name]['description'];
            } else {
                unset($variables[$name]['description']);
            }
        }

        return $variables;
    }

    /**
     * Get the theme variables from the theme user_variables.php file; also inject
     * any other variables provided by extensions.
     *
     * @return array
     */
    public function get_less_variable_data_from_config_file(): array
    {
        // Load the file to parse using the theme loader to select the right file.
        $file = ThemeLoader::factory($this->app)
                           ->get_file('less/user_variables.php', [], false);

        // This variables are returned by evaluating the PHP file.
        $variables = $file->get_content();

        /**
         * Add variables after user_variables.php is loaded.
         *
         * Inject variables after loading theme user_variables.php
         *
         * @since 1.0
         *
         * @param  array  $variables  Array of less variables.
         *
         * @return array
         */
        return apply_filters('osec_less_variables', $variables);
    }

    /**
     * Drop extraneous attributes from variable array and convert to simple
     * key-value pairs required by the LESS parser.
     *
     * @return array
     */
    private function convert_less_variables_for_parsing(array $variables)
    {
        $converted_variables = [];
        foreach ($variables as $variable_name => $variable_params) {
            $converted_variables[$variable_name] = $variable_params['value'];
        }

        return $converted_variables;
    }

    /**
     * Different themes need different variables.less files. This uses the theme
     * loader (searches active theme first, then default) to load it unparsed.
     */
    private function load_static_theme_variables()
    {
        return ThemeLoader::factory($this->app)->get_file('variables.less', [], false);
    }

    private function abs_path_to_url($path = '')
    {
        $url = str_replace(
            wp_normalize_path(untrailingslashit(ABSPATH)),
            site_url(),
            wp_normalize_path($path)
        );

        return esc_url_raw($url);
    }

    /**
     * Check LESS variables are stored in the options table; if not, initialize
     * with defaults from config file and extensions.
     */
    public function initialize_less_variables_if_not_set(): void
    {
        $variables = $this->app->options->get(
            self::DB_KEY_FOR_LESS_VARIABLES,
            []
        );

        if (empty($variables)) {
            // Initialize variables with defaults from config file and extensions,
            // omitting descriptions.
            $variables = $this->get_saved_variables(false);

            // Save the new/updated variable array back to the database.
            $this->app->options->set(
                self::DB_KEY_FOR_LESS_VARIABLES,
                $variables
            );
        }
    }

    /**
     * After updating core themes, we also need to update the LESS variables with
     * the new ones as they may have changed. This function assumes that the
     * user_variables.php file in the active theme and/or parent theme has just
     * been updated.
     */
    public function update_less_variables_on_theme_update()
    {
        // Get old variables from the DB.
        $saved_variables = $this->get_saved_variables(false);
        // Get the new variables from file.
        $new_variables = $this->get_less_variable_data_from_config_file();
        foreach ($new_variables as $name => $attributes) {
            // If the variable already exists, keep the old value.
            if (isset($saved_variables[$name])) {
                $new_variables[$name]['value'] = $saved_variables[$name]['value'];
            }
        }
        // Save the new variables to the DB.
        $this->app->options->set(
            self::DB_KEY_FOR_LESS_VARIABLES,
            $new_variables
        );
    }

    /**
     * Returns compilation specific hashmap.
     *
     * @return array Hashmap.
     */
    public function get_less_hashmap()
    {
        foreach ($this->variables as $key => $value) {
            if (str_starts_with($key, 'fontdir_')) {
                unset($this->variables[$key]);
            }
        }
        $hashmap   = ThemeHashMap::factory($this->app)->build_current_theme_hashmap();
        $variables = $this->variables;
        ksort($variables);

        return [
            'variables' => $variables,
            'files'     => $hashmap,
        ];
    }

    /**
     * Returns whether LESS compilation should be performed or not.
     *
     * @param  array|null  $variables  LESS variables.
     *
     * @return bool Result.
     *
     * @throws BootstrapException
     */
    public function is_compilation_needed(?array $variables = [])
    {
        /**
         * Hook to trigger less processing
         *
         * Allows to request theme recompile action.
         * You may also set OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST
         * which forces recompile too.
         *
         * @since 1.0
         *
         * @param  array  $variables  Array of less variables.
         */
        $shouldRecompile = apply_filters('osec_should_recompile_less', false);
        if ($shouldRecompile
            || (defined('OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST')
                && OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST)
        ) {
            return true;
        }
        if (null === $variables) {
            $variables = [];
        }
        $hashMap = ThemeHashMap::factory($this->app);

        $cur_hashmap = $hashMap->get_current_theme_hashmap();
        if (empty($variables)) {
            $variables = $this->get_saved_variables(false);
        }
        $variables = $this->convert_less_variables_for_parsing($variables);

        $variables = $this->cleanUp($variables);
        ksort($variables);

        /**
         * Alter Less variables before hashmap generation.
         *
         * @since 1.0
         *
         * @param  array  $variables  Array of less variables
         */
        $variables = apply_filters('osec_less_constants_pre_hashmap', $variables);
        if (
            null === $cur_hashmap ||
            $variables !== $cur_hashmap['variables']
        ) {
            return true;
        }
        $file_hashmap = $hashMap->build_current_theme_hashmap();

        return ! $hashMap->compare_hashmaps($file_hashmap, $cur_hashmap['files']);
    }

    /**
     * Removes fontdir variables added by add-ons.
     *
     * @param  array  $variables  Input variables array.
     *
     * @return array Modified variables.
     */
    protected function cleanUp(array $variables)
    {
        foreach ($variables as $key => $value) {
            if (str_starts_with($key, 'fontdir_')) {
                unset($variables[$key]);
            }
        }

        return $variables;
    }

    /**
     * Load font as base 64 encoded
     *
     * @param  array  $matches
     *
     * @return string
     */
    private function load_font_base64($matches)
    {
        // Find out the active theme URL.
        $theme = $this->app->options->get('osec_current_theme');

        /**
         * Alter font directories
         *
         * @since 1.0
         *
         * @param  array  $variables  Array of Less variables
         */
        $dirs        = apply_filters(
            'osec_font_dirs',
            [
                'AI1EC' => [
                    $theme['theme_dir'] . DIRECTORY_SEPARATOR . 'font',
                    OSEC_DEFAULT_THEME_PATH . DIRECTORY_SEPARATOR . 'font',
                ],
            ]
        );
        $directories = $dirs[$matches[1]];
        foreach ($directories as $dir) {
            $font_file = $dir . DIRECTORY_SEPARATOR . $matches[2];
            if (file_exists($font_file)) {
                return base64_encode(file_get_contents($font_file));
            }
        }

        return '';
    }
}
