<?php

namespace Osec\Tests\Unit\Cache;

use Osec\Cache\CacheApcu;
use Osec\Cache\CacheFactory;
use Osec\Cache\CachePath;

/**
 * Engine order: file, then APCu, then DB (D1). Engines are refused through injected subclasses.
 *
 * @group cacheFactory
 */
class CacheFactoryTest extends CacheFileTestBase
{
    public function tear_down()
    {
        global $osec_app;

        $osec_app->inject_object(CachePath::class, new CachePath($osec_app));
        parent::tear_down();
    }

    public function test_file_cache_first()
    {
        global $osec_app;

        $this->assertSame('CacheFile', CacheFactory::factory($osec_app)->createCache('css')->get_active_cache());
    }

    public function test_apcu_when_no_folder_is_writable()
    {
        global $osec_app;

        $this->assertTrue(CacheApcu::is_available(), 'APCu needs apc.enable_cli=1');
        $this->refuse_folders();

        $this->assertSame('CacheApcu', CacheFactory::factory($osec_app)->createCache('css')->get_active_cache());
    }

    public function test_db_when_no_folder_is_writable_and_apcu_is_off()
    {
        global $osec_app;

        $this->refuse_folders();
        $factory = new class ($osec_app) extends CacheFactory {
            protected function is_apcu_available(): bool
            {
                return false;
            }
        };

        $this->assertSame('CacheDb', $factory->createCache('css')->get_active_cache());
    }

    private function refuse_folders(): void
    {
        global $osec_app;

        $osec_app->inject_object(
            CachePath::class,
            new class ($osec_app) extends CachePath {
                protected function is_writable_dir(string $dir): bool
                {
                    return false;
                }
            }
        );
    }
}
