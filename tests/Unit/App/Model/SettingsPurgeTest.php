<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Settings;
use Osec\Tests\Utilities\TestBase;

/**
 * The purge on deactivation (OSEC_UNINSTALL_PLUGIN_DATA) removes the plugin's options, not others' that only
 * start alike: LIKE 'osec_%' also matched osecurity_* and osec-* (a bare _ matches any character).
 *
 * @group settings
 */
class SettingsPurgeTest extends TestBase
{
    public function test_purge_removes_only_osec_options()
    {
        global $osec_app;

        update_option('osec_purge_test', 'ours');
        update_option('osecurity_key', 'theirs');
        update_option('osec-other', 'theirs');
        $this->assertSame('ours', get_option('osec_purge_test'));

        Settings::factory($osec_app)->uninstall(true);

        $this->assertFalse(get_option('osec_purge_test'), 'deleted, and gone from the option cache');
        $this->assertSame('theirs', get_option('osecurity_key'));
        $this->assertSame('theirs', get_option('osec-other'));
    }
}
