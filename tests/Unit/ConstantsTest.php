<?php

namespace Osec\Tests\Unit;

use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 * Sample test case.
 */
class ConstantsTest extends TestBase
{
    public function test_is_debug_disabled()
    {
        $this->assertFalse(OSEC_DEBUG);
    }
    public function test_is_file_cache_enabled()
    {
        $this->assertTrue(OSEC_ENABLE_CACHE_FILE);
    }
    public function test_is_acpuapcu_cache_enabled()
    {
        $this->assertTrue(OSEC_ENABLE_CACHE_APCU);
    }
    public function test_is_less_debug_disabled()
    {
        $this->assertFalse(OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST);
    }

    public function is_delete_on_uninstall_disabled()
    {
        global $osec_app;
        $this->assertFalse(OSEC_UNINSTALL_PLUGIN_DATA);
    }
}
