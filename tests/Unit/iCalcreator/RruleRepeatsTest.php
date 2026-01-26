<?php

namespace Osec\Tests;

use DateTime;
use DateTimeZone;
use Kigkonsult\Icalcreator\Pc;
use Kigkonsult\Icalcreator\Util\RecurFactory;
use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use Osec\Tests\Unit\Cache\CacheFileTestBase;
use RRule\RSet;


/**
 * @group rrules
 * Sample test case.
 */
class RruleRepeatsTest extends CacheFileTestBase
{
    private static function getData(): array
    {
        return [

            // DAILY
//            ['FREQ=DAILY'],
//            ['FREQ=DAILY;INTERVAL=2'],
//            ['FREQ=DAILY;COUNT=10'],
//            ['FREQ=DAILY;UNTIL=20271231T235959Z'],
            ['FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR'],
            ['FREQ=DAILY;BYHOUR=9;BYMINUTE=30'],

            // WEEKLY
            ['FREQ=WEEKLY;BYDAY=MO'],
            ['FREQ=WEEKLY;BYDAY=MO,WE,FR'],
            ['FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,TH'],
            ['FREQ=WEEKLY;WKST=MO;BYDAY=MO'],
            ['FREQ=WEEKLY;COUNT=20;BYDAY=SU'],

            // MONTHLY
            ['FREQ=MONTHLY;BYMONTHDAY=15'],
            ['FREQ=MONTHLY;BYMONTHDAY=-1'],
            ['FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1'],
            ['FREQ=MONTHLY;BYDAY=MO;BYSETPOS=-1'],
            ['FREQ=MONTHLY;INTERVAL=3;BYMONTHDAY=10'],

            // YEARLY
            ['FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=1'],
            ['FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=31'],
            ['FREQ=YEARLY;BYDAY=MO;BYWEEKNO=1'],
            ['FREQ=YEARLY;BYYEARDAY=100'],
            ['FREQ=YEARLY;INTERVAL=4;BYMONTH=2;BYMONTHDAY=29'],

            // EDGE CASES
            ['FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1'],
            ['FREQ=DAILY;COUNT=5;UNTIL=20301231T235959Z'],
            ['FREQ=DAILY;INTERVAL=30'],
            ['FREQ=YEARLY;BYMONTH=3,6,9;BYDAY=MO;BYSETPOS=2'],
            ['FREQ=YEARLY;BYWEEKNO=20;BYDAY=TH'],
            ['FREQ=MONTHLY;BYMONTHDAY=-15'],

            // INVALID BUT REALISTIC (must not crash)
//            ['FREQ=MONTHLY;BYSETPOS=1'],
//            ['FREQ=WEEKLY;BYMONTHDAY=15'],
//            ['FREQ=DAILY;BYWEEKNO=10'],
//            ['FREQ=YEARLY;BYMONTHDAY=32'],
//            ['FREQ=YEARLY;BYMONTH=13'],

            // Copied from UI
            ['FREQ=YEARLY;BYMONTH=1,9;COUNT=100;'],
            ['FREQ=YEARLY;BYMONTH=11;UNTIL=20280125T000000Z;'],
            ['FREQ=YEARLY;'],
            ['FREQ=YEARLY;INTERVAL=6;COUNT=10;'],
            ['FREQ=MONTHLY;BYday=5TH;'],
            ['FREQ=YEARLY;BYMONTH=1,2;'],
            ['FREQ=MONTHLY;BYMONTHDAY=9,27;'],
        ];
    }

    /**
     * ddev phpunit --filter test_rrule_parsing  ./tests/Unit/iCalcreator/RruleRepeatsTest.php
     *
     * @group request_params
     *
     * @dataProvider getData
     */
    public function test_rrule_parsing(string $rrule)
    {
        global $osec_app;

        // This generally works, but a lot of Standards
        // (e.g bymonthday, BYMONTH=6,7 ...)
        // are not implemented and thus produce errors.

        $rrule = 'RRULE:' . $rrule;
        $rruleObj = new RSet($rrule);
        $exrule = 'EXRULE:' . $rrule;
        $exruleObj = new RSet($rrule);

        $rruleCollection = $rruleObj->getOccurrencesBetween(new \DateTime(), new \DateTime('+1 year'));
        $exuleCollection = $exruleObj->getOccurrencesBetween(new \DateTime(), new \DateTime('+1 year'));

        $DATA = [];
        foreach ($rruleCollection as $occurrence) {
            $ZZZ = $occurrence->format('D d M Y');
            $DATA[$occurrence->format('U')] = true;
            $NNN = false;
        }
        foreach ($exuleCollection as $occurrence) {
            $ZZZ = $occurrence->format('D d M Y');
            if (isset($DATA[$occurrence->format('U')])) {
                unset($DATA[$occurrence->format('U')]);
            }
            $NNN = false;
        }

        $NNN = FALSE;
        // Ensure rrule and exrule work as expected
        $this->assertEquals(0, count($DATA));
    }

