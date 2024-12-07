<?php

namespace Unit\Cache;

use Osec\Cache\CacheFactory;
use Osec\Tests\Unit\Cache\CacheFileTestBase;

/**
 * @group cacheFactory
 * Sample test case.
 */
class CacheFactoryTest extends CacheFileTestBase
{

    public function test_cache_factory_basic()
    {
        global $osec_app;
        $cache = CacheFactory::factory($osec_app)->createCache('css');
        $this->assertEquals('CacheApcu', $cache->get_active_cache());
    }

    public function test_cache_factory_acpu_unavailable()
    {
        global $osec_app;
        // Using $override to disable ACPU forcefully.
        $cache = CacheFactory::factory($osec_app)
                    ->createCache('css', 'DISABLE ACPU');
        $this->assertEquals('CacheFile', $cache->get_active_cache());
    }

    public function test_cache_factory_file_and_acpu_unavailable()
    {
        global $osec_app;

        $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);
        $this->makeDirReadonly($this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR);
        // Using $override to disable ACPU forcefully.
        $cache = CacheFactory::factory($osec_app)
                             ->createCache('css', 'DISABLE ACPU');
        $this->assertEquals('CacheDb', $cache->get_active_cache());
    }

}
