<?php

namespace Osec\Tests\Unit\App\Model\Date;

use DateTime;
use DateTimeZone;
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

        /* @var int $weekStartInUTC Monday November 11, 2024 00:00:00 in time zone Europe/Berlin (CET) */
        $weekStartInUTC = 1731279600;

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

    /**
     * Regression test for the week grid showing the wrong week: `getWeekStart()`
     * used to compute the weekday via `$this->date->format('w')`, i.e. in
     * whatever timezone the receiver itself happened to carry - UTC by
     * default (`DT::__construct()`), regardless of the requested target
     * timezone. For a positive UTC offset, local midnight is still the
     * previous day in UTC, so the weekday read one day early.
     *
     * Covers all 7 possible `week_start_day` settings (0 = Sunday … 6 =
     * Saturday), not just Sunday/Monday, since the bug's one-day shift moves
     * which weekday is "already the week start" (the guard in `getWeekStart()`
     * that skips the `modify('last …')` call) for every setting.
     *
     * @group date-start-of-week
     */
    public function test_get_week_start_across_timezones_and_weekdays()
    {
        global $osec_app;

        $original_week_start_day = $osec_app->settings->get('week_start_day');

        try {
            // Pacific/Kiritimati (UTC+14) and Pacific/Niue (UTC-11) are the most
            // extreme real-world offsets - any timezone-blind arithmetic shifts
            // the calendar day by a whole day under them.
            $timezones = ['UTC', 'Europe/Berlin', 'Pacific/Kiritimati', 'Pacific/Niue'];
            $dates     = [
                '2026-09-20', // Sunday
                '2026-09-21', // Monday
                '2026-09-22', // Tuesday
                '2026-09-23', // Wednesday
                '2026-09-24', // Thursday
                '2026-09-25', // Friday
                '2026-09-26', // Saturday
            ];

            foreach ([0, 1, 2, 3, 4, 5, 6] as $week_start_day) {
                $osec_app->settings->set('week_start_day', $week_start_day);

                foreach ($timezones as $tz) {
                    foreach ($dates as $date) {
                        $weekday       = (int) (new DateTime($date))->format('w');
                        $days_back     = ($weekday - $week_start_day + 7) % 7;
                        $expected_date = (new DateTime($date))
                            ->modify("-{$days_back} days")
                            ->format('Y-m-d');

                        // Local midnight instant, wrapped with NO timezone
                        // argument - reproducing WeekView.php's original
                        // mistake, so this proves the receiver's own timezone
                        // is irrelevant to the result.
                        $instant = (new DateTime($date . ' 00:00:00', new DateTimeZone($tz)))
                            ->format('U');
                        $weekStart = (new DT((int) $instant))->getWeekStart(new DateTimeZone($tz));

                        $context = "date=$date tz=$tz week_start_day=$week_start_day";
                        $this->assertSame($expected_date, $weekStart->format('Y-m-d', $tz), $context);
                        $this->assertSame(
                            $week_start_day,
                            (int) $weekStart->format('w', $tz),
                            $context
                        );
                        $this->assertSame('00:00:00', $weekStart->format('H:i:s', $tz), $context);

                        // Idempotency: getWeekStart() of a week start is itself.
                        $again = $weekStart->getWeekStart(new DateTimeZone($tz));
                        $this->assertSame($expected_date, $again->format('Y-m-d', $tz), $context);
                    }
                }
            }
        } finally {
            $osec_app->settings->set('week_start_day', $original_week_start_day);
        }
    }
}
