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
        // Namespace style separators avoid OS collisions.
        $cache_key = str_replace('/', '\\', $this->findTemplate($name));
        // make path relative
        $cache_key = str_replace(
            str_replace('/', '\\', OSEC_PATH),
            '',
            $cache_key
        );

        return $cache_key;
    }
}
