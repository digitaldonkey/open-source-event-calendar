<?php

namespace Osec\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheFile;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Http\Response\RenderJson;
use Osec\Twig\TwigDebugExtension;
use Osec\Twig\TwigExtension;
use Osec\Twig\TwigLoader;
use Twig\Environment;

/**
 * Loads files for admin and frontend.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Theme_Loader
 * @author     Time.ly Network Inc.
 */
class ThemeLoader extends OsecBaseClass
{
    /**
     * @const string Name of option which forces theme clean-up if set to true.
     */
    public const OPTION_FORCE_CLEAN = 'osec_clean_twig_cache';

    /**
     * @const string Prefix for theme arguments filter name.
     */
    public const ARGS_FILTER_PREFIX = 'ai1ec_theme_args_';

    /**
     * @var array contains the admin and theme paths.
     */
    protected array $paths = [
        'admin' => [OSEC_ADMIN_PATH => OSEC_ADMIN_URL],
        'theme' => [],
    ];

    /**
     * @var array Array of Twig environments.
     */
    protected array $twig = [];

    /**
     * @var bool Whether this theme is a child of the default theme
     */
    protected bool $childTheme = false;

    /**
     * @var bool Whether this theme is a core theme
     */
    protected bool $coreTheme = false;

    protected ?CacheFile $fileCache = null;

    /**
     *
     * @param $app App
     *         The registry Object.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->init_themes();
        $this->fileCache = CacheFile::createFileCacheInstance($app, 'twig');
    }

    private function init_themes(): void
    {
        $theme = $this->app->options->get('osec_current_theme');
        // Find out if this is a core theme.
        $core_themes     = explode(',', OSEC_CORE_THEMES);
        $this->coreTheme = in_array($theme['stylesheet'], $core_themes);

        // Default theme's path is always the last in the list of paths to check,
        // so add it first (path list is a stack).
        $this->add_path_theme(
            OSEC_DEFAULT_THEME_PATH . DIRECTORY_SEPARATOR,
            OSEC_THEMES_URL . '/' . OSEC_DEFAULT_THEME_NAME . '/'
        );

        // If using a child theme, set flag and push its path to top of stack.
        if (OSEC_DEFAULT_THEME_NAME !== $theme['stylesheet']) {
            $this->childTheme = true;
            $this->add_path_theme(
                $theme['theme_dir'] . DIRECTORY_SEPARATOR,
                $theme['theme_url'] . '/'
            );
        }
    }

    /**
     * Add theme files search path.
     *
     * @param  string  $path  Path to theme template files.
     * @param  string  $url  URL to the directory represented by $path.
     * @param  string  $is_extension  Whether an extension is adding this path.
     *
     * @return bool Success.
     */
    public function add_path_theme($path, $url, $is_extension = false): bool
    {
        return $this->add_path('theme', $path, $url, $is_extension);
    }

    /**
     * Adds file search path to list. If an extension is adding this path, and
     * this is a custom child theme, inserts its path at the second index of the
     * list. Else pushes it onto the top of the stack.
     *
     * @param  string  $target  Name of path purpose, i.e. 'admin' or 'theme'.
     * @param  string  $path  Absolute path to the directory to search.
     * @param  string  $url  URL to the directory represented by $path.
     * @param  string  $is_extension  Whether an extension is adding this page.
     *
     * @return bool Success.
     */
    public function add_path($target, $path, $url, $is_extension = false): bool
    {
        if ( ! isset($this->paths[$target])) {
            // Invalid target.
            return false;
        }

        /**
         * Replace or alter theme path.
         *
         * @param  array  $path_data  Return array [ $path => $url ]
         * @param  string  $target  Shoule be ´theme´ or `admin`
         * @param  bool  $is_extension
         */
        $new = apply_filters('osec_theme_loader_path_alter', [$path => $url], $target, $is_extension);

        if (
            true === $is_extension &&
            true === $this->childTheme &&
            false === $this->coreTheme
        ) {
            // Special case: extract first element into $head and insert $new after.
            $head = array_splice($this->paths[$target], 0, 1);
        } else {
            // Normal case: $new gets pushed to the top of the array.
            $head = [];
        }

        $this->paths[$target] = $head + $new + $this->paths[$target];

        return true;
    }