    /**
     * ddev phpunit --filter test_rrule_parsing_recur2date  ./tests/Unit/iCalcreator/RruleRepeatsTest.php
     *
     * @group request_params
     *
     * @dataProvider getData
     */
    public function test_rrule_parsing_recur2date(string $rrule)
    {
        global $osec_app;

        // Starts
        $dtstart = new DateTime();
        // Limit
        $repeatUntil = new DateTime('+1 year');
        // Timezone
        $timezone = new DateTimeZone('Europe/Berlin');

        /**
         * 1) RRULE-String → RRULE-Array
         *    (genau das Format, das RecurFactory erwartet)
         */
        $rrule_array = [];
        foreach (explode(';', $rrule) as $part) {
            if (!str_contains($part, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $part, 2);
            $key = strtoupper($key);

            $rrule_array[$key] = str_contains($value, ',')
                ? explode(',', $value)
                : $value;
        }
        unset($part, $key, $value);

        /**
         * 2) Hard-Limit gegen Endlos-RRULEs
         */
        if (!isset($rrule_array['UNTIL'])) {
            $rrule_array['UNTIL'] = clone $repeatUntil;
        } elseif (is_string($rrule_array['UNTIL'])) {
            $rrule_array['UNTIL'] = new DateTime($rrule_array['UNTIL']);
        }


        /**
         * 4) Recurrence berechnen
         */
        $DATA = [];
        $dtstart = new DateTime();

        RecurFactory::recur2date(
            $DATA,        // Ergebnis (array<string,bool>)
            $rrule_array,         // RRULE-Array
            $dtstart,       // DTSTART
            $dtstart,       // Seed
            $repeatUntil    // absolutes Stop-Datum
        );


        /**
         * 5) Ergebnis normalisieren (Timestamp + Ziel-TZ)
         */
         $timestamped_data = [];
        foreach ($DATA as $dateStamp => $bool) {
            $instance = new DateTime($dateStamp, $timezone);

            // Uhrzeit wie Basis-Event
            $instance->setTime(
                (int) $dtstart->format('H'),
                (int) $dtstart->format('i')
            );
            $timestamped_data[$instance->getTimestamp()] = true;
        }
        unset($instance, $bool, $dateStamp);

        $count = count($timestamped_data);
        // Ensure rrule and exrule work as expected
        $this->assertEquals(0, count($timestamped_data));
    }


    /**
     * ddev phpunit --filter test_rrule_parsing_ical_setRrule  ./tests/Unit/iCalcreator/RruleRepeatsTest.php
     *
     * @group request_params
     *
     * @dataProvider getData
     */
    public function test_rrule_parsing_ical_setRrule(string $ruleString)
    {
        global $osec_app;

        // Starts
        $dtstart = new DateTime();
        // Limit
        $repeatUntil = new DateTime('+1 year');
        // Timezone
        $timezone = new DateTimeZone('Europe/Berlin');
        $cal = new Vcalendar();
        $vevent = new Vevent();
        $vevent->setDtstart($dtstart);




        $pc = Pc::factory('RRULE', self::parseRfcRuleForRecurFactory($ruleString));
        $vevent->setRrule(self::parseRfcRuleForSetRrule($ruleString));

// parseRfcRuleForRecurFactory

        $vevent->setRrule($pc);
        $cal->addComponent($vevent);

        $it = $cal->getDateIterator(
            '20240101T000000Z',
            '20240131T235959Z'
        );



        // Ensure rrule and exrule work as expected
//        $this->assertEquals(0, count($timestamped_data));
    }

    public static function parseRfcRuleForSetRrule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [, $rule] = explode(':', $rule, 2);
        }

        $out = [];

        foreach (explode(';', $rule) as $chunk) {
            if (!$chunk) continue;
            [$k, $v] = explode('=', $chunk, 2);

            $k = strtoupper($k);

            if ($k === 'BYDAY') {
                $out[$k] = explode(',', $v); // 👈 STRING ARRAY
                continue;
            }

            if (str_contains($v, ',')) {
                $out[$k] = explode(',', $v);
            } elseif (is_numeric($v)) {
                $out[$k] = (int)$v;
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    public static function parseRfcRuleForRecurFactory(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [, $rule] = explode(':', $rule, 2);
        }

        $out = [];

        foreach (explode(';', $rule) as $chunk) {
            if (!$chunk) continue;
            [$k, $v] = explode('=', $chunk, 2);
            $k = strtoupper($k);

            if ($k === 'BYDAY') {
                $out[$k] = array_map(
                    fn($d) => ['DAY' => $d],
                    explode(',', $v)
                );
                continue;
            }

            $out[$k] = str_contains($v, ',')
                ? explode(',', $v)
                : (is_numeric($v) ? (int)$v : $v);
        }

        return $out;
    }
}
