<?php

namespace Osec\Cache;

use Exception;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * File cache: one folder, a key is the file name.
 *
 * Files are written with WP_Filesystem_Direct (as core writes uploads: no credentials, no file owner test) to a
 * temporary file next to the target and then renamed over it, so a request never reads a half-written file.
 *
 * @since        2.0
 * @replaces Ai1ec_Cache_Strategy_File
 * @author       Time.ly Network, Inc.
 */
class CacheFile extends OsecBaseClass implements CacheInterface
{
    /**
     * Stored in the setting `twig_cache` when no folder is writable.
     */
    public const OSEC_FILE_CACHE_UNAVAILABLE = 'OSEC_FILE_CACHE_UNAVAILABLE';

    /**
     * Prefix of the 1.1.x options which indexed the cache files. Only removed on upgrade.
     */
    public const LEGACY_OPTION_PREFIX = 'osec_file_cache__';

    private function __construct(App $app, private string $_cache_path, private string $_root)
    {
        parent::__construct($app);
    }

    public static function is_available(): bool
    {
        global $osec_app;

        return (bool) CachePath::factory($osec_app)->get_dir();
    }

    /**
     * Creates a file cache in the first writable folder.
     *
     * @param  App  $app
     * @param  string|null  $cache_id  A valid (ascii) directory name, e.g. 'css'.
     *
     * @return CacheFile|null Null if no folder is writable.
     * @throws Exception
     */
    public static function createFileCacheInstance(App $app, ?string $cache_id = null): ?CacheFile
    {
        if ($cache_id && str_starts_with($cache_id, '/')) {
            throw new Exception('a cache identifier must be provided. It will define a directory in cachePath');
        }
        $dir = CachePath::factory($app)->get_dir((string) $cache_id);

        return $dir ? new self($app, $dir['dir'], $dir['root']) : null;
    }

    /**
     * A file cache in a known folder, e.g. the one a page reads the compiled CSS from.
     */
    public static function for_dir(App $app, string $dir, string $root): CacheFile
    {
        return new self($app, trailingslashit($dir), $root);
    }

    /**
     * Absolute path to directory of this file cache instance
     *
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->_cache_path;
    }

    /**
     * @return string CachePath::ROOT_OVERRIDE or CachePath::ROOT_UPLOADS.
     */
    public function get_root(): string
    {
        return $this->_root;
    }

    /**
     * Insert or replace.
     *
     * @throws CacheWriteException
     */
    public function set(string $key, mixed $value): bool
    {
        $file = $this->path($key);
        $temp = $file . '.' . wp_generate_password(8, false) . '.tmp';
        $fs   = CachePath::filesystem();
        if ( ! $fs->put_contents($temp, maybe_serialize($value), self::file_mode())) {
            throw new CacheWriteException(esc_html($file));
        }
        // Atomic on one filesystem. WP_Filesystem_Direct::move() deletes the target first, leaving a moment
        // without a file for a request to read.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
        if ( ! rename($temp, $file)) {
            $fs->delete($temp);
            throw new CacheWriteException(esc_html($file));
        }

        return true;
    }

    public function add(string $key, mixed $value): bool
    {
        return ! file_exists($this->path($key)) && $this->set($key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);
        if ( ! file_exists($file)) {
            if ($default) {
                return $default;
            }
            throw new CacheNotSetException(esc_html($file) . ' does not exist');
        }

        return maybe_unserialize(CachePath::filesystem()->get_contents($file));
    }

    public function delete(string $key): bool
    {
        $file = $this->path($key);
        if (file_exists($file)) {
            wp_delete_file($file);
        }

        return ! file_exists($file);
    }

    public function delete_matching(string $pattern): int
    {
        $count = 0;
        foreach (glob($this->_cache_path . '*') ?: [] as $file) {
            if (is_file($file) && str_contains(basename($file), $pattern)) {
                wp_delete_file($file);
                $count += (int) ! file_exists($file);
            }
        }

        return $count;
    }

    public function clear_cache(): bool
    {
        return CachePath::clean_and_check_dir($this->_cache_path);
    }

    /**
     * @throws Exception On a key that is not a plain file name.
     */
    private function path(string $key): string
    {
        if ('' === $key || $key !== sanitize_file_name($key)) {
            throw new Exception('Cache key must be a plain file name.');
        }

        return $this->_cache_path . $key;
    }

    /**
     * FS_CHMOD_FILE, which core only defines in WP_Filesystem(); the same default otherwise.
     */
    private static function file_mode(): int
    {
        return defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : (fileperms(ABSPATH . 'index.php') & 0777 | 0644);
    }
}
