<?php

namespace Osec\Tests\Unit\App\Model\Date;

use Osec\App\Model\Date\DateValidator;
use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 */
class DateValidatorTest extends TestBase
{
    public static function invalid_calendar_dates(): array
    {
        return [
            ['32-13-2024'], // day 32, month 13
            ['99-99-9999'], // day 99, month 99
            ['30-2-2024'],  // Feb 30 - even leap years max out at 29
            ['29-2-2023'],  // Feb 29 in a non-leap year
            ['31-4-2026'],  // April has only 30 days
            ['1-1-0000'],   // year 0000
        ];
    }

    /**
     * @dataProvider invalid_calendar_dates
     */
    public function test_format_as_iso_rejects_calendrically_invalid_dates(string $date)
    {
        $this->assertFalse(DateValidator::format_as_iso($date, 'def'));
    }

    public static function valid_calendar_dates(): array
    {
        return [
            ['18-6-2026', '2026-06-18'],
            ['29-2-2024', '2024-02-29'], // leap year - must still pass
            ['1-1-1970', '1970-01-01'],
        ];
    }

    /**
     * @dataProvider valid_calendar_dates
     */
    public function test_format_as_iso_accepts_valid_dates(string $date, string $expected)
    {
        $this->assertEquals($expected, DateValidator::format_as_iso($date, 'def'));
    }

    public function test_format_as_iso_still_rejects_non_matching_pattern()
    {
        $this->assertFalse(DateValidator::format_as_iso('not-a-date', 'def'));
    }

    public function test_format_as_iso_accepts_iso_pattern()
    {
        $this->assertSame('2026-09-21', DateValidator::format_as_iso('2026-9-21', 'iso'));
    }

    public static function exact_date_timestamp_cases(): array
    {
        return [
            'truncated day fragment is rejected'      => ['21', false],
            'exactly one day past epoch is accepted'  => [86400, true],
            'one second short of a day is rejected'   => [86399, false],
            'a real calendar timestamp is accepted'   => [1789941600, true],
            'non-numeric input is rejected'           => ['garbage', false],
            'negative input is rejected'               => [-1, false],
        ];
    }

    /**
     * @dataProvider exact_date_timestamp_cases
     */
    public function test_is_exact_date_timestamp(mixed $timestamp, bool $expected)
    {
        $this->assertSame($expected, DateValidator::is_exact_date_timestamp($timestamp));
    }
}
