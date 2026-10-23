<?php

namespace Osec\Tests\Unit\App\Model\Date;

use Osec\App\Model\Date\Timezones;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;

/**
 * Timezones::decode_gmt_timezone() falls back to UTC and tells the admin when an offset maps to no timezone.
 *
 * @group date
 */
class TimezonesDecodeTest extends TestBase
{
    public function test_known_offset_maps_to_a_timezone()
    {
        global $osec_app;

        $this->assertNotSame('UTC', Timezones::factory($osec_app)->decode_gmt_timezone(1));
    }

    /**
     * +15 hours is no timezone. The notice's sprintf() had "%1$" and "%3$" without a type, which PHP 8 rejects with a
     * ValueError, so the fallback ended in a fatal error.
     */
    public function test_unknown_offset_falls_back_to_utc_with_a_notice()
    {
        global $osec_app;

        $this->assertSame('UTC', Timezones::factory($osec_app)->decode_gmt_timezone(15));

        $notices = wp_json_encode($osec_app->options->get(NotificationAdmin::OPTION_KEY, []));
        $this->assertStringContainsString('UTC15', $notices);
        $this->assertStringContainsString('options-general.php', $notices);
    }
}
