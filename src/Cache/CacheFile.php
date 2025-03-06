<?php

namespace Osec\Cache;

use Exception;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Concrete class for file caching strategy.
 *
 * @since        2.0
 * @replaces Ai1ec_Cache_Strategy_File
 * @author       Time.ly Network, Inc.
 */
class CacheFile extends OsecBaseClass implements CacheInterface
{
    /**
     * @car OSEC_FILE_CACHE_UNAVAILABLE
     *
     * A value identifying that file cache is not available.
     * Used in place of actual path for cache to use.
     */
    public const OSEC_FILE_CACHE_UNAVAILABLE = 'OSEC_FILE_CACHE_UNAVAILABLE';

    public const OPTION_PREFIX = 'osec_file_cache__';

    /**
     * @var string
     */
    private ?string $_cache_path;

    private ?string $_cache_url;

    /**
     * Directory/context in cache dir.
     *
     * @var string
     */
    private ?string $_cache_id;

    private $_cacheData;

    private function __construct(App $app, string $path, ?string $url, ?string $cache_id)
    {
        parent::__construct($app);
        $this->_cache_path = $path;
        $this->_cache_url  = $url;
        $this->_cache_id   = $cache_id ?? 'default';
    }

    public static function is_available(): bool
    {
        return (bool)(new CachePath())->getCachePath();
    }

    /**
     * Absolut path to directory of this file cache instance
     *
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->_cache_path;
    }

    /**
     * Creates a file cache instance
     *
     * @param  App  $app
     * @param  string|null  $cache_id  A valid (asci) directory name string.
     *
     * @return CacheFile|null
     * @throws Exception
     */
    public static function createFileCacheInstance(App $app, ?string $cache_id = null): ?CacheFile
    {
        if ($cache_id && str_starts_with($cache_id, '/')) {
            throw new Exception('a cache identifier must be provided. It will define a directory in cachePath');
        }
        $cacheData = (new CachePath())->getCacheData($cache_id);

        // $cacheData['url'] is optional.
        // the creator needs to deal with not
        // having a public url for the cache.
        if ( ! is_array($cacheData)
            || ! isset($cacheData['path'])
        ) {
            self::setUnavailable($app, $cache_id);

            return null;
        }
        self::setAvailable($app, $cache_id);
        return new self($app, $cacheData['path'], $cacheData['url'], $cache_id);
    }

    /**
     * Setting entire directory unavailable in wp-options.
     *
     * @param  App  $app
     * @param  string|null  $cache_id
     *
     * @return void
     */
    private static function setUnavailable(App $app, ?string $cache_id): void
    {
        $cache_id = ! empty($cache_id) ? $cache_id : 'default_cache';
        $app->options->set(
            self::optionKey($cache_id),
            self::OSEC_FILE_CACHE_UNAVAILABLE,
            true
        );
        // TODO : Maybe add Admin message?
    }

    /**
     * Setting entire directory unavailable in wp-options.
     *
     * @param  App  $app
     * @param  string|null  $cache_id
     *
     * @return void
     */
    private static function setAvailable(App $app, ?string $cache_id): void
    {
        $cache_id = ! empty($cache_id) ? $cache_id : 'default_cache';
        $app->options->delete(
            self::optionKey($cache_id)
        );
    }

    public function set(string $key, mixed $value): bool
    {
        return is_array($this->setWithFileInfo($key, $value));
    }

    /**
     * Insert Replace
     *
     * @inheritDoc
     */
    public function setWithFileInfo(string $key, mixed $value): array
    {
        $fileName = $this->_safe_file_name($key);

        $value  = maybe_serialize($value);
        $result = $this->put_contents(
            $this->_cache_path . $fileName,
            $value
        );

        $oldFile = $this->get_file_name($key);
        if ($result !== false) {
            // Delete old file if on update.
            if ($oldFile && file_exists($this->_cache_path . $oldFile)) {
                wp_delete_file($this->_cache_path . $oldFile);
            } else {
                // Update
                $this->setOption($key, $fileName);
            }
        } else {
            throw new CacheWriteException(
                esc_html(sprintf(
                    /* translators: File name */
                    __( 'An error occured while saving data to: %s', 'open-source-event-calendar'),
                    $this->_cache_path . $fileName
                ))
            );
        }

        return [
            'key'  => $key,
            'path' => $this->_cache_path . $fileName,
            'url'  => $this->_cache_url ?? $this->_cache_url . $fileName,
            'file' => $fileName,
        ];
    }

    /**
     * _safe_file_name method
     *
     * Generate safe file name for any storage case.
     *
     * @param  string  $file  File name currently supplied. Prefixed or not.
     *
     * @return string Sanitized file name
     */
    private function _safe_file_name(string $file)
    {
        if (empty($file) || strpbrk($file, "\\/?%*:|\"<>") !== false) {
            throw new Exception('Filename empty or conatins Illegal chars');
        }
        static $prefix = null;
        if (null === $prefix && ! OSEC_DEBUG) {
            $prefix = substr(md5(site_url()), 0, 8);
        }
        // Make sure Prefix is onöly added if not yet set.
        if (0 !== strncmp($file, (string)$prefix, 8)) {
            $key = $prefix . $file;
        }


        return is_string($prefix) ? $prefix . '_' . $file : $file;
    }

