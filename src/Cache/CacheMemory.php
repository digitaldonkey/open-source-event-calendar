<?php

namespace Osec\Cache;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * In-memory cache storage engine.
 *
 * Store values in memory, for use in a single session scope.
 *
 * @since        2.0
 * @replaces Ai1ec_Cache_Memory
 * @author       Time.ly Network, Inc.
 */
final class CacheMemory extends OsecBaseClass implements CacheInterface
{
    /**
     * @var int Number of entries to hold in map.
     */
    public int $limit = 0;
    /**
     * @var array Map of memory entries.
     */
    protected array $cacheData = [];

    /**
     * Constructor initiates stack (memory) length.
     *
     * @param  int  $limit  Number of entries specific to this location.
     *
     * @return void Constructor does not return.
     */
    public function __construct(App $app, int $limit = 100)
    {
        parent::__construct($app);
        $limit = (int)$limit;
        if ($limit < 10) {
            $limit = 10;
        }
        $this->limit = $limit;
    }

    public static function is_available(): bool
    {
        return true;
    }

    /**
     * Add data to memory under given key, if it does not exist.
     *
     * @param  string  $key  Key under which value must be added.
     * @param  mixed  $value  Value to associate with given key.
     *
     * @return bool Success.
     */
    public function add($key, $value): bool
    {
        if (isset($this->cacheData[$key])) {
            return false;
        }

        return $this->set($key, $value);
    }

    /**
     * Write data to memory under given key.
     *
     * @param  string  $key  Key under which value must be written.
     * @param  mixed  $value  Value to associate with given key.
     *
     * @return bool Success.
     */
    public function set($key, $value): bool
    {
        if (count($this->cacheData) > $this->limit) {
            array_shift($this->cacheData); // discard
        }
        $this->cacheData[$key] = $value;

        return true;
    }

    /**
     * Retrieve data from memory, stored under specified key.
     *
     * @param  string  $key  Key under which value is expected to be.
     * @param  mixed  $default  Value to return if nothing is found.
     *
     * @return mixed Found value or {$default}.
     */
    public function get($key, $default = null): mixed
    {
        if ( ! isset($this->cacheData[$key])) {
            return $default;
        }

        return $this->cacheData[$key];
    }

    /**
     * Remove entry from cache table.
     *
     * @param  string  $key  Key to be removed.
     *
     * @return bool Success.
     */
    public function delete($key): bool
    {
        if ( ! isset($this->cacheData[$key])) {
            return false;
        }
        unset($this->cacheData[$key]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear_cache(): bool
    {
        $this->cacheData = [];

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete_matching(string $pattern): int
    {
        $count = 0;
        foreach (array_keys($this->cacheData) as $k) {
            if (str_contains($k, $pattern)) {
                unset($this->cacheData [$k]);
                $count++;
            }
        }

        return $count;
    }
}
