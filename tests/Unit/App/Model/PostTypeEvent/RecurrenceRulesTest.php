<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use DateTime;
use DateTimeZone;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventInstance;
use Osec\Tests\Utilities\TestBase;

/**
 * Expands a corpus of RFC 5545 recurrence rules through OSEC's own generator.
 *
 * The rules come from the abandoned `evaluate-libs-RFC-2445-vs-RFC-5545` branch,
 * where they only exercised the libraries directly. Here they run through
 * EventInstance::create_instances_by_recurrence(), which is what Event::save()
 * uses, so the assertions describe what actually lands in wp_osec_event_instances.
 *
 * Library-level behaviour is pinned separately in PhpRruleApiTest; DST handling
 * in EventInstanceTest.
 *
 * @group event
 * @group recurrence
 */
class RecurrenceRulesTest extends TestBase
{
    private const TIMEZONE = 'Europe/Berlin';

    /**
     * Rules reported by osec_recurrence_truncated during one test.
     *
     * @var string[]
     */
    protected array $truncations = [];

    /**
     * @dataProvider recurrence_rules
     */
    public function test_rule_expands_to_expected_occurrences(
        string $rrule,
        string $start,
        string $until,
        array $expected
    ) {
        $this->assertSame($expected, $this->expand($rrule, $start, $until));
    }

