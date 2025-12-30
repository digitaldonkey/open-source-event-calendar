<?php

namespace Osec\Twig;

use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

/**
 * Wrapper for Twig_Loader_Filesystem
 *
 * @since      2.1
 *
 * @replaces Ai1ec_Twig_Loader_Filesystem
 * @author     Time.ly Network Inc.
 */
class TwigLoader extends FilesystemLoader
{
    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param  string  $name  The name of the template to load
     *
     * @return string The cache key
     *
     * @throws LoaderError
     */
    public function getCacheKey(string $name): string
    {
        // namespace style separators avoid OS colisions.
        $cache_key = str_replace('/', '\\', $this->findTemplate($name));
        // make path relative
        $cache_key = str_replace(
            str_replace('/', '\\', self::get_plugin_dir()),
            '',
            $cache_key
        );

        return $cache_key;
    }

    /**
     * Correct way to get Plugin directory URL [sic].
     *
     * @see https://stackoverflow.com/questions/20780422/wordpress-get-plugin-directory
     *
     * @return string
     */
    private static function get_plugin_dir(): string
    {
        static $plugin_dir = null;
        if ($plugin_dir === null) {
            if (defined('WP_SITEURL')) {
                $base_url = WP_SITEURL;
            } else {
                $base_url = get_option('siteurl');
            }
            $plugin_dir = str_replace($base_url, ABSPATH, plugins_url()) . '/';
        }
        return $plugin_dir;
    }
}
