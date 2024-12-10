<?php

namespace Osec\App\Model;

use Osec\Bootstrap\OsecBaseInitialized;
use Osec\Cache\CacheMemory;

/**
 * Abstract class for meta entry management.
 *
 * Via use of cache allows object-based access to meta entries.
 *
 * @since      2.0
 * @replaces Ai1ec_Meta, Ai1ec_Meta_Post
 * @author     Time.ly Network, Inc.
 */
abstract class MetaAdapterAbstract extends OsecBaseInitialized
{
    /**
     * @var string Name of base object for storage.
     */
    protected string $objectId = '';

    /**
     * @var CacheMemory In-memory cache operator.
     */
    protected ?CacheMemory $cache;

    /**
     * Create new entry if it does not exist and cache provided value.
     *
     * @param  string  $object_id  ID of object to store.
     * @param  string  $key  Key particle for ID to store.
     * @param  mixed  $value  Serializable value to store.
     *
     * @return bool Success.
     */
    final public function add($object_id, $key, mixed $value)
    {
        if ( ! $this->_add($object_id, $key, $value)) {
            return false;
        }
        $this->cache->set($this->_cache_key($object_id, $key), $value);

        return true;
    }

    /**
     * Create new entry if it does not exist.
     *
     * @param  string  $object_id  ID of object to store.
     * @param  string  $key  Key particle for ID to store.
     * @param  mixed  $value  Serializable value to store.
     *
     * @return bool Success.
     */
    protected function _add($object_id, $key, mixed $value)
    {
        $function = 'add_' . $this->objectId . '_meta';

        return $function($object_id, $key, $value, true);
    }

    /**
     * Create or update an entry cache new value.
     *
     * @param  string  $object_id  ID of object to store.
     * @param  string  $key  Key particle for ID to store.
     * @param  mixed  $value  Serializable value to store.
     *
     * @return bool Success.
     */
    final public function set($object_id, $key, mixed $value)
    {
        if ( ! $this->get($object_id, $key)) {
            if ( ! $this->_add($object_id, $key, $value)) {
                return false;
            }
        } elseif ( ! $this->_update($object_id, $key, $value)) {
            return false;
        }
        $this->cache->set($this->_cache_key($object_id, $key), $value);

        return true;
    }

    /**
     * Get object value - from cache or actual store.
     *
     * @param  string  $object_id  ID of object to get.
     * @param  string  $key  Key particle for ID to get.
     * @param  mixed  $default  Value to return if nothing found.
     *
     * @return mixed Value stored or {$default}.
     */
    final public function get($object_id, $key, mixed $default = null)
    {
        $cache_key = $this->_cache_key($object_id, $key);
        $value     = $this->cache->get($cache_key, $default);
        if ($default === $value) {
            $value = $this->getMeta($object_id, $key);
            $this->cache->set($cache_key, $value);
        }

        return $value;
    }

    /**
     * Generate key for use with cache engine.
     *
     * @param  string  $object_id  ID of object.
     * @param  string  $key  Key particle for ID.
     *
     * @return string EventSingleView identifier for given keys.
     */
    protected function _cache_key($object_id, $key)
    {
        static $separator = "\0";

        return $object_id . $separator . $key;
    }

    /**
     * Get object value from actual store.
     *
     * @param  string  $object_id  ID of object to get.
     * @param  string  $key  Key particle for ID to get.
     *
     * @return mixed Value as found.
     */
    protected function getMeta($object_id, $key)
    {
        $function = 'get_' . $this->objectId . '_meta';

        return $function($object_id, $key, true);
    }

    /**
     * Update existing entry.
     *
     * @param  string  $object_id  ID of object to store.
     * @param  string  $key  Key particle for ID to store.
     * @param  mixed  $value  Serializable value to store.
     *
     * @return bool Success.
     */
    protected function _update($object_id, $key, mixed $value)
    {
        $function = 'update_' . $this->objectId . '_meta';

        return $function($object_id, $key, $value);
    }

    /**
     * Update existing entry and cache it's value.
     *
     * @param  string  $object_id  ID of object to store.
     * @param  string  $key  Key particle for ID to store.
     * @param  mixed  $value  Serializable value to store.
     *
     * @return bool Success.
     */
    final public function update($object_id, $key, mixed $value)
    {
        if ( ! $this->_update($object_id, $key, $value)) {
            return false;
        }
        $this->cache->set($this->_cache_key($object_id, $key), $value);

        return true;
    }

    /**
     * Remove object entry based on ID and key.
     *
     * @param  string  $object_id  ID of object to remove.
     * @param  string  $key  Key particle for ID to remove.
     *
     * @return bool Success.
     */
    final public function delete($object_id, $key)
    {
        $this->cache->delete($this->_cache_key($object_id, $key));

        return $this->_delete($object_id, $key);
    }

    /**
     * Remove object entry based on ID and key.
     *
     * @param  string  $object_id  ID of object to remove.
     * @param  string  $key  Key particle for ID to remove.
     *
     * @return bool Success.
     */
    protected function _delete($object_id, $key)
    {
        $function = 'delete_' . $this->objectId . '_meta';

        return $function($object_id, $key);
    }

    /**
     * Initialize instance-specific in-memory cache storage.
     *
     * @return void Method does not return.
     */
    protected function _initialize()
    {
        $class          = static::class;
        $this->objectId = strtolower(substr($class, strlen(__NAMESPACE__ . '\MetaAdapter')));
        $this->cache    = CacheMemory::factory($this->app);
    }
}
