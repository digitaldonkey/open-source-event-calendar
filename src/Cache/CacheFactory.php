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

        if (CacheApcu::is_available() && ! $override) {
            $apcu = new CacheApcu($this->app);
            if (!$apcu) throw new BootstrapException('Constructing CacheApcu returned null');
            return new Cache(
                $cache_id,
                $apcu
            );
        }
        if (true === OSEC_ENABLE_CACHE_FILE && CacheFile::is_available()) {
            $cacheFile = CacheFile::createFileCacheInstance($this->app, $cache_id);
            if (!$cacheFile) throw new BootstrapException('Constructing CacheFile returned null');
            return new Cache(
                $cache_id,
                $cacheFile
            );
        }
        $cacheDb = CacheDb::factory($this->app);
        if (!$cacheDb) throw new BootstrapException('Constructing CacheDb returned null');
        return new Cache(
            $cache_id,
            CacheDb::factory($this->app)
        );
    }
}
