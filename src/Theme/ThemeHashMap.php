<?php

namespace Osec\Theme;

use FilesystemIterator;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * Miscellaneous file system related functions.
 *
 * @since      2.2
 *
 * @replaces Ai1ec_Filesystem_Misc (partly)
 * @author     Time.ly Network Inc.
 */
class ThemeHashMap extends OsecBaseClass
{

    /**
     * Returns hashmap for current theme.
     *
     * @return mixed|null Hashmap or null if none.
     *
     * @throws BootstrapException
     */
    public function get_current_theme_hashmap() : ?array
    {
        $cur_theme = $this->app->options->get('osec_current_theme');
        $file_location = $cur_theme[ 'theme_dir' ].'/less.sha1.map.php';
        if ( ! file_exists($file_location)) {
            return null;
        }

        return require $file_location;
    }

    /**
     * Builds file hashmap for current theme.
     *
     * @return array Hashmap.
     *
     * @throws BootstrapException
     */
    public function build_current_theme_hashmap() : array
    {
        $paths = ThemeLoader::factory($this->app)->get_paths();

        return $this->build_dirs_hashmap(
            array_keys(
                $paths[ 'theme' ]
            ),
            ['ai1ec_parsed_css.css', 'less.sha1.map.php', 'index.php']
        );
    }

    /**
     * Builds directory hashmap.
     *
     * @param  array|string  $paths  Paths for hashmap generation. It accepts
     *                                 string or array of paths. Elements in
     *                                 hashmaps are not overwritten.
     * @param  array  $exclusions  List of excluded file names.
     *
     * @return array Hashmap.
     */
    public function build_dirs_hashmap($paths, $exclusions = [])
    {
        if ( ! is_array($paths)) {
            $paths = [$paths];
        }
        $hashmap = [];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $hashmap += $this->build_dir_hashmap($path, $exclusions);
            }
        }

        ksort($hashmap);

        return $hashmap;
    }

    /**
     * Builds hashmap for given directory.
     *
     * @param  string  $directory  Directory for hashmap creation.
     * @param  array  $exclusions  List of excluded file names.
     *
     * @return array Hashmap.
     */
    public function build_dir_hashmap($directory, $exclusions = [])
    {
        $directory_iterator = new RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS
        );
        $recursive_iterator = new RecursiveIteratorIterator(
            $directory_iterator
        );
        $files = new RegexIterator(
            $recursive_iterator,
            '/^.+\.(less|css|php)$/i',
            RegexIterator::GET_MATCH
        );
        $hashmap = [];
        foreach ($files as $file) {
            $file_info = new SplFileInfo($file[ 0 ]);
            $file_path = $file_info->getPathname();
            if (in_array($file_info->getFilename(), $exclusions)) {
                continue;
            }
            $key = str_replace(
                [$directory, '/'],
                ['', '\\'],
                $file_path
            );

            $hashmap[ $key ] = [
                'size' => $file_info->getSize(),
                'sha1' => sha1_file($file_path)
            ];
        }
        ksort($hashmap);

        return $hashmap;
    }

    /**
     * Compares files hashmaps. If $src key doesn't exist in $dst, it's just
     * ommited. This is intended for LESS compilation check. Current theme
     * may contain more LESS files than base one, what does not matter as
     * other files should be changed accordingly.
     *
     * @param  array  $src  Source hashmap. Should be computed from current
     *                   theme contents.
     * @param  array  $dst  Base hashmap. Should be taken from less.sha1.map.php
     *                   file.
     *
     * @return bool Comparision result. True if they are equal.
     */
    public function compare_hashmaps(array $src, array $dst) : bool
    {
        foreach ($src as $key => $value) {
            if ( ! isset($dst[ $key ])) {
                continue;
            }
            $dst_value = $dst[ $key ];
            if ($dst_value !== $value) {
                return false;
            }
        }

        return true;
    }

}
