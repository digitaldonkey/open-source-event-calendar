<?php

namespace Osec\Tests\Unit\Cache;

use FilesystemIterator;
use Osec\Cache\CacheFile;
use Osec\Cache\CacheNotSetException;
use Osec\Cache\CachePath;
use Osec\Cache\CacheWriteException;

/**
 * A file cache is one folder; a key is the file name, stored nowhere else.
 *
 * @group cache
 */
class CacheFileTest extends CacheFileTestBase
{
    public function tear_down()
    {
        global $osec_app;

        $osec_app->inject_object(CachePath::class, new CachePath($osec_app));
        parent::tear_down();
    }

    public function test_write_and_read()
    {
        $fileCache = $this->cache('testing_rw');

        $this->assertTrue($fileCache->set('osec-compiled-1.css', 'body{color:red}'));

        $this->assertFileExists($fileCache->getCachePath() . 'osec-compiled-1.css');
        $this->assertSame('body{color:red}', $fileCache->get('osec-compiled-1.css'));
        $this->assertSame(CachePath::ROOT_OVERRIDE, $fileCache->get_root());
    }

    public function test_no_index_options_are_written()
    {
        global $wpdb;

        $this->cache('testing_index')->set('osec-compiled-1.css', 'body{}');

        $this->assertSame(
            '0',
            $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'osec\\_file\\_cache\\_\\_%'")
        );
    }

    public function test_overwrite_replaces_the_file_and_leaves_no_temp_files()
    {
        $fileCache = $this->cache('testing_overwrite');

        $fileCache->set('osec-compiled-1.css', 'body{color:red}');
        $fileCache->set('osec-compiled-1.css', 'body{color:blue}');

        $this->assertSame('body{color:blue}', $fileCache->get('osec-compiled-1.css'));
        $this->assertSame(['osec-compiled-1.css'], $this->files($fileCache->getCachePath()));
    }

    public function test_missing_file_throws()
    {
        $this->expectException(CacheNotSetException::class);
        $this->cache('testing_missing')->get('osec-compiled-1.css');
    }

    public function test_delete()
    {
        $fileCache = $this->cache('testing_delete');
        $fileCache->set('osec-compiled-1.css', 'body{}');

        $this->assertTrue($fileCache->delete('osec-compiled-1.css'));

        $this->assertFileDoesNotExist($fileCache->getCachePath() . 'osec-compiled-1.css');
        $this->assertTrue($fileCache->delete('osec-compiled-1.css'));
    }

    public function test_clear_cache_empties_the_folder()
    {
        $fileCache = $this->cache('testing_clear');
        $fileCache->set('a.css', 'a{}');
        $fileCache->set('b.css', 'b{}');

        $this->assertTrue($fileCache->clear_cache());

        $this->assertSame([], $this->files($fileCache->getCachePath()));
    }

    public function test_write_failure_throws()
    {
        global $osec_app;

        $fileCache = CacheFile::createFileCacheInstance($osec_app, 'testing_gone');
        rmdir($fileCache->getCachePath());

        $this->expectException(CacheWriteException::class);
        $fileCache->set('osec-compiled-1.css', 'body{}');
    }

    public function test_key_must_be_a_plain_file_name()
    {
        $this->expectException(\Exception::class);
        $this->cache('testing_names')->set('../escape.css', 'body{}');
    }

    public function test_unavailable_when_no_folder_is_writable()
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

        $this->assertFalse(CacheFile::is_available());
        $this->assertNull(CacheFile::createFileCacheInstance($osec_app, 'css'));
    }

    private function cache(string $id): CacheFile
    {
        global $osec_app;

        $fileCache = CacheFile::createFileCacheInstance($osec_app, $id);
        $this->deleteAtTeardown($fileCache->getCachePath());

        return $fileCache;
    }

    private function files(string $dir): array
    {
        $names = [];
        foreach (new FilesystemIterator($dir) as $file) {
            $names[] = $file->getFilename();
        }
        sort($names);

        return $names;
    }
}
