<?php

namespace Osec\Cache;

/**
 * The context class which handles the caching strategy.
 *
 * @since        2.0
 * @replaces Ai1ec_Persistence_Context
 * @author       Time.ly Network, Inc.
 */
class Cache implements CacheInterface
{
    public readonly CacheInterface $engine;
    private ?string $key_for_persistance;

    /**
     *
     * @param  string  $key_for_persistance
     * @param  CacheInterface  $engine
     */
    public function __construct(string $key_for_persistance, CacheInterface $engine)
    {
        $this->key_for_persistance = $key_for_persistance;
        $this->engine              = $engine;
    }

    public static function is_available(): bool
    {
        return true;
    }

    /**
     * Are we using file cache?
     *
     * @return bool
     */
    public function is_file_cache()
    {
        return $this->engine instanceof CacheFile;
    }

    /**
     * Delete matching entries from persistance.
     *
     * @param  string  $pattern  Expected pattern, to be contained within key
     *
     * @return int Count of entries deleted
     */
    public function delete_matching_entries_from_persistence(string $pattern): int
    {
        return $this->engine->delete_matching($pattern);
    }

    /**
     * @inheritDoc
     */
    public function delete_matching(string $pattern): int
    {
        return $this->engine->delete_matching($pattern);
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, mixed $value): bool
    {
        if ( ! $this->get($key)) {
            return $this->set($key, $value);
        }

        return false;
    }

    /**
     * @param  string  $key
     * @param  mixed|NULL  $default
     *
     * @return mixed
     * @throws CacheNotSetException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $data = $this->engine->get($key);
        } catch (CacheNotSetException $e) {
            throw $e;
        }

        return $data;
    }

    /**
     * Write data to cache. If that fails - false is returned.
     * Exceptions are suspended, as cache write is not a fatal error by no
     * mean, thus shall not be escalated further. If you want exception to
     * be escalated - use lower layer method directly.
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        try {
            return $this->engine->set($key, $value);
        } catch (CacheWriteException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key = null): bool
    {
        if ( ! $key) {
            $key = $this->key_for_persistance;
        }

        return $this->engine->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear_cache(): bool
    {
        return $this->engine->clear_cache();
    }

    public function get_active_cache()
    {
        $path = explode('\\', get_class($this->engine));

        return array_pop($path);
    }
}
