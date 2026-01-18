<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 */
class DTTest extends TestBase
{
    public function test_date_object()
    {
        global $osec_app;
        /* @var DT $XX Monday, November 11, 2024 at 5:00:00 AM Coordinated Universal Time */
        $XX = 1731301200;
        $YY = new DT($XX);
        $ZZ = $YY->format_to_gmt();
        $this->assertEquals($XX, $ZZ);
    }

    public function test_date_object_timezone()
    {
        global $osec_app;
        // phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date

        /* @var DT $randomDate Monday, November 11, 2024 at 5:00:00 AM Coordinated Universal Time */
        $randomDate      = 1731301200;
        $LocalRandomDate = strtotime('2024-11-11 05:00');

        $phpTimeZoneIs = date_default_timezone_get(); // Which is a WP requirement to be UTC.

        $LocalRandomDateOffset = date('P', $LocalRandomDate);

        // Verify timezone using PHP functions.
        $localDateInUTC             = gmdate('U', $LocalRandomDate);
        $LocalRandomDateOffsetInSec = date('Z', $LocalRandomDate);
        $this->assertEquals($localDateInUTC, ($randomDate - $LocalRandomDateOffsetInSec));

        // Ensure UTC is Correct.
        $YY              = new DT($LocalRandomDate);
        $localDateInUTC2 = $YY->format_to_gmt();
        $this->assertEquals($localDateInUTC2, $randomDate - $LocalRandomDateOffsetInSec);

        // TODO
        // I didn't understans what DT->setTimezone() actually does.
        // Add a test to visualize this behavior.
    }

    /**
     * @group date-start-of-week
     */
    public function test_start_of_the_week()
    {
        global $osec_app;

        /* @var int $weekStartInUTC Date and time (GMT): Monday, 11. November 2024 00:00:00 */
        $weekStartInUTC = 1731283200;

        /* @var int $ranomDateInThisWeek Date and time (GMT): Thursday, 14. November 2024 17:45:00 */
        $ranomDateInThisWeek = 1731606300;

        // Ensure weekstartDay is actually set.
        $osec_app->settings->set('week_start_day', 1); // [0-6] = Sun-Sat

        $randomDateObject = new DT($ranomDateInThisWeek);
        $weekStart        = $randomDateObject->getWeekStart();

        $siteTimezone = Timezones::factory($osec_app)->get_default_timezone();
        $this->assertEquals('Europe/Berlin', $siteTimezone);

        $offset = $weekStart->utcOffsetInSeconds($siteTimezone);

         $weekStartReadable = date(DATE_RFC2822, $weekStartInUTC);
         $calculatedWeekStartReadable = date(DATE_RFC2822, $weekStart->format_to_gmt());
         $calculatedWeekStartReadableLocalized = $weekStart->format_i18n(DATE_RFC2822);

         // Soll UTC 1731283200
        $this->assertEquals($weekStartInUTC, (int) $weekStart->format_to_gmt());
    }
}
