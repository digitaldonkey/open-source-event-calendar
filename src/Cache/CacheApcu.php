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
        $dist_key = $this->prefixed_key($key);

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
    protected function prefixed_key($key)
    {
        // Per call: the site can change within one process (switch_to_blog()).
        $prefix = $this->prefix();
        if ( ! str_starts_with((string) $key, $prefix)) {
            $key = $prefix . $key;
        }

        return $key;
    }

    private function prefix(): string
    {
        return substr(md5((string) get_site_url()), 0, 8) . '_';
    }

    /**
     * apcu_add — Cache a new variable in the data store.
     * @return False if Key does not exist.
     */
    public function add($key, mixed $value): bool
    {
        $dist_key = $this->prefixed_key($key);

        return apcu_add($dist_key, $value);
    }

    /**
     * @inheritDoc
     */
    public function get($key, mixed $default = null): mixed
    {
        $dist_key = $this->prefixed_key($key);
        $data     = apcu_fetch($dist_key);
        if (false === $data && $default) {
            return $default;
        }
        if (false === $data) {
            throw new CacheNotSetException(esc_html($dist_key) . ' not set');
        }

        return $data;
    }

    /**
     * Removes this site's keys only; APCu is shared with every application on the server.
     */
    public function clear_cache(): bool
    {
        $this->delete_matching('');

        return true;
    }

    /**
     * Removes this site's keys containing $pattern (plain text, not a regex).
     */
    public function delete_matching(string $pattern): int
    {
        $regex = '/^' . preg_quote($this->prefix(), '/') . '.*' . preg_quote($pattern, '/') . '/';
        $keys  = [];
        foreach (new APCUIterator($regex, APC_ITER_KEY) as $entry) {
            $keys[] = $entry['key'];
        }

        return count(array_filter($keys, 'apcu_delete'));
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return apcu_delete($this->prefixed_key($key));
    }
}
