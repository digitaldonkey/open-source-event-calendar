<?php

namespace Osec\Tests\Unit\Cache;

use Osec\Cache\CacheApcu;
use Osec\Cache\CacheNotSetException;

/**
 * CI and the dev container run PHP CLI with apc.enable_cli=1 (phpunit.xml).
 *
 * @group cache
 */
class CacheApcuTest extends CacheFileTestBase
{

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
        $cache = CacheApcu::factory($osec_app);
        $this->assertTrue($cache->set('test_key', $value));
        $this->assertEquals($value, $cache->get('test_key'));
    }

    /**
     * APCu is shared with every other application on the server (C10).
     */
    public function test_clear_cache_removes_only_this_sites_keys()
    {
        global $osec_app;

        $cache = CacheApcu::factory($osec_app);
        $cache->set('osec_test_own', 'mine');
        apcu_store('osec_test_foreign', 'other');

        $this->assertTrue($cache->clear_cache());

        $this->assertTrue($this->missing($cache, 'osec_test_own'));
        $this->assertSame('other', apcu_fetch('osec_test_foreign'));
        apcu_delete('osec_test_foreign');
    }

    /**
     * C16: delete_matching() quoted nothing and decremented with apc_dec(), so it never deleted.
     */
    public function test_delete_matching()
    {
        global $osec_app;

        $cache = CacheApcu::factory($osec_app);
        $cache->set('osec_test_match_a', 1);
        $cache->set('osec_test_match_b', 2);
        $cache->set('osec_test_other', 3);

        $this->assertSame(2, $cache->delete_matching('osec_test_match'));
        $this->assertSame(3, $cache->get('osec_test_other'));
        $cache->delete('osec_test_other');
    }

    /**
     * M2: the key prefix follows the site, also after switching sites in one process.
     */
    public function test_prefix_follows_the_site_url()
    {
        global $osec_app;

        $cache = CacheApcu::factory($osec_app);
        $cache->set('osec_test_site', 'site one');
        add_filter('site_url', [$this, 'other_site_url']);

        $this->assertTrue($this->missing($cache, 'osec_test_site'));

        remove_filter('site_url', [$this, 'other_site_url']);
        $cache->delete('osec_test_site');
    }

    private function missing(CacheApcu $cache, string $key): bool
    {
        try {
            $cache->get($key);

            return false;
        } catch (CacheNotSetException) {
            return true;
        }
    }

    public function other_site_url(): string
    {
        return 'https://other.example.org';
    }
}
