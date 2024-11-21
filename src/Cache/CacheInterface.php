<?php

namespace Osec\Cache;

/**
 * Interface for cache engines.
 *
 * @since      2.0
 * @replaces Ai1ec_Cache_Interface
 * @author     Time.ly Network, Inc.
 */
interface CacheInterface
{
    public static function is_available(): bool;

    /**
     * Set entry to cache. Overwrite Existing.
     *
     * @param  string  $key  Key for which value must be stored.
     * @param  mixed  $value  Actual value to store.
     *
     * @return bool Success.
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Add entry to cache if one does not exist.
     *
     * @param  string  $key  Key for which value must be stored.
     * @param  mixed  $value  Actual value to store.
     *
     * @return bool Success or false if $key exists.
     */
    public function add(string $key, mixed $value): bool;

    /**
     * Retrieve value from cache.
     *
     * @param  string  $key  Key for which to retrieve value.
     * @param  mixed  $default  Value to return if none found.
     *
     * @return mixed Previously stored or $default value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Delete value from cache.
     *
     * @param  string  $key  Key for value to remove.
     *
     * @return bool Success.
     */
    public function delete(string $key): bool;

    /**
     * Delete all enries.
     *
     * @return bool
     */
    public function clear_cache(): bool;

    /**
     *
     * @return int Count of deleted matches.
     */
    public function delete_matching(string $pattern): int;
}
