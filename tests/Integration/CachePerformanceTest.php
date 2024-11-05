<?php

namespace Osec\Tests\integration;

use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheFile;
use Osec\Cache\CacheMemory;
use Osec\Tests\Unit\Cache\CacheFileTestBase;

/**
 * Sample test case.
 */
class CachePerformanceTest extends CacheFileTestBase
{

    public const REPEAT_COUNT = 1000;

    public function test_apcu_with_timer()
    {
        global $osec_app;
        if ( ! CacheApcu::is_available()) {
            $this->markTestSkipped('APCU not available');
        }

        $key = 'ABCDEF';
        $DATA = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 100)), 0, 100);

        $repeats = [];
        for ($i = 0; $i < self::REPEAT_COUNT; $i++) {
            $repeats[ $key.$i ] = $DATA;
        }

        $start = $this->performance_report_start();

        $engine = CacheApcu::factory($osec_app);
        foreach ($repeats as $k => $v) {
            $engine->set($k, $v);
        }
        foreach ($repeats as $k => $v) {
            $a = $engine->get($k);
        }
        $time_elapsed_secs = round(
            microtime(true) - $start,
            4
        );
        $this->assertEquals($repeats[ $key.'0' ], $engine->get($key.'0'));
        $this->performance_report_print($start);
    }

    /**
     * Helps to
     * @return float
     */
    private function performance_report_start() : float
    {
        return microtime(true);
    }

    /**
     * Prints duration time since $start.
     *
     * @param  float  $start
     *
     * @return void
     * @see performance_report_start()
     *
     */
    private function performance_report_print(float $start) : void
    {
        $time_elapsed_secs = round(
            microtime(true) - $start,
            4
        );
        echo "   TIME: ".$time_elapsed_secs."s (Repeats: ".self::REPEAT_COUNT.")\n";
    }

    public function test_cache_db_with_timer()
    {
        global $osec_app;

        // DB uses WP Options autoload.Always available ;).
        $this->assertTrue(CacheDb::is_available());

        $key = 'ABCDEF';
        $DATA = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 100)), 0, 100);

        $repeats = [];
        for ($i = 0; $i < self::REPEAT_COUNT; $i++) {
            $repeats[ $key.$i ] = $DATA;
        }

        $start = $this->performance_report_start();

        $engine = CacheDb::factory($osec_app);
        foreach ($repeats as $k => $v) {
            $engine->set($k, $v);
        }
        foreach ($repeats as $k => $v) {
            $a = $engine->get($k);
        }
        $time_elapsed_secs = round(
            microtime(true) - $start,
            4
        );
        $this->assertEquals($repeats[ $key.'0' ], $engine->get($key.'0'));
        $this->performance_report_print($start);
    }

    public function test_cache_memory_with_timer()
    {
        global $osec_app;

        // DB uses WP Options autoload.Always available ;).
        $this->assertTrue(CacheMemory::is_available());

        $key = 'ABCDEF';
        $DATA = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 100)), 0, 100);

        $repeats = [];
        for ($i = 0; $i < self::REPEAT_COUNT; $i++) {
            $repeats[ $key.$i ] = $DATA;
        }

        $start = $this->performance_report_start();

        // Some memory caches are assigned in Bootstrap. Original Limit was 50.
        $engine = CacheMemory::factory($osec_app);
        if ($engine->limit < self::REPEAT_COUNT) {
            $engine->limit = self::REPEAT_COUNT + 100;
        }

        foreach ($repeats as $k => $v) {
            $engine->set($k, $v);
        }
        foreach ($repeats as $k => $v) {
            $a = $engine->get($k);
        }
        $time_elapsed_secs = round(
            microtime(true) - $start,
            4
        );
        $this->assertEquals($repeats[ $key.'0' ], $engine->get($key.'0'));
        $this->performance_report_print($start);
    }

    public function test_filecache_with_timer()
    {
        global $osec_app;
        $key = 'test_filecache';
        $DATA = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 100)), 0, 100);

        $repeats = [];
        for ($i = 0; $i < self::REPEAT_COUNT; $i++) {
            $repeats[ $key.$i ] = $DATA;
        }

        $start = $this->performance_report_start();

        $fileCache = CacheFile::createFileCacheInstance($osec_app, $key);
        $this->deleteAtTeardown($fileCache->getCachePath());

        foreach ($repeats as $k => $v) {
            $fileCache->set($k, $v);
        }
        foreach ($repeats as $k => $v) {
            $a = $fileCache->get($k);
        }

        $this->assertEquals($repeats[ $key.'0' ], $fileCache->get($key.'0'));
        $this->performance_report_print($start);
//    echo "   TIME: " . $time_elapsed_secs ."s (Repeats: " . self::REPEAT_COUNT ."\n";
    }
}
