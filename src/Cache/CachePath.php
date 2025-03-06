<?php

namespace Osec\Cache;

use FilesystemIterator;
use Osec\Exception\Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * A factory class for caching strategy.
 *
 * @since      2.0
 * @replaces Ai1ec_Filesystem_Checker
 * @author     Time.ly Network, Inc.
 */
class CachePath
{
    public const CLEAN_DIR_DEFAULT_PERMISSIONS = 0754;

    public function __construct()
    {
        include_once ABSPATH . 'wp-admin/includes/file.php';
    }

    /**
     * Ensure cache directory pre-conditions.
     *
     * Before compilation starts cache directory must be empty but existing.
     * NOTE: it attempts to preserve `.gitignore` file in cache/ directory.
     *
     * @param  string  $dir  Directory to check.
     *
     * @return bool Validity.
     */
    public static function clean_and_check_dir(string $dir): bool
    {
        if (empty($dir)) {
            throw new Exception('Empty directory.');
        }
        try {
            self::delete_directory_content($dir);

            return chmod($dir, self::CLEAN_DIR_DEFAULT_PERMISSIONS);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Remove directory and all it's contents.
     *
     * @param  string  $dir
     *
     * @return void Success.
     * @throws \Exception
     */
    public static function delete_directory_content(string $dir): void
    {
        if ( ! $dir || $dir === '' || ! is_dir($dir) || ! (realpath($dir) === untrailingslashit($dir))) {
            throw new \Exception('Empty directory, relative or not a directory. Got : ' . esc_html($dir));
        }
        // @see https://stackoverflow.com/a/3352564/308533;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

    public function getCacheData(?string $subDirectory = null): ?array
    {
        $path = $this->getCachePath($subDirectory);
        if ( ! $path) {
            return null;
        }
        $url = str_starts_with($path, $_SERVER['DOCUMENT_ROOT']) ?
            get_bloginfo('wpurl') . substr($path, strlen(untrailingslashit($_SERVER['DOCUMENT_ROOT']))) : null;

        $data = [
            'path' => $path,
            'url'  => $url,
        ];

        return $data;
    }

    /**
     * Get a writable file cache directory
     *
     * @param  string|null  $subDirectory  Cache `namespace`, subdirectory in cache.
     *
     * @return string|null
     */
    public function getCachePath(?string $subDirectory = null): ?string
    {
        $subDirectory = $subDirectory ? trailingslashit($subDirectory) : '';

        if ( ! OSEC_ENABLE_CACHE_FILE) {
            return null;
        }

        // Defaults to defined config
        if ($default_path = $this->_default_cache()) {
            $directory_path = $default_path . $subDirectory;
            if ( ! is_dir($directory_path)) {
                wp_mkdir_p($directory_path);
            }
            if (is_writable($directory_path)) {
                return $directory_path;
            }
        }

        // TODO add a Notice/Info if we are not useing the default cache path.

        // Facllback on wp-content/uploads if possible.
        // Reset the filesystem to defaults.
        WP_Filesystem();

        $wp_upload = wp_upload_dir();
        if ($wp_upload['error']) {
            //  Admit, that we can not use Filecache.
            return null;
        }
        $cachePath = trailingslashit($wp_upload['basedir'])
                     . trailingslashit(OSEC_FILE_CACHE_WP_UPLOAD_DIR)
                     . $subDirectory;

        //    $cacheUrl = trailingslashit($wp_upload['baseurl']) . $cacheDir;
        if ( ! is_dir($cachePath)) {
            wp_mkdir_p($cachePath);
        }
        if (is_writable($cachePath)) {
            return trailingslashit(realpath($cachePath));
        }

        // TODO Disable cache?
        return null;
    }

    /**
     * Get constant defined default cache path.
     *
     * @return string|null Path or Null if not writable.
     */
    private function _default_cache(): ?string
    {
        if (is_dir(OSEC_FILE_CACHE_DEFAULT_PATH)) {
            if (is_writable(OSEC_FILE_CACHE_DEFAULT_PATH)) {
                return OSEC_FILE_CACHE_DEFAULT_PATH;
            }
        } else {
            wp_mkdir_p(OSEC_FILE_CACHE_DEFAULT_PATH);
        }

        return is_writable(OSEC_FILE_CACHE_DEFAULT_PATH) ? trailingslashit(OSEC_FILE_CACHE_DEFAULT_PATH) : null;
    }
}