    public function getCachPath(): string
    {
        return $this->fileCache->getCachePath();
    }

    /**
     * Rescan cache for writable directory.
     *
     *  Helper called by js with
     *    /wp-admin/admin-ajax.php?action=osec_rescan_cache
     *
     * @return void
     */
    public function ajax_clear_cache(): void
    {
        $args['data'] = [
            'state' => (int)(false !== $this->clear_cache()),
        ];
        RenderJson::factory($this->app)->render($args);
    }

    public function clear_cache(): bool
    {
        return ! $this->fileCache || (bool)$this->fileCache->clear_cache();
    }

    /**
     * Runs the filter for the specified filename just once
     *
     * @param  string  $filename
     * @param  bool  $is_admin
     */
    public function apply_filters_to_args(array $args, string $filename, bool $is_admin): array
    {
        return apply_filters(
            self::ARGS_FILTER_PREFIX . $filename,
            $args,
            $is_admin
        );
    }

    /**
     * Extension registration hook to automatically add file paths.
     *
     * NOTICE: extensions are expected to exactly replicate Core directories
     * structure. If different extension is to be developed at some point in
     * time - this will have to be changed.
     *
     * @param  string  $path  Absolute path to extension's directory.
     * @param  string  $url  URL to directory represented by $path.
     *
     * @return self Instance of self for chaining.
     */
    public function register_extension($path, $url): self
    {
        // Add extension's admin path.
        $this->add_path_admin(
            $path . '/public/admin/',
            $url . '/public/admin/'
        );

        // Add extension's theme path(s).
        $theme = $this->app->options->get('osec_current_theme');

        // Default theme's path is always later in the list of paths to check,
        // so add it first (path list is a stack).
        $this->add_path_theme(
            $path . '/public/' . OSEC_THEME_FOLDER . '/' . OSEC_DEFAULT_THEME_NAME . '/',
            $url . '/public/' . OSEC_THEME_FOLDER . '/' . OSEC_DEFAULT_THEME_NAME . '/',
            true
        );

        // If using a core child theme, set flag and push its path to top of stack.
        if (true === $this->childTheme && true === $this->coreTheme) {
            $this->add_path_theme(
                $path . '/public/' . OSEC_THEME_FOLDER . '/' . $theme['stylesheet'] . '/',
                $url . '/public/' . OSEC_THEME_FOLDER . '/' . $theme['stylesheet'] . '/',
                true
            );
        }

        return $this;
    }

    /**
     * Add admin files search path.
     *
     * @param  string  $path  Path to admin template files.
     * @param  string  $url  URL to the directory represented by $path.
     *
     * @return bool Success.
     */
    public function add_path_admin($path, $url): bool
    {
        return $this->add_path('admin', $path, $url);
    }

    /**
     * Get the requested file from the filesystem.
     *
     * Get the requested file from the filesystem. The file is already parsed.
     *
     * @param  string  $filename  Name of file to load.
     * @param  array  $args  Map of variables to use in file.
     * @param  bool  $is_admin  Set to true for admin-side views.
     * @param  bool  $throw_exception  Set to true to throw exceptions on error.
     * @param  array|null  $paths  For PHP & Twig files only: list of paths to use
     *  instead of default.
     *
     * @return FileAbstract An instance of a file object with content parsed.
     * @throws Exception If File is not found or not possible to handle.
     */
    public function get_file(
        $filename,
        $args = [],
        $is_admin = false,
        $throw_exception = true,
        array $paths = null
    ): FileAbstract {
        $fileExt      = pathinfo($filename, PATHINFO_EXTENSION);
        $fileBasename = pathinfo($filename, PATHINFO_FILENAME);
        switch ($fileExt) {
            case 'less':
            case 'css':
                $file = new FileLess($this->app, $fileBasename, array_keys($this->paths['theme']));
                break;

            case 'png':
            case 'gif':
            case 'jpg':
                $paths = $is_admin ? $this->paths['admin'] : $this->paths['theme'];
                $file  = new FileImage($this->app, $filename, $paths); // Paths => URLs needed for images
                break;

            case 'php':
                $args = apply_filters(
                    self::ARGS_FILTER_PREFIX . $filename,
                    $args,
                    $is_admin
                );
                if (null === $paths) {
                    $paths = $is_admin ? $this->paths['admin'] : $this->paths['theme'];
                    $paths = array_keys($paths); // Values (URLs) not used for PHP
                }
                $file = new FilePhp($this->app, $filename, $paths, $args);
                break;

            case 'twig':
                $args = apply_filters(self::ARGS_FILTER_PREFIX . $filename, $args, $is_admin);

                if (null === $paths) {
                    $paths = $is_admin ? $this->paths['admin'] : $this->paths['theme'];
                    $paths = array_keys($paths); // Values (URLs) not used for Twig
                }
                $file = new FileTwig($this->app, $filename, $args, $this->getTwigInstance($paths, $is_admin));
                break;

            default:
                throw new Exception(
                    sprintf(
                        /* translators: Missing file extensions */
                        esc_html__(
                            "We couldn't find a suitable loader for filename with extension '%s'",
                            'open-source-event-calendar'
                        ),
                        esc_html($fileExt)
                    )
                );
        }

        // here file is a concrete class otherwise the exception is thrown
        if ( ! $file->process_file() && true === $throw_exception) {
            throw new Exception(
                esc_html('The specified file "' . $filename . '" doesn\'t exist.')
            );
        }

        return $file;
    }

