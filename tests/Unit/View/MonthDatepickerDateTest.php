<?php

namespace Osec\Tests\Unit\View;

use DateTime;
use DateTimeZone;
use Osec\App\Model\Date\Timezones;
use Osec\App\View\Calendar\CalendarShortcodeView;
use Osec\Tests\Utilities\TestBase;

/**
 * The month view's date picker opens on the first of the displayed month.
 *
 * It was aligned to the first in UTC while keeping the time of local midnight, so east
 * of UTC (local midnight is the previous evening in UTC) it named the 2nd.
 *
 * @group date
 */
class MonthDatepickerDateTest extends TestBase
{
    public function test_datepicker_opens_on_the_first_of_the_month()
    {
        global $osec_app;

        $timezone = Timezones::factory($osec_app)->get_default_timezone();
        $this->assertEquals('Europe/Berlin', $timezone, 'Test assumes a timezone east of UTC.');

        $html = CalendarShortcodeView::factory($osec_app)->shortcode(['view' => 'monthly']);
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression('~ai1ec-minical-trigger[^>]*data-date="(\d+)/(\d+)/(\d+)"~s', $html);
        preg_match('~ai1ec-minical-trigger[^>]*data-date="(\d+)/(\d+)/(\d+)"~s', $html, $m);

        $today = new DateTime('now', new DateTimeZone($timezone));
        $this->assertSame(
            '1/' . $today->format('n/Y'),
            "$m[1]/$m[2]/$m[3]",
            'The month view picker names the first of the displayed month.'
        );
    }
}