    /**
     * Creates a file using $wp_filesystem.
     *
     * @param  string  $file
     * @param  string  $content
     */
    private function put_contents(string $file, string $content)
    {
        global $wp_filesystem;
        // @see https://wordpress.stackexchange.com/a/372407/15081
        require_once ABSPATH . 'wp-admin/includes/file.php';
        if (WP_Filesystem([], dirname($file))) {
            try {
                return $wp_filesystem->put_contents(
                    $file,
                    $content
                );
            } catch (Exception $e) {
                // fall through.
            }
        }
        // Throw a mean message.

        //  if ($notification->are_notices_available(2)) {}
        $msg = sprintf(
        /* translators: 1: Filename 2: ABSPATH 3: OSEC_FILE_CACHE_WP_UPLOAD_DIR 4: OSEC_FILE_CACHE_DEFAULT_PATH */
            __(
                'Can not use WP_Filesystem() method to write to file: %1$s
                        <br /><br />
                        You may set OSEC_ENABLE_CACHE_FILE false to use other cache methods like APCU or DB and ignore this message.
                        <br /><br /><strong>BUT: If we can not write files Twig cache is disabled.</strong>
                        <br /><br /><strong>Ensure that</strong><br /><code>%2$swp-content/uploads/%3$s
                        </code><br />or<br /><code>%4$</code><br /> are writable by php.',
                'open-source-event-calendar'
            ),
            $file,
            ABSPATH,
            OSEC_FILE_CACHE_WP_UPLOAD_DIR,
            OSEC_FILE_CACHE_DEFAULT_PATH
        );
        NotificationAdmin::factory($this->app)->store(
            "<p>" . wp_kses($msg,$this->app->kses->allowed_html_inline()) . "</p>",
            'error',
            1,
            [NotificationAdmin::RCPT_ADMIN],
            true
        );
        throw new CacheWriteException(esc_html($file));
    }

    /**
     * Tries to get the stored filename
     *
     * @param  string  $key
     *
     * @return string|null
     */
    public function get_file_name(string $key): ?string
    {
        $fileName = $this->getOption($key);
        if ($fileName) {
            return $fileName;
        }

        return false;
    }

    private function getOption($key): mixed
    {
        return $this->app->options->get(
            $this->optionKey($key)
        );
    }

    /**
     *
     * @param  string  $key
     * @param  mixed|null  $default  *
     */
    public function get($key, mixed $default = null): mixed
    {
        $filename = $this->get_file_name($key);
        if ( ! $key || ! file_exists($filename)) {
            if ($default) {
                return $default;
            }
            throw new CacheNotSetException(
                esc_html(
                    sprintf(
                        /* translators: File name */
                        esc_html__('File %s does not exist', 'open-source-event-calendar'),
                        $key
                    )
                )
            );
        }

        return maybe_unserialize(
            file_get_contents($filename)
        );
    }

    public static function optionKey($key, $revert = false): string
    {
        if ($revert) {
            return substr($key, strlen(self::OPTION_PREFIX));
        }

        return self::OPTION_PREFIX . $key;
    }

    /**
     * Handling CacheFile WP-Options.
     *
     * @param $key
     * @param $filename
     *
     * @return bool True on success.
     * @see https://developer.wordpress.org/reference/functions/update_option/
     */
    private function setOption($key, $filename): bool
    {
        return $this->app->options->set(
            $this->optionKey($key),
            $this->_cache_path . $filename,
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function delete_matching(string $pattern): int
    {
        $dirhandle = opendir($this->_cache_path);
        if (false === $dirhandle) {
            return 0;
        }
        $count = 0;
        while (false !== ($entry = readdir($dirhandle))) {
            if ('.' !== $entry[0] && str_contains($entry, $pattern)) {
                if (wp_delete_file($this->_cache_path . $entry)) {
                    ++$count;
                }
            }
        }
        closedir($dirhandle);

        return $count;
    }

    public function clear_cache(): bool
    {
        $cache = (new CachePath())->getCacheData($this->_cache_id);

        if ($cache && CachePath::clean_and_check_dir($cache['path'])) {
            $this->_cache_path = $cache['path'];
            $this->_cache_url  = $cache['url'];
            self::setAvailable($this->app, $this->_cache_id);
            return true;
        }
        self::setUnavailable($this->app, $this->_cache_id);

        return false;
    }

    public function empty_all_caches(): bool
    {
        foreach ($this->get_all_cache_files() as $f) {
            if ( ! $this->delete($this->optionKey($f->name, true))) {
                return false;
            }
        }
        $cacheBasepath = (new CachePath())->getCachePath();

        return CachePath::clean_and_check_dir($cacheBasepath);
    }

    public function get_all_cache_files(): array
    {
        $db        = $this->app->db;
        $sql_query = $db->prepare(
            'SELECT option_name as name, option_value as filename FROM ' . $db->get_table_name('options') .
            ' WHERE option_name LIKE %s',
            '%%' . (string)self::OPTION_PREFIX . '%%'
        );
        $files     = [];
        foreach ($db->get_results($sql_query) as $result) {
            $files[] = $result;
        }

        return $files;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $filename = $this->get_file_name($key);
        if ($filename) {
            $this->app->options->delete($this->optionKey($key));
            if (file_exists($filename)) {
                wp_delete_file($filename);
                return !file_exists($filename);
            }
        }

        return true;
    }

    public function add($key, mixed $value): bool
    {
        if ($this->key_exists($key)) {
            return false;
        } else {
            return $this->set($key, $value);
        }
    }

    private function key_exists($key): bool
    {
        try {
            return (bool)$this->get_file_name($key);
        } catch (CacheNotSetException) {
            return false;
        }
    }
}
