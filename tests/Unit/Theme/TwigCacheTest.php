<?php

namespace Osec\Tests\Unit\Theme;

use Osec\Cache\CacheFile;
use Osec\Cache\CachePath;
use Osec\Tests\Unit\Cache\CacheFileTestBase;
use Osec\Theme\ThemeLoader;
use WP_Site;

/**
 * Twig cache folder per site (D2b): the override, then wp-content/cache/osec/, then uploads, then none.
 * Never the plugin folder, which a plugin update replaces.
 *
 * @group cache
 */
class TwigCacheTest extends CacheFileTestBase
{
    private string $content_root;

    public function set_up()
    {
        parent::set_up();
        $this->content_root = trailingslashit(realpath(WP_CONTENT_DIR)) . 'cache/osec/';
    }

    public function tear_down()
    {
        global $osec_app;

        $osec_app->inject_object(CachePath::class, new CachePath($osec_app));
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        foreach ([$this->content_root, OSEC_FILE_CACHE_DEFAULT_PATH . 'twig/'] as $dir) {
            if (is_dir($dir)) {
                CachePath::delete_directory_content(untrailingslashit(realpath($dir)));
            }
        }
        parent::tear_down();
    }

    public function test_override_folder_per_site()
    {
        global $osec_app;

        $this->assertSame(
            OSEC_FILE_CACHE_DEFAULT_PATH . 'twig/site-' . get_current_blog_id() . '/',
            CachePath::factory($osec_app)->get_twig_dir()
        );
    }

    public function test_wp_content_cache_when_the_override_is_refused()
    {
        $this->assertSame(
            $this->content_root . 'twig/site-' . get_current_blog_id() . '/',
            $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH])->get_twig_dir()
        );
    }

    public function test_uploads_when_wp_content_cache_is_refused()
    {
        $this->assertSame(
            $this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR . 'twig/',
            $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH, $this->content_root])->get_twig_dir()
        );
    }

    public function test_none_when_all_are_refused()
    {
        $this->assertNull(
            $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH, $this->content_root, $this->wp_upload_path])->get_twig_dir()
        );
    }

    public function test_never_the_plugin_folder()
    {
        global $osec_app;

        foreach (CachePath::factory($osec_app)->twig_dirs() as $dir) {
            $this->assertStringStartsNotWith(OSEC_PATH, $dir);
        }
    }

    public function test_rescan_stores_the_folder()
    {
        global $osec_app;

        $dir = ThemeLoader::factory($osec_app)->get_cache_dir(true);

        $this->assertSame(OSEC_FILE_CACHE_DEFAULT_PATH . 'twig/site-' . get_current_blog_id() . '/', $dir);
        $this->assertSame($dir, $osec_app->settings->get('twig_cache'));
    }

    /**
     * A page view uses the stored folder as long as it is writable, even when a preferred one exists now.
     */
    public function test_stored_folder_is_kept_without_rescan()
    {
        global $osec_app;

        $stored = $this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR . 'twig/';
        wp_mkdir_p($stored);
        $osec_app->settings->set('twig_cache', $stored);

        $this->assertSame($stored, ThemeLoader::factory($osec_app)->get_cache_dir());
    }

    public function test_rescan_when_the_stored_folder_is_gone()
    {
        global $osec_app;

        // E.g. the 1.1.x plugin folder cache/twig/ after an update.
        $osec_app->settings->set('twig_cache', OSEC_FILE_CACHE_DEFAULT_PATH . 'gone/');

        $this->assertSame(
            OSEC_FILE_CACHE_DEFAULT_PATH . 'twig/site-' . get_current_blog_id() . '/',
            ThemeLoader::factory($osec_app)->get_cache_dir()
        );
    }

    public function test_unavailable_is_stored_when_nothing_is_writable()
    {
        global $osec_app;

        $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH, $this->content_root, $this->wp_upload_path]);

        $this->assertNull(ThemeLoader::factory($osec_app)->get_cache_dir(true));
        $this->assertSame(CacheFile::OSEC_FILE_CACHE_UNAVAILABLE, $osec_app->settings->get('twig_cache'));
    }

    public function test_clear_cache_empties_every_twig_folder_of_the_site()
    {
        global $osec_app;

        $files = [];
        foreach (CachePath::factory($osec_app)->twig_dirs() as $dir) {
            wp_mkdir_p($dir . 'ab');
            $files[] = $dir . 'ab/template.php';
            file_put_contents(end($files), '<?php');
        }

        $this->assertTrue(ThemeLoader::factory($osec_app)->clear_cache());

        foreach ($files as $file) {
            $this->assertFileDoesNotExist($file);
        }
    }

    /**
     * C15: the upgrade flag was never consumed, because the filter check was inverted.
     */
    public function test_upgrade_flag_rescans_and_is_consumed()
    {
        global $osec_app;

        $osec_app->settings->set('twig_cache', OSEC_FILE_CACHE_DEFAULT_PATH . 'gone/');
        $osec_app->options->set(ThemeLoader::OPTION_FORCE_CLEAN, true);

        ThemeLoader::factory($osec_app)->clean_cache_on_upgrade();

        $this->assertFalse((bool) $osec_app->options->get(ThemeLoader::OPTION_FORCE_CLEAN));
        $this->assertSame(
            OSEC_FILE_CACHE_DEFAULT_PATH . 'twig/site-' . get_current_blog_id() . '/',
            $osec_app->settings->get('twig_cache')
        );
    }

    /**
     * Core deletes the uploads folder of a deleted site; the other Twig folders are ours to remove.
     */
    public function test_deleting_a_site_removes_only_its_twig_folders()
    {
        global $osec_app;

        $path = CachePath::factory($osec_app);
        $mine = $path->twig_dirs();
        $gone = array_filter($path->twig_dirs(987), fn($dir) => ! str_contains($dir, '/uploads/'));
        foreach (array_merge($mine, $gone) as $dir) {
            wp_mkdir_p($dir);
            file_put_contents($dir . 'template.php', '<?php');
        }

        // Loaded by core on multisite only, where this hook fires.
        require_once ABSPATH . WPINC . '/class-wp-site.php';
        do_action('wp_uninitialize_site', new WP_Site((object) ['blog_id' => 987]));

        foreach ($gone as $dir) {
            $this->assertDirectoryDoesNotExist($dir);
        }
        foreach ($mine as $dir) {
            $this->assertFileExists($dir . 'template.php');
        }
    }

    /**
     * Injects a CachePath that treats folders below the given paths as not writable.
     */
    private function refuse(array $prefixes): CachePath
    {
        global $osec_app;

        $path = new class ($osec_app, $prefixes) extends CachePath {
            public function __construct($app, private array $prefixes)
            {
                parent::__construct($app);
            }

            protected function is_writable_dir(string $dir): bool
            {
                foreach ($this->prefixes as $prefix) {
                    if (str_starts_with($dir, $prefix)) {
                        return false;
                    }
                }

                return parent::is_writable_dir($dir);
            }
        };
        $osec_app->inject_object(CachePath::class, $path);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));

        return $path;
    }
}
