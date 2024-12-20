<?php

namespace Osec\App\Model;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheMemory;
use Osec\Exception\BootstrapException;

/**
 * Options management class.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package App
 * @replaces Ai1ec_Option
 */
class Options extends OsecBaseClass
{
    /**
     * @var CacheMemory In-memory cache storage engine for fast access.
     */
    protected ?CacheMemory $cache;

    /**
     * Add cache instance to object scope.
     *
     * @param  App  $app  App object.
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->cache = CacheMemory::factory($app);
    }

    /**
     * Create an option if it does not exist.
     *
     * @param  string  $name  Key to put value under.
     * @param  mixed  $value  Value to put to storage.
     * @param  bool  $autoload  Set to true to load on start.
     *
     * @return bool Success.
     */
    public function add($name, mixed $value, $autoload = false)
    {
        $autoload = $this->parseAutoload($autoload);
        if ( ! add_option($name, $value, '', $autoload)) {
            return false;
        }
        $this->cache->set($name, $value);

        return true;
    }

    /**
     * Convert autoload flag input to value recognized by WordPress.
     *
     * @param  bool  $input  Autoload flag value.
     *
     * @return string Autoload identifier.
     */
    protected function parseAutoload($input)
    {
        return $input ? 'yes' : 'no';
    }

    /**
     * Create an option if it does not exist, or update existing.
     *
     * @param  string  $name  Key to put value under.
     * @param  mixed  $value  Value to put to storage.
     * @param  bool  $autoload  Set to true to load on start.
     *
     * @return bool Success.
     */
    public function set($name, mixed $value, $autoload = true)
    {
        $comparator = "\0t\0";
        if ($this->get($name, $comparator) === $comparator) {
            return $this->add($name, $value, $autoload);
        }
        if ( ! update_option($name, $value)) {
            return false;
        }
        $this->cache->set($name, $value);

        return true;
    }

    /**
     * Get a value from storage.
     *
     * @param  string  $name  Key to retrieve.
     * @param  mixed  $default  Value to return if key was not set previously.
     *
     * @return mixed Value from storage or {$default}.
     */
    public function get($name, mixed $default = null)
    {
        $value = $this->cache->get($name, $default);
        if ($default === $value) {
            $value = get_option($name, $default);
            $this->cache->set($name, $value);
        }

        return $value;
    }

    /**
     * Delete value from storage.
     *
     * @param  string  $name  Key to delete.
     *
     * @wp_hook deleted_option Fire after deletion.
     *
     * @return bool Success.
     */
    public function delete($name)
    {
        $this->cache->delete($name);
        if ('deleted_option' === current_filter()) {
            return true; // avoid loops
        }

        return delete_option($name);
    }
}
