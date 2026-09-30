<?php

namespace Osec\Tests\Unit\Cache;

use Osec\Cache\CacheDb;
use Osec\Cache\CacheNotSetException;
use Osec\Tests\Utilities\TestBase;

/**
 * A missing entry must throw, so callers such as the CSS compile on a miss.
 *
 * @group cache
 */
class CacheDbTest extends TestBase
{
    public function test_missing_entry_throws()
    {
        global $osec_app;

        $this->expectException(CacheNotSetException::class);
        CacheDb::factory($osec_app)->get('osec_test_missing');
    }

    public function test_deleted_entry_throws()
    {
        global $osec_app;

        $cache = CacheDb::factory($osec_app);
        $cache->set('osec_test_entry', 'body{}');
        $this->assertSame('body{}', $cache->get('osec_test_entry'));

        $cache->delete('osec_test_entry');
        $this->expectException(CacheNotSetException::class);
        $cache->get('osec_test_entry');
    }
}
