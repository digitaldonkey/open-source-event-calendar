<?php

namespace Osec\Tests\Unit\Cache;

use FilesystemIterator;
use Osec\Cache\CachePath;
use Osec\Exception\Exception;

/**
 * @group cachepath
 *
 * Sample test case.
 */
class CachePathTest extends CacheFileTestBase
{

    public function test_default_cache_path()
    {
        $cache_path = (new CachePath())->getCachePath();

        $this->assertEquals(
            OSEC_TEST__PLUGIN_ROOT_PATH . 'cache/',
            $cache_path
        );
    }

    public function test_default_cache_path_with_subpath()
    {
        $cachePath = (new CachePath())->getCachePath('the_new_directory');
        $this->deleteAtTeardown($cachePath);
        $this->assertEquals(
            OSEC_TEST__PLUGIN_ROOT_PATH . 'cache/the_new_directory/',
            $cachePath
        );
    }

    public function test_default_cache_path_not_writable()
    {
        $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);
        $cache_path = (new CachePath())->getCachePath();
        $this->assertEquals(
            ABSPATH . 'wp-content/uploads/open_source_event_calendar_cache/',
            $cache_path
        );
        $this->deleteAtTeardown($cache_path);
    }

    public function test_no_cache_writable_returns_NULL()
    {
        $a          = $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);
        $b          = $this->makeDirReadonly($this->wp_upload_path);
        $cache_path = (new CachePath())->getCachePath();
        $this->assertNull($cache_path);
    }

    public function test_get_cache_object()
    {
        // Force to use wp-uploads based cache,
        // as plugin might not be in $_SERVER['DOCUMENT_ROOT'],
        // but wp-uploads will be as defined in tests/Utilities/bootstrap.php.
        // Depending on DOCUMENT_ROOT for Uri generation at getCacheData().
        $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);

        $cacheData = (new CachePath())->getCacheData('another_directory');
        $this->deleteAtTeardown($cacheData['path']);

        $this->assertEquals(
            ABSPATH . 'wp-content/uploads/open_source_event_calendar_cache/another_directory/',
            $cacheData['path']
        );
        $this->assertEquals(
            'http://example.org/wp-content/uploads/open_source_event_calendar_cache/another_directory/',
            $cacheData['url']
        );
    }

    public function test_delete_directory_content()
    {
        $cachePath = (new CachePath())->getCachePath('directory_to_delete');
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

}
