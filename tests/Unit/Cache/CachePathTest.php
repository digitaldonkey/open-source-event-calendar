<?php

namespace Osec\Tests\Unit\Cache;

use FilesystemIterator;
use Osec\Cache\CachePath;
use Osec\Exception\Exception;

/**
 * Cache folders (chosen when writing) and URLs (built when a page links a cached file).
 *
 * The test bootstrap sets the override OSEC_FILE_CACHE_DEFAULT_PATH to wp-content/osec-phpunit-cache/.
 * Folders are refused with an injected CachePath, so no permissions are changed.
 *
 * @group cachepath
 */
class CachePathTest extends CacheFileTestBase
{
    private string $document_root;

    private array $temp_dirs = [];

    private array $temp_files = [];

    public function set_up()
    {
        parent::set_up();
        $this->document_root = $_SERVER['DOCUMENT_ROOT'];
    }

    public function tear_down()
    {
        global $osec_app;

        $_SERVER['DOCUMENT_ROOT'] = $this->document_root;
        $osec_app->inject_object(CachePath::class, new CachePath($osec_app));
        array_map('unlink', array_filter($this->temp_files, 'file_exists'));
        foreach ($this->temp_dirs as $dir) {
            if (is_dir($dir)) {
                CachePath::delete_directory_content($dir);
                rmdir($dir);
            }
        }
        parent::tear_down();
    }

    public function test_override_folder_is_used_when_writable()
    {
        global $osec_app;

        $this->assertSame(
            ['root' => CachePath::ROOT_OVERRIDE, 'dir' => OSEC_FILE_CACHE_DEFAULT_PATH . 'css/'],
            CachePath::factory($osec_app)->get_dir('css')
        );
    }

    public function test_uploads_folder_when_the_override_is_refused()
    {
        $path = $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH]);

        $this->assertSame(
            [
                'root' => CachePath::ROOT_UPLOADS,
                'dir'  => $this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR . 'css/',
            ],
            $path->get_dir('css')
        );
    }

    public function test_no_folder_when_all_are_refused()
    {
        $path = $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH, $this->wp_upload_path]);

        $this->assertNull($path->get_dir('css'));
    }

    public function test_no_folder_when_uploads_has_an_error()
    {
        $path = $this->refuse([OSEC_FILE_CACHE_DEFAULT_PATH]);
        add_filter('upload_dir', [$this, 'upload_dir_error']);

        $this->assertNull($path->get_dir('css'));
        $this->assertNull($path->root_dir(CachePath::ROOT_UPLOADS, 'css'));
    }

    public function upload_dir_error(array $uploads): array
    {
        $uploads['error'] = 'Simulated upload error';

        return $uploads;
    }

    public function test_root_dir_does_not_create_folders()
    {
        global $osec_app;

        $dir = CachePath::factory($osec_app)->root_dir(CachePath::ROOT_OVERRIDE, 'never_created');

        $this->assertSame(OSEC_FILE_CACHE_DEFAULT_PATH . 'never_created/', $dir);
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_url_of_a_file_in_uploads()
    {
        global $osec_app;

        $file = $this->file($this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR . 'css/', 'osec-compiled-1.css');

        $this->assertSame(
            'http://example.org/wp-content/uploads/open_source_event_calendar_cache/css/osec-compiled-1.css',
            CachePath::factory($osec_app)->path_to_url($file)
        );
    }

    public function test_url_without_document_root()
    {
        global $osec_app;

        $file = $this->file(OSEC_FILE_CACHE_DEFAULT_PATH . 'css/', 'osec-compiled-1.css');
        // WP-CLI and cron have no DOCUMENT_ROOT (finding 1).
        $_SERVER['DOCUMENT_ROOT'] = '';

        $this->assertSame(
            'http://example.org/wp-content/osec-phpunit-cache/css/osec-compiled-1.css',
            CachePath::factory($osec_app)->path_to_url($file)
        );
    }

    public function test_url_of_a_file_below_abspath_only()
    {
        global $osec_app;

        $file = $this->file(ABSPATH . 'osec-cache-test/', 'a.css');

        $this->assertSame(
            'http://example.org/osec-cache-test/a.css',
            CachePath::factory($osec_app)->path_to_url($file)
        );
    }

    public function test_url_of_a_file_below_document_root_only()
    {
        global $osec_app;

        // WordPress in a subfolder of the web root, the cache next to it.
        $_SERVER['DOCUMENT_ROOT'] = dirname(realpath(ABSPATH));
        $file = $this->file(dirname(realpath(ABSPATH)) . '/osec-cache-test-docroot/', 'a.css');

        $this->assertSame(
            'http://example.org/osec-cache-test-docroot/a.css',
            CachePath::factory($osec_app)->path_to_url($file)
        );
    }

    public function test_no_url_outside_all_roots()
    {
        global $osec_app;

        $_SERVER['DOCUMENT_ROOT'] = '';
        $file = $this->file(dirname(realpath(ABSPATH)) . '/osec-cache-test-outside/', 'a.css');

        $this->assertNull(CachePath::factory($osec_app)->path_to_url($file));
    }

    public function test_url_segments_are_encoded()
    {
        global $osec_app;

        $file = $this->file(ABSPATH . 'osec cache ü/', 'a b.css');

        $this->assertSame(
            'http://example.org/osec%20cache%20%C3%BC/a%20b.css',
            CachePath::factory($osec_app)->path_to_url($file)
        );
    }

    public function test_url_of_uploads_served_elsewhere()
    {
        global $osec_app;

        add_filter('upload_dir', [$this, 'upload_dir_on_cdn']);
        $file = $this->file($this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR . 'css/', 'osec-compiled-1.css');

        $this->assertSame(
            'https://cdn.example.com/media/open_source_event_calendar_cache/css/osec-compiled-1.css',
            CachePath::factory($osec_app)->path_to_url($file)
        );
    }

    public function upload_dir_on_cdn(array $uploads): array
    {
        $uploads['baseurl'] = 'https://cdn.example.com/media';

        return $uploads;
    }

    public function test_delete_directory_content()
    {
        global $osec_app;

        $cachePath = CachePath::factory($osec_app)->get_dir('directory_to_delete')['dir'];
        $this->deleteAtTeardown($cachePath);
        $filePath = $cachePath . 'x/y/z/';
        $file     = $filePath . 'my_testfile';
        if ( ! wp_mkdir_p($filePath)) {
            throw new Exception('Can not create path.');
        }
        $writtenBytes = file_put_contents($file, 'Ene mene muh und raus bist du.');
        $this->assertEquals(
            30,
            $writtenBytes
        );
        CachePath::delete_directory_content($cachePath);
        // @see https://stackoverflow.com/a/18856880/308533
        $isDirEmpty = ! (new FilesystemIterator($cachePath))->valid();
        $this->assertTrue($isDirEmpty);
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

        return $path;
    }

    private function file(string $dir, string $name): string
    {
        wp_mkdir_p($dir);
        if ( ! str_starts_with($dir, OSEC_FILE_CACHE_DEFAULT_PATH) && ! str_starts_with($dir, $this->wp_upload_path)) {
            $this->temp_dirs[] = $dir;
        }
        file_put_contents($dir . $name, '/* test */');
        $this->temp_files[] = $dir . $name;

        return realpath($dir . $name);
    }
}
