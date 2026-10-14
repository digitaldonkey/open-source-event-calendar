<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FrontendCssController;
use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheInterface;
use Osec\Cache\CacheWriteException;
use Osec\Tests\Utilities\CssEngineTrait;
use Osec\Tests\Utilities\TestBase;

/**
 * Compiled CSS state (option osec_css) and the stylesheet link built from it on every page.
 *
 * No theme ships precompiled CSS, so the CSS is always compiled on the site.
 *
 * @group css
 */
class FrontendCssControllerTest extends TestBase
{
    use CssEngineTrait;

    private mixed $saved_as_link;

    private string $document_root;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_as_link = $osec_app->settings->get('render_css_as_link');
        $this->document_root = $_SERVER['DOCUMENT_ROOT'];
        $osec_app->settings->set('render_css_as_link', true);
        $osec_app->options->delete(FrontendCssController::CSS_OPTION);
    }

    public function tear_down()
    {
        global $osec_app;

        $this->reset_css_engine();
        $osec_app->settings->set('render_css_as_link', $this->saved_as_link);
        $_SERVER['DOCUMENT_ROOT'] = $this->document_root;
        $GLOBALS['wp_styles']     = null;
        parent::tear_down();
        $this->commit_css_cleanup();
    }

    public function test_not_compiled_yet_links_the_compiling_route()
    {
        global $osec_app;

        $url = FrontendCssController::factory($osec_app)->get_css_url();

        $this->assertStringNotContainsString('osec_parsed.css', $url);
        $this->assertStringContainsString(FrontendCssController::REQUEST_CSS_PARAM . '=0', $url);
    }

    public function test_invalidate_cache_always_compiles()
    {
        global $osec_app;

        $this->use_css_engine('file');

        $this->assertTrue(FrontendCssController::factory($osec_app)->invalidate_cache(null, true));
        $this->assertIsArray($osec_app->options->get(FrontendCssController::CSS_OPTION));
    }

    public function test_file_engine_state()
    {
        global $osec_app;

        $this->use_css_engine('file');

        FrontendCssController::factory($osec_app)->update_persistence_layer('body{color:red}');

        $state = $osec_app->options->get(FrontendCssController::CSS_OPTION);
        $this->assertIsFloat($state['started']);
        unset($state['started']);
        $this->assertSame(
            [
                'engine' => 'file',
                'root'   => 'override',
                'file'   => 'osec-compiled-' . get_current_blog_id() . '.css',
                'ver'    => substr(md5('body{color:red}'), 0, 7),
            ],
            $state
        );
        $this->assertSame('body{color:red}', $this->stored_css());
    }

    public function test_file_engine_links_the_static_file_without_document_root()
    {
        global $osec_app;

        $this->use_css_engine('file');
        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');
        $_SERVER['DOCUMENT_ROOT'] = '';

        $this->assertSame(
            '//example.org/wp-content/osec-phpunit-cache/css/osec-compiled-' . get_current_blog_id() . '.css',
            $ctrl->get_css_url()
        );
    }

    public function test_missing_file_links_the_route_with_ver()
    {
        global $osec_app;

        $this->use_css_engine('file');
        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');
        $this->css_cache->delete(FrontendCssController::css_file_name());

        $this->assertSame($this->route_url(substr(md5('body{color:red}'), 0, 7)), $ctrl->get_css_url());
    }

    public static function route_engines(): array
    {
        return [
            'APCu' => ['apcu'],
            'DB'   => ['db'],
        ];
    }

    /**
     * @dataProvider route_engines
     */
    public function test_non_file_engines_link_the_route_with_ver(string $engine)
    {
        global $osec_app;

        $this->use_css_engine($engine);
        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');

        $this->assertSame($engine, $osec_app->options->get(FrontendCssController::CSS_OPTION)['engine']);
        $this->assertSame($this->route_url(substr(md5('body{color:red}'), 0, 7)), $ctrl->get_css_url());
    }

    public function test_stylesheet_is_enqueued_with_ver()
    {
        global $osec_app;

        $this->use_css_engine('file');
        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');
        $GLOBALS['wp_styles'] = null;

        $ctrl->add_link_to_html_for_frontend();

        $this->assertSame(substr(md5('body{color:red}'), 0, 7), wp_styles()->registered['ai1ec_style']->ver);
    }

    /**
     * The route carries the hash in its own parameter; a second ver= would only repeat it.
     */
    public function test_route_link_has_no_second_ver()
    {
        global $osec_app;

        $this->use_css_engine('db');
        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');
        $GLOBALS['wp_styles'] = null;

        $ctrl->add_link_to_html_for_frontend();

        $this->assertNull(wp_styles()->registered['ai1ec_style']->ver);
        $this->assertSame($this->route_url(substr(md5('body{color:red}'), 0, 7)), wp_styles()->registered['ai1ec_style']->src);
    }

    public function test_ver_changes_with_the_css_only()
    {
        global $osec_app;

        $this->use_css_engine('file');
        $ctrl = FrontendCssController::factory($osec_app);

        $ctrl->update_persistence_layer('body{color:red}');
        $first = $osec_app->options->get(FrontendCssController::CSS_OPTION)['ver'];
        $ctrl->update_persistence_layer('body{color:red}');
        $same = $osec_app->options->get(FrontendCssController::CSS_OPTION)['ver'];
        $ctrl->update_persistence_layer('body{color:blue}');
        $changed = $osec_app->options->get(FrontendCssController::CSS_OPTION)['ver'];

        $this->assertSame($first, $same);
        $this->assertNotSame($first, $changed);
    }

    /**
     * The route reads the engine named in osec_css, even when the factory would pick another one today.
     */
    public function test_route_reads_the_engine_that_stored_the_css()
    {
        global $osec_app;

        $this->use_css_engine('apcu');
        FrontendCssController::factory($osec_app)->update_persistence_layer('/* stored in APCu */');
        $apcu = $this->css_cache;

        $this->use_css_engine('file');
        $css = FrontendCssController::factory($osec_app)->get_compiled_css();

        $this->assertSame('/* stored in APCu */', $css);
        $apcu->delete(FrontendCssController::css_file_name());
    }

    public function test_write_failure_keeps_the_previous_state()
    {
        global $osec_app;

        $this->use_css_engine('file');
        FrontendCssController::factory($osec_app)->update_persistence_layer('body{color:red}');
        $state = $osec_app->options->get(FrontendCssController::CSS_OPTION);

        $this->use_css_engine('broken', $this->failing_engine());
        try {
            FrontendCssController::factory($osec_app)->update_persistence_layer('body{color:blue}');
            $this->fail('A failed write must throw.');
        } catch (CacheWriteException) {
            $this->assertSame($state, $osec_app->options->get(FrontendCssController::CSS_OPTION));
        }
        $this->assertFalse(FrontendCssController::factory($osec_app)->invalidate_cache(null, true));
        $this->assertSame($state, $osec_app->options->get(FrontendCssController::CSS_OPTION));
    }

    public function test_clear_cache_removes_the_css_of_every_engine_and_legacy_leftovers()
    {
        global $osec_app;

        $name = FrontendCssController::css_file_name();
        $file = $this->use_css_engine('file');
        FrontendCssController::factory($osec_app)->update_persistence_layer('body{}');
        $apcu = new CacheApcu($osec_app);
        $apcu->set($name, 'body{}');
        $db = CacheDb::factory($osec_app);
        $db->set($name, 'body{}');
        apcu_store('osec_test_foreign_key', 'other application');
        $legacy = $this->legacy_state();

        FrontendCssController::factory($osec_app)->clear_cache();

        foreach ([$file, $apcu, $db] as $engine) {
            $this->assertNull($this->get_or_null($engine, $name), get_class($engine));
        }
        $this->assertNull($osec_app->options->get(FrontendCssController::CSS_OPTION));
        $this->assertLegacyRowsReplaced($legacy['options']);
        $this->assertFileDoesNotExist($legacy['file']);
        $this->assertSame('other application', apcu_fetch('osec_test_foreign_key'));
        apcu_delete('osec_test_foreign_key');
    }

    /**
     * A 1.1.x state: the upgrade drops the option rows, but keeps the CSS files that cached pages may still link (H3).
     */
    public function test_upgrade_removes_legacy_rows_and_keeps_legacy_files()
    {
        global $osec_app;

        $legacy = $this->legacy_state();
        set_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT, 1, 30);

        $osec_app->settings->perform_upgrade_actions([]);

        $this->assertFalse(get_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT));
        $this->assertLegacyRowsReplaced($legacy['options']);
        $this->assertFileExists($legacy['file']);
        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        wp_delete_file($legacy['file']);
    }

    /**
     * The 1.1.x rows are gone, except osec_compiled.css: a number there makes a downgraded 1.1.x link its compiling
     * route; without the row it links a precompiled osec_parsed.css that no theme ships (404, unstyled; V12).
     */
    private function assertLegacyRowsReplaced(array $options): void
    {
        foreach ($options as $option) {
            if (FrontendCssController::COMPILED_CSS_KEY === $option) {
                $this->assertTrue(is_numeric(get_option($option)), $option . ' is a number');
                continue;
            }
            $this->assertFalse(get_option($option), $option);
        }
    }

    private function route_url(string $ver): string
    {
        return '//example.org/?' . FrontendCssController::REQUEST_CSS_PARAM . '=' . $ver;
    }

    private function legacy_state(): array
    {
        // The 1.1.x upload cache folder.
        $dir = trailingslashit(wp_upload_dir()['basedir']) . 'open_source_event_calendar_cache/css/';
        wp_mkdir_p($dir);
        $file = $dir . substr(md5(site_url()), 0, 8) . '_osec_compiled.css';
        file_put_contents($file, '/* 1.1.x */');
        $options = [
            FrontendCssController::COMPILED_CSS_KEY            => 'http://example.org/wp-content/uploads/x.css',
            'osec_file_cache__' . FrontendCssController::COMPILED_CSS_KEY => $file,
            'osec_file_cache__twig'                            => 'OSEC_FILE_CACHE_UNAVAILABLE',
            CacheDb::OPTION_PREFIX . FrontendCssController::COMPILED_CSS_KEY => '/* 1.1.x */',
        ];
        foreach ($options as $name => $value) {
            update_option($name, $value, true);
        }

        return ['options' => array_keys($options), 'file' => $file];
    }

    private function get_or_null(object $engine, string $key): mixed
    {
        try {
            return $engine->get($key);
        } catch (\Osec\Cache\CacheNotSetException) {
            return null;
        }
    }

    private function failing_engine(): CacheInterface
    {
        return new class () implements CacheInterface {
            public static function is_available(): bool
            {
                return true;
            }

            public function set(string $key, mixed $value): bool
            {
                throw new CacheWriteException('Simulated write failure');
            }

            public function add(string $key, mixed $value): bool
            {
                return $this->set($key, $value);
            }

            public function get(string $key, mixed $default = null): mixed
            {
                throw new \Osec\Cache\CacheNotSetException('none');
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear_cache(): bool
            {
                return true;
            }

            public function delete_matching(string $pattern): int
            {
                return 0;
            }
        };
    }
}