    /**
     * This method whould be in a factory called by the object registry.
     * I leave it here for reference.
     *
     * @param  array  $paths  Array of paths to search
     * @param  bool  $is_admin  whether to use the admin or not admin Twig instance
     *
     * @return Environment
     */
    protected function getTwigInstance(array $paths, $is_admin): Environment
    {
        $instance = $is_admin ? 'admin' : 'front';
        if ( ! isset($this->twig[$instance])) {
            // Set up Twig environment.
            $loader_path = [];

            foreach ($paths as $path) {
                if (is_dir($path . 'twig' . DIRECTORY_SEPARATOR)) {
                    $loader_path[] = $path . 'twig' . DIRECTORY_SEPARATOR;
                }
            }

            $twigLoader = new TwigLoader($loader_path);
            unset($loader_path);

            $cache = $this->get_cache_dir();
            $environment = [
                'cache' => is_string($cache) ? $cache : false,
                'optimizations' => -1,
                // (default to -1 -- all optimizations are enabled; set it to 0 to disable).
                // all
                'auto_reload'   => true,
            ];
            if (OSEC_DEBUG) {
                $environment += ['debug' => true];
            }

            /**
             * Alter Twig environment variables
             *
             * Alter variables beforeinitalisation of
             * Twig\Environment($twigLoader, $environment).
             *
             * @since 1.0
             *
             * @param  array  $environment  Environment settings
             */
            $environment = apply_filters('osec_twig_environment_settings_alter', $environment);

            $twig_environment = new Environment(
                $twigLoader,
                $environment
            );
            $this->twig[$instance] = $twig_environment;

            if (OSEC_DEBUG && apply_filters('osec_twig_add_debug', true)) {
                $this->twig[$instance]->addExtension(new TwigDebugExtension());
            }

            $extension = new TwigExtension();
            $extension->set_registry($this->app);
            $this->twig[$instance]->addExtension($extension);
        }

        return $this->twig[$instance];
    }

