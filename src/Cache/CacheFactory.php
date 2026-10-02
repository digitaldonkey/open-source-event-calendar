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
     * The first available engine: file, site transient (only with a persistent object cache), APCu, database.
     *
     * The file cache comes first because the web server sends a static file without starting PHP; the others are
     * read by PHP on the `?osec-css-cache=` route. An object cache (Redis, Memcached) comes before APCu, because web
     * requests and WP-CLI share it.
     *
     * @param  string  $cache_id  Sub folder of the file cache, e.g. 'css'.
     *
     * @return Cache
     * @throws BootstrapException
     */
    public function createCache(string $cache_id): Cache
    {
        $cacheFile = CacheFile::createFileCacheInstance($this->app, $cache_id);
        if ($cacheFile) {
            return new Cache($cache_id, $cacheFile);
        }
        if (CacheTransient::is_available()) {
            return new Cache($cache_id, CacheTransient::factory($this->app));
        }
        if ($this->is_apcu_available()) {
            return new Cache($cache_id, new CacheApcu($this->app));
        }

        return new Cache($cache_id, CacheDb::factory($this->app));
    }

    protected function is_apcu_available(): bool
    {
        return CacheApcu::is_available();
    }
}
