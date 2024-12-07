<?php

namespace Osec\Tests\Unit\Cache;

use Exception;
use Osec\Cache\CachePath;
use Osec\Tests\Utilities\TestBase;

/**
 * This class allows to make file paths readonly for a test
 * and restore original permissions later at tearDown().
 *
 * In case tests crashed without tearDown() being called
 * you may end up with unwritable folders.
 *
 * E.g.
 *  chmod 777 /var/www/html/wp-content/plugins/open-source-event-calendar/cache/
 *  chmod 777 /var/www/html/phpunit_wp_cache/wordpress/wp-content/uploads/open_source_event_calendar_cache
 *   or delete folder `open_source_event_calendar_cache` in wp-uploads dir.
 * We check/clear the dirs at test start in tests/Utilities/bootstrap.php.
 */
class CacheFileTestBase extends TestBase
{

    protected const FILE_PERMISSION_READONLY = 0500;
    protected string $wp_upload_path;
    private array $_restorePermissions = [];
    private array $_deleteDirectories = [];

    /**
     * Create a directory and
     *  let it be deleted after testing is done
     */
    protected function deleteAtTeardown(string $path): void
    {
        $this->precheckDirectory($path);
        $this->_deleteDirectories[] = $path;
    }

    /**
     * Precaution:
     * Limit File Actions to Plugin and wp-upload dirs.
     * You will never know how many this saved your life ;)
     *
     * @param $path
     *
     * @return void
     * @throws Exception
     */
    protected function precheckDirectory($path): void
    {
        if ( ! (str_starts_with($path, OSEC_FILE_CACHE_DEFAULT_PATH)
                || ($this->wp_upload_path && str_starts_with($path, $this->wp_upload_path)))) {
            throw new Exception('Path not in whitelist. Got: ' . $path);
        }
        if (
            $path === $this->wp_upload_path . OSEC_FILE_CACHE_WP_UPLOAD_DIR
            && !is_dir($path)
        ) {
            wp_mkdir_p($path);
        }
        if ( ! is_dir($path)) {
            throw new Exception('Only directories allowed. Got: ' . $path);
        }
    }

    /**
     * Make a dir Readonly
     *  e.g to test unavailability of cache.
     */
    protected function makeDirReadonly(string $path): bool
    {
        $this->precheckDirectory($path);
        $this->_restorePermissions[] = [
            'path'  => $path,
            'perms' => fileperms($path),
        ];

        return chmod($path, self::FILE_PERMISSION_READONLY);
    }

    /*
     * Let's try to ensure we don't delete or chmod anything bad.
     */

    protected function setUp(): void
    {
        parent::setUp();
        $wp_upload = wp_upload_dir();
        if ( ! $wp_upload['error']) {
            $this->wp_upload_path = trailingslashit($wp_upload['basedir']);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->_restorePermissions as $i => $restore) {
            $this->precheckDirectory($restore['path']);
            chmod($restore['path'], $restore['perms']);
            unset($this->_restorePermissions[$i]);
        }
        foreach ($this->_deleteDirectories as $i => $restore) {
            $this->precheckDirectory($restore);
            if (CachePath::clean_and_check_dir($restore) && rmdir($restore)) {
                unset($this->_deleteDirectories[$i]);
            }
        }
    }

}
