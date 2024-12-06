<?php

namespace Osec\Cache;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;

/**
 * A factory class for caching strategy.
 *
 * @since      2.0
 * @replaces Ai1ec_Factory_Strategy
 * @author     Time.ly Network, Inc.
 */
class CacheFactory extends OsecBaseClass
{
    /**
     * create_cache_strategy_instance method
     *
     * Method to instantiate new cache strategy object
     *
     * @param $cache_id
     * @param  null  $override
     *
     * @return Cache Instance of CacheType
     * @throws BootstrapException
     */
    public function createCache($cache_id, $override = null): Cache
    {
        // TODO ADD SOME CONFIGURABILITY.

        if (OSEC_ENABLE_CACHE_APCU && CacheApcu::is_available() && ! $override) {
            return new Cache(
                $cache_id,
                new CacheApcu($this->app)
            );
        }
        if (true === OSEC_ENABLE_CACHE_FILE && CacheFile::is_available()) {
            return new Cache(
                $cache_id,
                CacheFile::createFileCacheInstance($this->app, $cache_id)
            );
        }
        return new Cache(
            $cache_id,
            CacheDb::factory($this->app)
        );
    }
}
