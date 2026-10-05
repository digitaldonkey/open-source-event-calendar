<?php

namespace Osec\Tests\Integration;

use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheFile;
use Osec\Cache\CacheInterface;
use Osec\Tests\Unit\Cache\CacheFileTestBase;

/**
 * The compiled-CSS workload per cache engine: one stylesheet-sized value written once, then read.
 *
 * Times are printed for comparison, not asserted. A page with a file-cached stylesheet reads nothing in PHP
 * (the web server sends the file); the other engines are read by PHP on every `?osec-css-cache=` request.
 * The DB read comes from the options already loaded in this request, as on a page after the first read.
 *
 * @group cache
 */
class CachePerformanceTest extends CacheFileTestBase
{
    /** Size of the compiled vortex CSS, ~390 KB. */
    private const CSS_BYTES = 400000;

    private const READS = 20;

    private string $css;

    public function set_up()
    {
        parent::set_up();
        // A unique head, so no engine can answer from a previous run.
        $this->css = '/* ' . wp_generate_password(12, false) . ' */'
            . str_repeat('.timely a{color:#333}', intdiv(self::CSS_BYTES, 21));
    }

    public function test_apcu()
    {
        global $osec_app;

        if ( ! CacheApcu::is_available()) {
            // Without APCu the CSS must not end up in an engine that is not there.
            $this->assertNotSame(
                'CacheApcu',
                CacheFactory::factory($osec_app)->createCache('css')->get_active_cache()
            );

            return;
        }
        $engine = CacheApcu::factory($osec_app);
        $this->measure('APCu', $engine, 'perf_css');
        $engine->delete('perf_css');
    }

    public function test_file()
    {
        global $osec_app;

        $engine = CacheFile::createFileCacheInstance($osec_app, 'perf_css');
        $this->deleteAtTeardown($engine->getCachePath());
        $this->measure('File', $engine, 'perf.css');
    }

    public function test_db()
    {
        global $osec_app;

        $this->measure('DB', CacheDb::factory($osec_app), 'perf_css');
    }

    private function measure(string $label, CacheInterface $engine, string $key): void
    {
        $start = microtime(true);
        $engine->set($key, $this->css);
        $write = microtime(true) - $start;

        $start = microtime(true);
        for ($i = 0; $i < self::READS; $i++) {
            $read = $engine->get($key);
        }
        $per_read = (microtime(true) - $start) / self::READS;

        $this->assertSame($this->css, $read);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo sprintf(
            "   %-4s %d KB: write %.2f ms, read %.3f ms (avg of %d)\n",
            $label,
            intdiv(strlen($this->css), 1000),
            $write * 1000,
            $per_read * 1000,
            self::READS
        );
    }
}
