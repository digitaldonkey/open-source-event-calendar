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

    /**
     * The CSS is only read on the CSS route, so it must not be loaded with every page (D5).
     */
    public function test_entries_are_not_autoloaded()
    {
        global $osec_app, $wpdb;

        $cache = CacheDb::factory($osec_app);
        $cache->set('osec_test_autoload', 'body{}');
        $cache->set('osec_test_autoload', 'body{color:red}');

        $autoload = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s",
                CacheDb::OPTION_PREFIX . 'osec_test_autoload'
            )
        );
        $this->assertContains($autoload, ['no', 'off']);
        $this->assertSame('body{color:red}', $cache->get('osec_test_autoload'));
    }

    public function test_writing_an_unchanged_value_succeeds()
    {
        global $osec_app;

        $cache = CacheDb::factory($osec_app);

        $this->assertTrue($cache->set('osec_test_unchanged', 'body{}'));
        $this->assertTrue($cache->set('osec_test_unchanged', 'body{}'));
    }
}
