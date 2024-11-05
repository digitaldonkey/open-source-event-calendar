<?php

namespace Osec\Tests\Unit\Cache;

use Exception;
use Osec\Cache\CachePath;
use Osec\Tests\Utilities\TestBase;

/**
 * Sample test case.
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
    protected function deleteAtTeardown(string $path) : void
    {
        $this->precheckDirectory($path);
        $this->_deleteDirectories[] = $path;
    }

    protected function precheckDirectory($path) : void
    {
        if ( ! is_dir($path)) {
            throw new Exception('Only directories allowed. Got: ' . $path);
        }
        if ( ! (str_starts_with($path, OSEC_FILE_CACHE_DEFAULT_PATH)
                || ($this->wp_upload_path && str_starts_with($path, $this->wp_upload_path)))) {
            throw new Exception('Path not in whitelist. Got: '.$path);
        }
    }

    /**
     * Make a dir Readonly
     *  e.g to test unavailability of cache.
     */
    protected function makeDirReadonly(string $path) : bool
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

    protected function setUp() : void
    {
        parent::setUp();
        $wp_upload = wp_upload_dir();
        if ( ! $wp_upload[ 'error' ]) {
            $this->wp_upload_path = trailingslashit($wp_upload[ 'basedir' ]);
        }
    }

    protected function tearDown() : void
    {
        foreach ($this->_restorePermissions as $i => $restore) {
            $this->precheckDirectory($restore[ 'path' ]);
            chmod($restore[ 'path' ], $restore[ 'perms' ]);
            unset($this->_restorePermissions[ $i ]);
        }
        foreach ($this->_deleteDirectories as $i => $restore) {
            $this->precheckDirectory($restore);
            if (CachePath::clean_and_check_dir($restore) && rmdir($restore)) {
                unset($this->_deleteDirectories[ $i ]);
            }
        }
    }

}
