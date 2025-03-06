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
    // TODO
    //   REPLACE + Add test.

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
        if ( ! $this->app->options->get($key)) {
            return $this->set($key, $value);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key  = $this->_key($key);
        $data = $this->app->options->get($key);
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
    protected function _key($key)
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
        $result = $this->app->options->set(
            $this->_key($key),
            maybe_serialize($value),
            true
        );
        if (false === $result) {
            throw new CacheWriteException(
                'An error occured while saving data to `' . esc_html($key) . '`'
            );
        }

        return $result;
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
        $db        = $this->app->db;
        $sql_query = $db->prepare(
            'SELECT option_name FROM ' . $db->get_table_name('options') .
            ' WHERE option_name LIKE %s',
            '%%' . $pattern . '%%'
        );
        $keys      = $db->get_col($sql_query);
        foreach ($keys as $key) {
            $this->app->options->delete($key);
        }

        return count($keys);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->app->options->delete(
            $this->_key($key)
        );
    }
}
