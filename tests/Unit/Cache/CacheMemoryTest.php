<?php

namespace Osec\Tests;

use Osec\Cache\CacheMemory;
use Osec\Tests\Unit\Cache\CacheFileTestBase;

/**
 * @group cachememory
 * Sample test case.
 */
class CacheMemoryTest extends CacheFileTestBase
{

    public function test_cache_memory_base()
    {
        global $osec_app;

        // DB uses WP Options autoload.Always available ;).
        $this->assertTrue(CacheMemory::is_available());

        $key  = 'ABCDEF';
        $DATA = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 100)), 0, 100);

        // Some memory caches are assigned in Bootstrap. Original Limit was 50.
        $engine = CacheMemory::factory($osec_app);
        $engine->set($key, $DATA);
        $result = $engine->get($key);
        $this->assertEquals($DATA, $result);

        $result_unset = $engine->get('my_very_unknown_key');
        $this->assertEquals(null, $result_unset);
    }


    public function test_cache_memory_exeed_limit()
    {
        global $osec_app;

        // DB uses WP Options autoload.Always available ;).
        $this->assertTrue(CacheMemory::is_available());

        $key  = 'memory__testkey_';
        $DATA = self::cacheElementsProvider(11);

        // Creating a new Cache.
        $engine = new CacheMemory($osec_app, 2);
        // There is a minimum limit at 10
        $this->assertEquals($engine->limit, 10);

        // Fill up to limit.
        for ($i = 0;$i <= 10;$i++) {
            $k = 'cache_element_' . $i;
            $engine->set($DATA[$k]['key'], $DATA[$k]['value']);
            $this->assertEquals($engine->get($DATA[$k]['key']), $DATA[$k]['value']);
        }

        // Adding over limit.
        $this->assertTrue($engine->set($DATA['cache_element_10']['key'], $DATA['cache_element_10']['value']));

        // Still works.
        $this->assertEquals($engine->get($DATA['cache_element_10']['key']), $DATA['cache_element_10']['value']);

        // But first value is droped if Limit Exceeded
        $getOverlLimit = $engine->get($DATA['cache_element_0']['key']);
        $this->assertNull($getOverlLimit);

        $result_unset = $engine->get('my_very_unknown_key');
        $this->assertNull($result_unset);
    }


    public static function cacheElementsProvider(int $elements): array
    {
        $key  = 'memory__testkey_';
        $DATA = [];
        for ($i = 0;$i <= $elements;$i++) {
            $DATA['cache_element_' . $i] = [
                'key'   => $key . $i,
                'value' => 'DATA_' . $i,
            ];
        }

        return $DATA;
    }

}
