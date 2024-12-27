<?php

namespace Osec\Theme;

use Exception;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Twig\Environment;

/**
 * Loads files for admin and frontend.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Theme_Compiler
 * @author     Time.ly Network Inc.
 */
class ThemeCompiler extends OsecBaseClass
{
    /**
     * Register filters early on.
     *
     * @param  App  $app  Instance to use.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        add_filter('osec_twig_add_debug', '__return_false');
    }

    /**
     * Perform actual templates (re-)compilation.
     *
     * @return void
     */
    public function generate()
    {
        header('Content-Type: text/plain; charset=utf-8');
        $start = microtime(true);

        if ( ! ThemeLoader::factory($this->app)->clear_cache()) {
            throw new BootstrapException(
                'Failed to clean cache directory'
            );
        }
        foreach ([true, false] as $for_admin) {
            $twig  = ThemeLoader::factory($this->app)->get_twig_instance($for_admin, true);
            $files = $this->get_files($twig);
            $this->compile($twig, $files);
        }
        echo esc_html('Re-compiled in ' . (microtime(true) - $start) . "\n");
        exit(0);
    }

    /**
     * Extract files locatable within provided Twig Environment.
     *
     * @param  Environment  $twig  Instance to check.
     *
     * @return array Map of files => Twig templates.
     */
    public function get_files(Environment $twig)
    {
        try {
            $paths = $twig->getLoader()->getPaths();
            $files = [];
            foreach ($paths as $path) {
                $files += $this->read_files($path, strlen((string)$path) + 1);
            }
        } catch (Exception) {
            $files = [];
        }
        return $files;
    }

    /**
     * Read file system searching for twig files.
     *
     * @param  string  $path  Directory to search in.
     * @param  int  $trim_length  Number of characters to omit for templates.
     *
     * @return array Map of files => Twig templates.
     */
    public function read_files($path, $trim_length)
    {
        $handle = opendir($path);
        $files  = [];
        if (false === $handle) {
            return $files;
        }
        while (false !== ($file = readdir($handle))) {
            if ('.' === $file[0]) {
                continue;
            }
            $new_path = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($new_path)) {
                $files += $this->read_files($new_path, $trim_length);
            } elseif (
                is_file($new_path) &&
                '.twig' === strrchr($new_path, '.')
            ) {
                $files[$new_path] = substr($new_path, $trim_length);
            }
        }
        closedir($handle);

        return $files;
    }

    /**
     * Actually compile templates to cache directory.
     *
     * @param  Environment  $twig  Instance to use for compilation.
     * @param  array  $file_list  Map of files located previously.
     *
     * @return void
     */
    public function compile(Environment $twig, array $file_list)
    {
        foreach ($file_list as $file => $template) {
            $twig->load($template);
            echo esc_html('Compiled: ', $template, ' (', $file, ')', "\n");
        }
    }
}
