<?php

namespace Osec\Tests\Unit\App\Model;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RRule\RRule;
use RRule\RSet;

use function RRule\date_interval_days;

/**
 * Pins the fixes rlanvin/php-rrule v3.0.0 shipped.
 *
 * Every test here fails on v2.6.0 (the version shipped up to OSEC 1.1.14), so a
 * downgrade of the library is caught by the suite instead of silently changing
 * recurrence behaviour.
 *
 * None of these defects reach OSEC itself - EventInstance only calls
 * RfcParser::parseRRule(), new RRule() and iterates it, always with a mutable
 * DateTime. They are pinned because that is a property of the calling code, not
 * of the library, and it is cheap to lose.
 *
 * @group recurrence
 * @group vendor
 */
class PhpRruleApiTest extends TestCase
{
    /**
     * DateTimeImmutable DTSTART must be converted to UTC for the RFC string.
     *
     * v2.6.0 called `$dtstart->setTimezone(...)` and discarded the return value.
     * On DateTimeImmutable that is a no-op, so an unsupported offset timezone
     * kept its local wall clock time while being labelled 'Z'.
     *
     * @see https://github.com/rlanvin/php-rrule/pull/171
     */
    public function testRfcStringConvertsImmutableDtstartWithOffsetTimezone(): void
    {
        $rule = new RRule([
            'FREQ'    => 'DAILY',
            'COUNT'   => 3,
            'DTSTART' => new DateTimeImmutable('2026-09-23 00:15:00', new DateTimeZone('+02:00')),
        ]);

        // 00:15 at +02:00 is 22:15 UTC on the previous day. v2.6.0: 20260923T001500Z.
        $this->assertStringContainsString('DTSTART:20260922T221500Z', $rule->rfcString());
    }

    /**
     * Same defect on UNTIL, which the RFC requires in UTC.
     *
     * @see https://github.com/rlanvin/php-rrule/pull/171
     */
    public function testRfcStringConvertsImmutableUntilToUtc(): void
    {
        $timezone = new DateTimeZone('Europe/Berlin');
        $rule     = new RRule([
            'FREQ'    => 'DAILY',
            'DTSTART' => new DateTimeImmutable('2026-09-23 00:15:00', $timezone),
            'UNTIL'   => new DateTimeImmutable('2026-09-30 00:15:00', $timezone),
        ]);

        // v2.6.0: UNTIL=20260930T001500Z, the Berlin wall clock time labelled UTC.
        $this->assertStringContainsString('UNTIL=20260929T221500Z', $rule->rfcString(true));
    }

    /**
     * occursAt() must compare instants, not wall clock times, for immutable dates.
     *
     * The probe below is the same instant as the first occurrence, expressed in
     * another timezone. v2.6.0 skipped the conversion and returned false.
     *
     * @see https://github.com/rlanvin/php-rrule/pull/171
     */
    public function testOccursAtConvertsImmutableDateToDtstartTimezone(): void
    {
        $rule = new RRule([
            'FREQ'    => 'DAILY',
            'COUNT'   => 5,
            'DTSTART' => new DateTimeImmutable('2026-09-23 00:15:00', new DateTimeZone('Europe/Berlin')),
        ]);

        $same_instant = new DateTimeImmutable('2026-09-23 01:15:00', new DateTimeZone('Europe/Helsinki'));

        $this->assertTrue($rule->occursAt($same_instant));
        // The mutable path was never broken; assert both agree.
        $this->assertTrue($rule->occursAt(new DateTime('2026-09-22 22:15:00', new DateTimeZone('UTC'))));
    }

    /**
     * RSet must still de-duplicate across the boundary of a warmed cache.
     *
     * Reading an offset first populates the cache. v2.6.0 reset the
     * de-duplication state before yielding cached occurrences, so the first
     * freshly generated occurrence was never compared against the last cached
     * one and an RDATE colliding with an RRULE occurrence was emitted twice.
     *
     * @dataProvider provideCacheWarmupOffsets
     * @see https://github.com/rlanvin/php-rrule/issues/165
     */
    public function testRsetDeduplicatesAfterArrayAccess(int $offset): void
    {
        $set = new RSet();
        $set->addRRule([
            'FREQ'    => 'DAILY',
            'COUNT'   => 4,
            'DTSTART' => new DateTime('2026-09-23 10:00:00'),
        ]);
        // Collides with the occurrence the RRULE already produces at $offset.
        $set->addDate(new DateTime('2026-09-2' . (3 + $offset) . ' 10:00:00'));

        $set[$offset];

        $dates = [];
        foreach ($set as $occurrence) {
            $dates[] = $occurrence->format('Y-m-d');
        }

        $this->assertSame(
            ['2026-09-23', '2026-09-24', '2026-09-25', '2026-09-26'],
            $dates
        );
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provideCacheWarmupOffsets(): array
    {
        return [
            'first occurrence cached'  => [0],
            'second occurrence cached' => [1],
            'third occurrence cached'  => [2],
        ];
    }

    /**
     * date_interval_days() must fall back to format('%a') when `days` is false.
     *
     * DateInterval::$days is false unless the interval came from diff(); Carbon 3
     * returns such an interval from its own diff(), which made occursAt() divide
     * by a false value. v2.6.0 has no such helper at all.
     *
     * A real Carbon interval would need nesbot/carbon as a dev dependency, so the
     * double below reproduces only the property that matters.
     *
     * @see https://github.com/rlanvin/php-rrule/issues/164
     * @see https://github.com/briannesbitt/Carbon/issues/3018
     */
    public function testDateIntervalDaysFallsBackToFormat(): void
    {
        $real = (new DateTime('2026-09-23'))->diff(new DateTime('2026-09-28'));
        $this->assertSame(5, date_interval_days($real));

        $carbon_like = new class ('P5D') extends DateInterval {
            public function format(string $format): string
            {
                return '%a' === $format ? '5' : parent::format($format);
            }
        };
        $this->assertFalse($carbon_like->days, 'Precondition: mimics a Carbon interval.');
        $this->assertSame(5, date_interval_days($carbon_like));
    }
}
