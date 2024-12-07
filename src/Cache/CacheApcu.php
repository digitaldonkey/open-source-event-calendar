<?php

namespace Osec\Cache;

use APCUIterator;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Concrete class for APC caching strategy.
 *
 * @since        2.0
 * @replaces Ai1ec_Cache_Strategy_Apc
 *
 * @author       Time.ly Network, Inc.
 * @see https://www.php.net/manual/de/book.apcu.php
 */
class CacheApcu extends OsecBaseClass implements CacheInterface
{
    /**
     * is_available method
     *
     * Checks if APC is available for use.
     * Following pre-requisites are checked: APC functions availability,
     * APC is enabled via configuration and PHP is not running in CGI.
     *
     * @return bool Availability
     */
    public static function is_available(): bool
    {
        if ( ! OSEC_ENABLE_CACHE_APCU) {
            return false;
        }
        $apcuAvailabe = function_exists('apcu_enabled') && apcu_enabled();
        return $apcuAvailabe;
    }

    /**
     * Cache a variable in the data store.
     * Overwrite if key exists.
     */
    public function set($key, mixed $value): bool
    {
        $dist_key = $this->_key($key);

        return apcu_store($dist_key, $value);
    }

    /**
     * _key method
     *
     * Make sure we are on the safe side - in case of multi-instances
     * environment some prefix is required.
     *
     * @param  string  $key  Key to be used against APC cache
     *
     * @return string Key with prefix prepended
     */
    protected function _key($key)
    {
        static $prefix = null;
        if (null === $prefix) {
            $prefix = substr(md5((string)get_site_url()), 0, 8) . '_';
        }
        if (0 !== strncmp($key, (string)$prefix, 8)) {
            $key = $prefix . $key;
        }

        return $key;
    }

    /**
     * apcu_add — Cache a new variable in the data store.
     * @return False if Key does not exist.
     */
    public function add($key, mixed $value): bool
    {
        $dist_key = $this->_key($key);

        return apcu_add($dist_key, $value);
    }

    /**
     * @inheritDoc
     */
    public function get($key, mixed $default = null): mixed
    {
        $dist_key = $this->_key($key);
        $data     = apcu_fetch($dist_key);
        if (false === $data && $default) {
            return $default;
        }
        if (false === $data) {
            throw new CacheNotSetException("$dist_key not set");
        }

        return $data;
    }

    public function clear_cache(): bool
    {
        return apcu_clear_cache();
    }

    public function delete_matching(string $pattern): int
    {
        $i = 0;
        foreach (new APCUIterator('/$pattern/') as $counter) {
            $this->delete($counter['key']);
            if (apc_dec($counter['key'], $counter['value'])) {
                $i++;
            }
        }

        return $i;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return apcu_delete($this->_key($key));
    }
}
