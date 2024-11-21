<?php

namespace Osec\Tests\Unit\Cache;

use Osec\Cache\CacheApcu;

/**
 * Sample test case.
 */
class CacheApcuTest extends CacheFileTestBase
{

    //var_dump($osec_app);
    public function test_apcu_available()
    {
        $this->assertTrue(
            CacheApcu::is_available(),
            'You may need to add "apc.enable_cli=1" to your php.ini or skip .... apcu tests'
        );
    }

    public function test_apcu_cache_file_write_and_read()
    {
        global $osec_app;
        $value = 'Curabitur blandit tempus porttitor.';
        if ( ! CacheApcu::is_available()) {
            $this->markTestSkipped('APCU not available');
        }
        $cache = CacheApcu::factory($osec_app);
        $this->assertTrue($cache->set('test_key', $value));
        $this->assertEquals($value, $cache->get('test_key'));
    }
}