    /**
     * Get cache dir for Twig.
     *
     * TODO:
     *   We are not regenerating any cache yet.
     *
     * @param  bool  $rescan  Set to true to force rescan
     *
     * @return ?string Cache directory or false
     */
    public function get_cache_dir(bool $rescan = false): ?string
    {
        $twig_cache = $this->app->settings->get('twig_cache');

        // New Install ?
        if ($twig_cache === '') {
            $rescan = true;
        }
        if (false === $rescan) {
            if (CacheFile::OSEC_FILE_CACHE_UNAVAILABLE === $twig_cache) {
                return null;
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable, WordPress.PHP.NoSilencedErrors
            return @is_writable($twig_cache) ? $twig_cache : null;
        }
        $this->fileCache = CacheFile::createFileCacheInstance($this->app, 'twig');
        if ( ! $this->fileCache) {
            // TODO This doubles up saving disabled cache to DB.
            // It's not a setting it's an option prefixed with Cache.

            $this->app->settings->set('twig_cache', CacheFile::OSEC_FILE_CACHE_UNAVAILABLE);

            return null;
        }
        $this->app->settings->set('twig_cache', $this->fileCache->getCachePath());

        return $this->fileCache->getCachePath();
    }

    /**
     * Reuturns loader paths.
     *
     * @return array Bootstrap paths.
     */
    public function get_paths(): array
    {
        return $this->paths;
    }

    /**
     * Get Twig instance.
     *
     * @param  bool  $is_admin  Set to true for admin views.
     * @param  bool  $refresh  Set to true to get fresh instance.
     *
     * @return Environment Configured Twig instance.
     */
    public function get_twig_instance($is_admin = false, $refresh = false): Environment
    {
        if ($refresh) {
            unset($this->twig);
        }
        $paths = $is_admin ? $this->paths['admin'] : $this->paths['theme'];
        $paths = array_keys($paths); // Values (URLs) not used for Twig

        return $this->getTwigInstance($paths, $is_admin);
    }

    /**
     * After upgrade clean cache if it's not default.
     *
     * @return void Method doesn't return
     */
    public function clean_cache_on_upgrade(): void
    {
        if (apply_filters('osec_clean_cache_on_upgrade', true)) {
            return;
        }
        if ($this->app->options->get(self::OPTION_FORCE_CLEAN, false)) {
            $this->app->options->set(self::OPTION_FORCE_CLEAN, false);
            $this->get_cache_dir(true);
        }
    }

    /**
     * Called during 'after_setup_theme' action. Runs theme's special
     * functions.php file, if present.
     */
    public function execute_theme_functions(): void
    {
        $theme     = $this->app->options->get('osec_current_theme');
        $functions = $theme['theme_dir'] . '/functions.php';

        if (file_exists($functions)) {
            include $functions;
        }
    }


    /**
     * Switches to default Vortex theme.
     *
     * @param  bool  $silent  Whether notify admin or not.
     *
     * @return array Method does not return.
     * @throws BootstrapException
     */
    public function switch_to_vortex($silent = false): array
    {
        $current_theme = $this->get_current_theme();
        if (isset($current_theme['stylesheet']) && 'vortex' === $current_theme['stylesheet']) {
            return $current_theme;
        }
        $root  = OSEC_PATH . 'public/' . OSEC_THEME_FOLDER;
        $theme = [
            'theme_root' => $root,
            'theme_dir'  => $root . DIRECTORY_SEPARATOR . 'vortex',
            'theme_url'  => OSEC_URL . '/public/' . OSEC_THEME_FOLDER . '/vortex',
            'stylesheet' => 'vortex',
        ];
        $this->switch_theme($theme);
        if ( ! $silent) {
            NotificationAdmin::factory($this->app)->store(
                __(
                    'Your calendar theme has been switched to Vortex due to a rendering problem. For more information, 
                        please enable debug mode by adding this line to your WordPress <code>wp-config.php  
                        </code> file:<pre>define( "OSEC_DEBUG", true );</pre>',
                    'open-source-event-calendar'
                ),
                'error',
                0,
                [NotificationAdmin::RCPT_ADMIN],
                true
            );
        }

        return $theme;
    }

    /**
     * Returns current calendar theme.
     *
     * @return array|null
     */
    public function get_current_theme(): ?array
    {
        return $this->app->options->get('osec_current_theme');
    }

    /**
     * Switch to the given calendar theme.
     *
     * @param  array  $theme  The theme's settings array
     * @param  bool  $delete_variables  If true, deletes user variables from DB.
     *                                 Else replaces them with config file.
     */
    public function switch_theme(array $theme, $delete_variables = true): void
    {
        $this->app->options->set(
            'osec_current_theme',
            $theme
        );

        // TODO
        // $delete_variables seems weird. Why we wouldn't??

        // If requested, delete theme variables from DB.;
        if ($delete_variables) {
            $this->app->options->delete(LessController::DB_KEY_FOR_LESS_VARIABLES);
        } else {
            // Else replace them with those loaded from config file.
            $this->app->options->set(
                LessController::DB_KEY_FOR_LESS_VARIABLES,
                LessController::factory($this->app)->get_less_variable_data_from_config_file()
            );
        }

        // Recompile CSS for new theme.
        // TODO Ensure cache is working
        FrontendCssController::factory($this->app)
                             ->invalidate_cache(null, false);
    }
}
