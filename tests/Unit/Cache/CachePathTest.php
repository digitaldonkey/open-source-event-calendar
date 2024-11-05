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
            '/var/www/html/wp-content/plugins/' . OSEC_PLUGIN_NAME . '/cache/',
            $cache_path
        );
    }

    public function test_default_cache_path_with_subpath()
    {
        $cachePath = (new CachePath())->getCachePath('the_new_directory');
        $this->deleteAtTeardown($cachePath);
        $this->assertEquals(
            '/var/www/html/wp-content/plugins/' . OSEC_PLUGIN_NAME . '/cache/the_new_directory/',
            $cachePath
        );
    }

    public function test_default_cache_path_not_writable()
    {
        $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);
        $cache_path = (new CachePath())->getCachePath();
        $this->assertEquals(
            '/var/www/html/phpunit_wp_cache/wordpress/wp-content/uploads/all_in_one_event_calendar_cache/',
            $cache_path
        );
        $this->deleteAtTeardown($cache_path);
    }

    public function test_no_cache_writable_returns_NULL()
    {
        $a = $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);
        $b = $this->makeDirReadonly($this->wp_upload_path);
        $cache_path = (new CachePath())->getCachePath();
        $this->assertNull($cache_path);
    }

    public function test_get_cache_object()
    {
        $cacheData = (new CachePath())->getCacheData('another_directory');
        $this->deleteAtTeardown($cacheData[ 'path' ]);

        $this->assertEquals(
            '/var/www/html/wp-content/plugins/' . OSEC_PLUGIN_NAME . '/cache/another_directory/',
            $cacheData[ 'path' ]
        );
        $this->assertEquals(
            'http://example.org/wp-content/plugins/' . OSEC_PLUGIN_NAME . '/cache/another_directory/',
            $cacheData[ 'url' ]
        );
    }

    public function test_delete_directory_content()
    {
        $cachePath = (new CachePath())->getCachePath('directory_to_delete');
        $this->deleteAtTeardown($cachePath);
        $filePath = $cachePath.'x/y/z/';
        $file = $filePath.'my_testfile';
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