    public function recurrence_rules(): array
    {
        return [
            'weekdays only' => [
                'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR',
                '2026-01-05 09:00',
                '2026-01-16 23:59',
                [
                    '2026-01-05 09:00',
                    '2026-01-06 09:00',
                    '2026-01-07 09:00',
                    '2026-01-08 09:00',
                    '2026-01-09 09:00',
                    '2026-01-12 09:00',
                    '2026-01-13 09:00',
                    '2026-01-14 09:00',
                    '2026-01-15 09:00',
                    '2026-01-16 09:00',
                ],
            ],
            'every 30 days across month lengths' => [
                'FREQ=DAILY;INTERVAL=30',
                '2026-01-05 09:00',
                '2026-04-30 23:59',
                ['2026-01-05 09:00', '2026-02-04 09:00', '2026-03-06 09:00', '2026-04-05 09:00'],
            ],
            'every second week on two weekdays' => [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,TH',
                '2026-01-05 09:00',
                '2026-02-13 23:59',
                [
                    '2026-01-06 09:00',
                    '2026-01-08 09:00',
                    '2026-01-20 09:00',
                    '2026-01-22 09:00',
                    '2026-02-03 09:00',
                    '2026-02-05 09:00',
                ],
            ],
            'last day of the month' => [
                'FREQ=MONTHLY;BYMONTHDAY=-1',
                '2026-01-05 09:00',
                '2026-06-30 23:59',
                [
                    '2026-01-31 09:00',
                    '2026-02-28 09:00',
                    '2026-03-31 09:00',
                    '2026-04-30 09:00',
                    '2026-05-31 09:00',
                    '2026-06-30 09:00',
                ],
            ],
            'last monday of the month' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=-1',
                '2026-01-05 09:00',
                '2026-04-30 23:59',
                ['2026-01-26 09:00', '2026-02-23 09:00', '2026-03-30 09:00', '2026-04-27 09:00'],
            ],
            // Trailing semicolon and lower case key: both come from the event editor.
            'two days per month, trailing semicolon' => [
                'FREQ=MONTHLY;BYMONTHDAY=9,27;',
                '2026-01-05 09:00',
                '2026-04-30 23:59',
                [
                    '2026-01-09 09:00',
                    '2026-01-27 09:00',
                    '2026-02-09 09:00',
                    '2026-02-27 09:00',
                    '2026-03-09 09:00',
                    '2026-03-27 09:00',
                    '2026-04-09 09:00',
                    '2026-04-27 09:00',
                ],
            ],
            'fifth thursday, lower case rule part' => [
                'FREQ=MONTHLY;BYday=5TH;',
                '2026-01-05 09:00',
                '2026-12-31 23:59',
                [
                    '2026-01-29 09:00',
                    '2026-04-30 09:00',
                    '2026-07-30 09:00',
                    '2026-10-29 09:00',
                    '2026-12-31 09:00',
                ],
            ],
            // Day 100 falls one calendar day earlier in a leap year.
            'hundredth day of the year' => [
                'FREQ=YEARLY;BYYEARDAY=100',
                '2026-01-05 09:00',
                '2028-12-31 23:59',
                ['2026-04-10 09:00', '2027-04-10 09:00', '2028-04-09 09:00'],
            ],
            'thursday of ISO week 20' => [
                'FREQ=YEARLY;BYWEEKNO=20;BYDAY=TH',
                '2026-01-05 09:00',
                '2028-12-31 23:59',
                ['2026-05-14 09:00', '2027-05-20 09:00', '2028-05-18 09:00'],
            ],
            'leap day every fourth year' => [
                'FREQ=YEARLY;INTERVAL=4;BYMONTH=2;BYMONTHDAY=29',
                '2028-02-29 09:00',
                '2040-03-01 23:59',
                ['2028-02-29 09:00', '2032-02-29 09:00', '2036-02-29 09:00', '2040-02-29 09:00'],
            ],
            'yearly without rule parts repeats the start date' => [
                'FREQ=YEARLY;',
                '2026-01-05 09:00',
                '2029-12-31 23:59',
                ['2026-01-05 09:00', '2027-01-05 09:00', '2028-01-05 09:00', '2029-01-05 09:00'],
            ],
            'two months per year, editor syntax' => [
                'FREQ=YEARLY;BYMONTH=1,2;UNTIL=20290101T000000Z;',
                '2026-01-05 09:00',
                '',
                [
                    '2026-01-05 09:00',
                    '2026-02-05 09:00',
                    '2027-01-05 09:00',
                    '2027-02-05 09:00',
                    '2028-01-05 09:00',
                    '2028-02-05 09:00',
                ],
            ],
        ];
    }

    /**
     * COUNT is honoured as written, without the generator's own UNTIL.
     */
    public function test_count_limits_the_series()
    {
        $starts = $this->expand('FREQ=YEARLY;INTERVAL=6;COUNT=10;', '2026-01-05 09:00', '');

        $this->assertCount(10, $starts);
        $this->assertSame('2026-01-05 09:00', $starts[0]);
        $this->assertSame('2080-01-05 09:00', $starts[9]);
    }

    /**
     * UNTIL and COUNT in one rule: whichever ends the series first wins.
     *
     * RFC 5545 forbids the combination and php-rrule throws on it, but real
     * exporters emit it. Both parts are honoured instead of rejecting the event.
     */
    public function test_count_ends_the_series_before_until()
    {
        $starts = $this->expand('FREQ=DAILY;COUNT=3;UNTIL=20261231T235959Z', '2026-01-05 09:00', '');

        $this->assertSame(
            ['2026-01-05 09:00', '2026-01-06 09:00', '2026-01-07 09:00'],
            $starts
        );
    }

    /**
     * The other direction of the same invalid-but-common combination.
     *
     * `UNTIL` together with `COUNT` violates RFC 5545 and is rejected by
     * php-rrule, yet it is one of the rule errors feeds send most often. Rather
     * than dropping such an event, the generator falls back to honouring both
     * parts, so this pins the fallback from the UNTIL side: the rule asks for
     * 100 occurrences and gets the 4 that fit inside UNTIL.
     */
    public function test_until_ends_the_series_before_count()
    {
        $starts = $this->expand('FREQ=DAILY;COUNT=100;UNTIL=20260108T090000Z', '2026-01-05 09:00', '');

        // UNTIL is 09:00 UTC, which is 10:00 in the event timezone, so the
        // 09:00 occurrence of the 8th is still inside the bound.
        $this->assertSame(
            ['2026-01-05 09:00', '2026-01-06 09:00', '2026-01-07 09:00', '2026-01-08 09:00'],
            $starts
        );
    }

    /**
     * A far UNTIL is honoured, not cut down to OSEC_REOCCURRENCE_TIMEFRAME.
     *
     * The timeframe only bounds rules that name no end of their own. Applying it
     * to an explicit UNTIL would leave 4 of these 74 yearly occurrences, while
     * costing nothing: the instance ceiling is what keeps the table in check.
     */
    public function test_far_until_is_honoured()
    {
        $this->collect_truncations();
        $starts = $this->expand('FREQ=YEARLY;UNTIL=20991231T000000Z', '2026-01-05 09:00', '');

        $this->assertCount(74, $starts);
        $this->assertSame('2099-01-05 09:00', end($starts));
        $this->assertSame([], $this->truncations);
    }

    /**
     * An open ended rule still stops at OSEC_REOCCURRENCE_TIMEFRAME.
     */
    public function test_rule_without_an_end_stops_at_the_timeframe()
    {
        $starts = $this->expand('FREQ=DAILY', '2026-01-05 09:00', '');
        $limit  = (new DateTime(OSEC_REOCCURRENCE_TIMEFRAME))->format('Y-m-d H:i');

        $this->assertNotEmpty($starts);
        $this->assertLessThanOrEqual($limit, end($starts));
    }

    /**
     * Whatever the timeframe leaves unbounded is bound by the instance ceiling.
     */
    public function test_instance_count_is_capped()
    {
        $this->collect_truncations();

        $this->assertCount(
            OSEC_REOCCURRENCE_MAX_INSTANCES,
            $this->expand('FREQ=DAILY;COUNT=100000', '2026-01-05 09:00', ''),
            'A large COUNT is capped.'
        );
        $this->assertCount(
            OSEC_REOCCURRENCE_MAX_INSTANCES,
            $this->expand('FREQ=DAILY;UNTIL=20991231T000000Z', '2026-01-05 09:00', ''),
            'So is a daily series running to 2099, which asks for 27023 instances.'
        );
        $this->assertNotEmpty($this->truncations);
    }

    /**
     * A series inside both limits is not touched by them.
     */
    public function test_series_within_the_limits_is_not_truncated()
    {
        $this->collect_truncations();
        $starts = $this->expand('FREQ=DAILY', '2026-01-05 09:00', '2026-01-09 23:59');

        $this->assertCount(5, $starts);
        $this->assertSame([], $this->truncations);
    }

    /**
     * Records the rule of every osec_recurrence_truncated of this test.
     */
    protected function collect_truncations(): void
    {
        $this->truncations = [];
        add_action(
            'osec_recurrence_truncated',
            function ($rrule) {
                $this->truncations[] = $rrule;
            }
        );
    }

    /**
     * An exception rule removes its occurrences from the recurrence set.
     */
    public function test_exception_rule_removes_occurrences()
    {
        $starts = $this->expand(
            'FREQ=DAILY',
            '2026-01-05 09:00',
            '2026-01-18 23:59',
            'FREQ=WEEKLY;BYDAY=SA,SU;UNTIL=20260118T230000Z'
        );

        $this->assertSame(
            [
                '2026-01-05 09:00',
                '2026-01-06 09:00',
                '2026-01-07 09:00',
                '2026-01-08 09:00',
                '2026-01-09 09:00',
                '2026-01-12 09:00',
                '2026-01-13 09:00',
                '2026-01-14 09:00',
                '2026-01-15 09:00',
                '2026-01-16 09:00',
            ],
            $starts,
            'Both weekends are excluded.'
        );
    }

    /**
     * A start date the rule does not match is not added to the set.
     *
     * RFC 5545 calls an unsynchronized DTSTART undefined, so this pins the
     * choice rather than a requirement: the editor's start date disappears from
     * the calendar when the rule skips it.
     */
    public function test_start_date_outside_the_rule_is_not_added()
    {
        $starts = $this->expand('FREQ=MONTHLY;BYMONTHDAY=9,27', '2026-01-05 09:00', '2026-02-28 23:59');

        $this->assertNotContains('2026-01-05 09:00', $starts);
        $this->assertSame('2026-01-09 09:00', $starts[0]);
    }

    /**
     * A malformed rule is dropped and reported, it does not abort the save.
     *
     * php-rrule validates while parsing and constructing. Letting that reach
     * Event::save() would fail the editor save and abort a whole feed import
     * over one bad event, so the recurrence is dropped instead: the event keeps
     * its single occurrence and osec_recurrence_rule_invalid carries the reason.
     * UNTIL together with COUNT is repaired rather than dropped, see
     * test_count_ends_the_series_before_until().
     *
     * @dataProvider invalid_recurrence_rules
     */
    public function test_invalid_rule_is_dropped_and_reported(string $rrule)
    {
        $reported = [];
        add_action(
            'osec_recurrence_rule_invalid',
            function ($rule, $message) use (&$reported) {
                $reported[] = $message;
            },
            10,
            2
        );

        $this->assertSame([], $this->expand($rrule, '2026-01-05 09:00', '2026-06-30 23:59'));
        $this->assertCount(1, $reported);
        $this->assertNotEmpty($reported[0], 'The reason is passed on.');
    }

    public function invalid_recurrence_rules(): array
    {
        return [
            'BYMONTHDAY with weekly'   => ['FREQ=WEEKLY;BYMONTHDAY=15'],
            'BYWEEKNO with daily'      => ['FREQ=DAILY;BYWEEKNO=10'],
            'month out of range'       => ['FREQ=YEARLY;BYMONTH=13'],
            'month day out of range'   => ['FREQ=YEARLY;BYMONTHDAY=32'],
        ];
    }

    /**
     * Expands a rule the way Event::save() does.
     *
     * @param  string  $rrule  Recurrence rule.
     * @param  string  $start  Local start date time.
     * @param  string  $until  Local date time to stop at, '' to rely on the rule.
     * @param  string  $exrule  Exception rule.
     *
     * @return string[] Sorted 'Y-m-d H:i' values in the event timezone.
     */
    protected function expand(string $rrule, string $start, string $until, string $exrule = ''): array
    {
        global $osec_app;

        $zone       = new DateTimeZone(self::TIMEZONE);
        $start_date = new DT($start, self::TIMEZONE);
        $end_date   = new DT($start, self::TIMEZONE);
        $end_date->adjust(1, 'hour');

        if ('' !== $until) {
            $limit = (new DateTime($until, $zone))->setTimezone(new DateTimeZone('UTC'));
            $rrule = rtrim(trim($rrule), ';') . ';UNTIL=' . $limit->format('Ymd\THis\Z');
        }

        $event = new Event(
            $osec_app,
            [
                'post_id'          => 1,
                'start'            => $start_date,
                'end'              => $end_date,
                'allday'           => 0,
                'timezone_name'    => self::TIMEZONE,
                'recurrence_rules' => $rrule,
                'recurrence_dates' => '',
                'exception_rules'  => $exrule,
                'exception_dates'  => '',
            ]
        );

        $instances = EventInstance::factory($osec_app)->create_instances_by_recurrence(
            $event,
            ['post_id' => 1],
            $start_date->format_to_gmt(),
            3600,
            self::TIMEZONE
        );

        $starts = [];
        foreach (array_keys($instances) as $timestamp) {
            $starts[] = (new DateTime('@' . $timestamp))->setTimezone($zone)->format('Y-m-d H:i');
        }
        sort($starts);

        return $starts;
    }
}
