<?php

namespace Osec\Tests\Unit\App\Model;

use DateTime;
use Osec\App\Model\Date\DT;
use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 * Sample test case.
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

		// Let's set a date in a local timezone.
		// Osec actually uses UTC internally.
		date_default_timezone_set('America/New_York');

		/* @var DT $randomDate Monday, November 11, 2024 at 5:00:00 AM Coordinated Universal Time */
		$randomDate = 1731301200;
		$LocalRandomDate = strtotime('2024-11-11 05:00');

		$localTimezoneIs = date_default_timezone_get();
		$LocalRandomDateOffset = date('P', $LocalRandomDate);

		// Verify timezone using PHP functions.
		$localDateInUTC =  gmdate('U', $LocalRandomDate);
		$LocalRandomDateOffsetInSec = date('Z', $LocalRandomDate);
		$this->assertEquals($localDateInUTC, $randomDate - $LocalRandomDateOffsetInSec);

		// Ensure UTC is Correct.
		$YY = new DT($LocalRandomDate);
		$localDateInUTC2 = $YY->format_to_gmt();
		$this->assertEquals($localDateInUTC2, $randomDate - $LocalRandomDateOffsetInSec);

		// TODO
		//   I didn't understans what DT->setTimezone() actually does.
		//   Add a test to visualize this behavior.
	}

	public function test_start_of_the_week()
	{
		global $osec_app;
		/* @var int $weekStartInUTC Date and time (GMT): Monday, 11. November 2024 00:00:00 */
		$weekStartInUTC = 1731283200;

		/* @var int $ranomDateInThisWeek Date and time (GMT): Thursday, 14. November 2024 17:45:00 */
		$ranomDateInThisWeek = 1731606300;

		$randomDateObject = new DT($ranomDateInThisWeek);
		$weekStart = $randomDateObject->getWeekStart();

		$siteTimezone = $randomDateObject->getSiteTimezone();
		$offset = $weekStart->utcOffsetInSeconds($siteTimezone);

		// $weekStartReadable = date(DATE_RFC2822, $weekStartInUTC);
		// $calculatedWeekStartReadable = date(DATE_RFC2822, $calculatedWeekStart->format_to_gmt());

		$this->assertEquals($weekStartInUTC - $offset, $weekStart->format_to_gmt());
	}

}
