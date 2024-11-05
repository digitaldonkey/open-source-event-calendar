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
    public function createCache($cache_id, $override = null) : Cache
    {

        // TODO ADD SOME CONFIGURABILITY.

        if (OSEC_ENABLE_CACHE_ACPU && CacheApcu::is_available() && ! $override) {
            $engine = new CacheApcu($this->app);
        } else {
            if (
                true === OSEC_ENABLE_CACHE_FILE &&
                null !== CacheFile::is_available()
            ) {
                $engine = CacheFile::createFileCacheInstance($this->app, $cache_id);
            } else {
                $engine = CacheDb::factory($this->app);
            }
        }

        return new Cache($cache_id, $engine);
    }

}
