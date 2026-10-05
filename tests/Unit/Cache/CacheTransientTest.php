<?php

namespace Osec\Tests\Unit\Cache;

use Osec\App\Controller\FrontendCssController;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheNotSetException;
use Osec\Cache\CachePath;
use Osec\Cache\CacheTransient;
use Osec\Tests\Utilities\CssEngineTrait;

/**
 * Site transients as CSS engine, only with a persistent object cache (Redis, Memcached): the CSS then lives in that
 * memory, shared by web requests and WP-CLI. Without one a transient is an autoloaded option row, so the DB engine
 * (autoload off) is used instead.
 *
 * @group cache
 */
class CacheTransientTest extends CacheFileTestBase
{
    use CssEngineTrait;

    private bool $ext_object_cache;

    public function set_up()
    {
        parent::set_up();
        $this->ext_object_cache = (bool) wp_using_ext_object_cache();
    }

    public function tear_down()
    {
        $this->reset_css_engine();
        wp_using_ext_object_cache($this->ext_object_cache);
        parent::tear_down();
        $this->commit_css_cleanup();
    }

    public function test_unavailable_without_a_persistent_object_cache()
    {
        wp_using_ext_object_cache(false);

        $this->assertFalse(CacheTransient::is_available());
    }

    public function test_write_read_delete()
    {
        global $osec_app;

        wp_using_ext_object_cache(true);
        $cache = CacheTransient::factory($osec_app);

        $this->assertTrue($cache->set('osec-compiled-1.css', 'body{}'));
        $this->assertTrue($cache->set('osec-compiled-1.css', 'body{}'), 'unchanged value');
        $this->assertSame('body{}', $cache->get('osec-compiled-1.css'));
        $this->assertTrue($cache->delete('osec-compiled-1.css'));

        $this->expectException(CacheNotSetException::class);
        $cache->get('osec-compiled-1.css');
    }

    public function test_factory_prefers_the_object_cache_to_apcu()
    {
        global $osec_app;

        wp_using_ext_object_cache(true);
        $this->refuse_folders();

        $this->assertSame('CacheTransient', CacheFactory::factory($osec_app)->createCache('css')->get_active_cache());
    }

    public function test_file_stays_first()
    {
        global $osec_app;

        wp_using_ext_object_cache(true);

        $this->assertSame('CacheFile', CacheFactory::factory($osec_app)->createCache('css')->get_active_cache());
    }

    public function test_css_state_route_and_clear()
    {
        global $osec_app;

        wp_using_ext_object_cache(true);
        $this->use_css_engine('transient', CacheTransient::factory($osec_app));
        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');

        $this->assertSame('transient', $osec_app->options->get(FrontendCssController::CSS_OPTION)['engine']);
        $this->assertStringContainsString(FrontendCssController::REQUEST_CSS_PARAM . '=', $ctrl->get_css_url());
        $this->assertSame('body{color:red}', (new FrontendCssController($osec_app))->get_compiled_css());

        $ctrl->clear_cache();

        $this->assertNull($this->stored_css());
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
