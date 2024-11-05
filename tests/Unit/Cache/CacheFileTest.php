<?php

namespace Osec\Tests\Unit\Cache;

use FilesystemIterator;
use Osec\Cache\CacheFile;

/**
 * @group cache
 * Sample test case.
 */
class CacheFileTest extends CacheFileTestBase
{

    public function test_file_cache_basic()
    {
        global $osec_app;
        $fileCache = CacheFile::createFileCacheInstance($osec_app);
        $this->assertInstanceOf('\Osec\Cache\CacheFile', $fileCache);
    }

    public function test_file_cache_basic_unavailable()
    {
        global $osec_app;

        $this->makeDirReadonly(OSEC_FILE_CACHE_DEFAULT_PATH);
        $this->makeDirReadonly($this->wp_upload_path);

        $fileCache = CacheFile::createFileCacheInstance($osec_app);
        $this->assertNull($fileCache);

        $default_cache = $osec_app->options->get(CacheFile::optionKey('default_cache'));
        $this->assertEquals('OSEC_FILE_CACHE_UNAVAILABLE', $default_cache);
    }

    public function test_file_cache_write_and_read()
    {
        global $osec_app;
        $value = 'Curabitur blandit tempus porttitor.';
        $fileCache = CacheFile::createFileCacheInstance($osec_app, 'testing_rw');
        $this->deleteAtTeardown($fileCache->getCachePath());

        $cacheInfo = $fileCache->setWithFileInfo('testfile', $value);

        $this->assertTrue(file_exists($cacheInfo[ 'path' ]));
        $this->assertEquals($value, $fileCache->get('testfile'));
    }

    public function test_file_cache_write_and_delete()
    {
        global $osec_app;
        $value = 'Curabitur blandit tempus porttitor.';
        $fileCache = CacheFile::createFileCacheInstance($osec_app, 'another_context');
        $this->deleteAtTeardown($fileCache->getCachePath());
        $cacheInfo = $fileCache->setWithFileInfo('testfile.css', $value);
        $this->assertEquals($value, $fileCache->get('testfile.css'));
        $this->assertTrue($fileCache->delete('testfile.css'));
        $this->assertFalse(file_exists($cacheInfo[ 'path' ]));
    }

    public function test_file_cache_empty_all_caches()
    {
        global $osec_app;
        $fileCache = CacheFile::createFileCacheInstance($osec_app);
        $this->assertTrue($fileCache->empty_all_caches());

        $isDirEmpty = ! (new FilesystemIterator($fileCache->getCachePath()))->valid();
        $this->assertTrue($isDirEmpty);

    }

    public function test_file_cache_get_all_caches()
    {
        global $osec_app;

        $fileCache = CacheFile::createFileCacheInstance($osec_app);
        $fileCache->add('file.abc', 'Value1');
        $this->deleteAtTeardown($fileCache->getCachePath());

        $fileCache2 = CacheFile::createFileCacheInstance($osec_app, 'my_namespace');
        $fileCache2->add('file2.cde', 'Value2');

        $value = $fileCache->get_all_cache_files($osec_app);

        $this->assertTrue(count($value) === 2);

        $fileCacheX = CacheFile::createFileCacheInstance($osec_app, 'my_namespace');
        $value_2 = $fileCacheX->get('file2.cde');
        $this->assertTrue($value_2 === 'Value2');

        // 'file2.cde' OR 'md5prefix_file2.cde' Depends on debug.
        $filename_2 = $value[1]->filename;
        if (OSEC_DEBUG) {
            $this->assertEquals('file2.cde', basename($filename_2));
        }
        else {
            // @see https://regex101.com/r/LLSG7H/4
            $re = '/^(?<prefix>[a-zA-Z0-9]*_)?(?<name>[a-zA-Z0-9_\.]*)\.(?<ext>[a-zA-Z0-9]*)$/';
            preg_match_all($re, basename($filename_2), $matches, PREG_SET_ORDER, 0);
            $matches = $matches[0];
            $this->assertEquals(9, strlen($matches['prefix']));
            $this->assertEquals('file2.cde', $matches['name'] . '.' . $matches['ext']);
        }

    }
}
