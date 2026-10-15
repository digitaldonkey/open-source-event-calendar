<?php

namespace Osec\Cache;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Concrete class for DB caching strategy.
 *
 * @since        2.0
 * @replaces Ai1ec_Cache_Strategy_Db
 * @author       Time.ly Network, Inc.
 */
class CacheDb extends OsecBaseClass implements CacheInterface
{
    public const OPTION_PREFIX = 'osec_cache_';

    /**
     * DB Cache is WP-options API.
     */
    public static function is_available(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, mixed $value): bool
    {
        if (false === get_option($this->prefixed_key($key), false)) {
            return $this->set($key, $value);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key  = $this->prefixed_key($key);
        $data = get_option($key, false);
        if (false === $data) {
            throw new CacheNotSetException(
                'No data under `' . esc_html($key) . '` present'
            );
        }

        return maybe_unserialize($data);
    }

    /**
     * _key method
     *
     * Get safe key name to use within options API
     *
     * @param  string  $key  Key to sanitize
     *
     * @return string Safe to use key
     */
    protected function prefixed_key($key)
    {
        if (strlen($key) > 42) {
            $hash = substr(md5($key), 0, 8);
            $key  = substr($key, 0, 32) . '_' . $hash;
        }

        return self::OPTION_PREFIX . $key;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): bool
    {
        // Not autoloaded: the value is only read on the CSS route, not on every page (D5). Written directly, as
        // Options::set() keeps the autoload of an existing option.
        $name  = $this->prefixed_key($key);
        $value = maybe_serialize($value);
        if (false === get_option($name, false)) {
            $result = add_option($name, $value, '', false);
        } else {
            $result = get_option($name) === $value || update_option($name, $value, false);
        }
        if ( ! $result) {
            throw new CacheWriteException(
                'An error occured while saving data to `' . esc_html($key) . '`'
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear_cache(): bool
    {
        return (bool)$this->delete_matching(self::OPTION_PREFIX);
    }

    /**
     * @inheritDoc
     */
    public function delete_matching(string $pattern): int
    {
        $db = $this->app->db;
        $keys = $db->get_col(
            $db->prepare(
                'SELECT option_name FROM ' . $db->get_table_name('options') .
                ' WHERE option_name LIKE %s',
                '%%' . $pattern . '%%'
            )
        );
        foreach ($keys as $key) {
            delete_option($key);
        }
        return count($keys);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        delete_option($this->prefixed_key($key));

        return false === get_option($this->prefixed_key($key), false);
    }
}
