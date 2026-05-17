<?php

namespace Osec\Tests\Unit\App\Model;

use DateTime;
use Kigkonsult\Icalcreator\IcalInterface;
use Kigkonsult\Icalcreator\Pc;
use Kigkonsult\Icalcreator\Util\RecurFactory;
use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use PHPUnit\Framework\TestCase;

/**
 * Test iCalcreator v2.41.x API compatibility.
 *
 * Documents breaking changes from v2.40.x:
 * - getComponent() no longer filters by type
 * - getDtstart(true) returns Pc object instead of array
 * - RecurFactory::parseRexrule() removed
 */
class IcalcreatorApiTest extends TestCase
{
    /**
     * Test Vcalendar factory methods return correct type.
     */
    public function testVcalendarFactory(): void
    {
        $config = [IcalInterface::UNIQUE_ID => 'test-unique-id'];
        $cal = Vcalendar::factory($config);

        $this->assertInstanceOf(Vcalendar::class, $cal);

        $cal2 = new Vcalendar($config);
        $this->assertInstanceOf(Vcalendar::class, $cal2);
    }

    /**
     * Test getComponent() no longer filters by type in v2.41.x.
     *
     * In v2.40.x, getComponent('vevent') returned the first vevent.
     * In v2.41.x, it returns false - use getComponents() + array_filter instead.
     */
    public function testGetComponentDoesNotFilterByType(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();
        $event->setUid('test-uid');
        $event->setSummary('Test Event');

        $returned = $calendar->getComponent('vevent');

        // In v2.41.x, getComponent() with filter returns false
        $this->assertFalse($returned, 'getComponent() with type filter returns false in v2.41.x');
    }

    /**
     * Test getComponents() returns array of all components.
     */
    public function testGetComponentsReturnsArray(): void
    {
        $calendar = new Vcalendar();
        $calendar->newVevent();
        $calendar->newVevent();

        $components = $calendar->getComponents();

        $this->assertIsArray($components);
        $this->assertGreaterThanOrEqual(2, count($components));
    }

    /**
     * Test getXprop() returns array format.
     */
    public function testGetXpropReturnsArray(): void
    {
        $calendar = new Vcalendar();
        $calendar->setXprop('X-WR-CALNAME', 'Test Calendar');

        $result = $calendar->getXprop('X-WR-CALNAME');

        $this->assertIsArray($result);
        $this->assertEquals('X-WR-CALNAME', $result[0]);
        $this->assertEquals('Test Calendar', $result[1]);
    }

    /**
     * Test getDtstart() without parameter returns DateTime.
     */
    public function testGetDtstartWithoutParamReturnsDateTime(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $startTime = new DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $event->setDtstart($startTime);

        $result = $event->getDtstart();

        $this->assertInstanceOf(DateTime::class, $result);
    }

    /**
     * Test getDtstart() with param returns Pc object (v2.41.x).
     */
    public function testGetDtstartWithParamReturnsPcObject(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $startTime = new DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $event->setDtstart($startTime);

        $result = $event->getDtstart(true);

        $this->assertInstanceOf(Pc::class, $result);
        $this->assertInstanceOf(DateTime::class, $result->getValue());
    }

    /**
     * Test createRrule() returns string.
     */
    public function testCreateRruleReturnsString(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $rrule = [
            'FREQ' => 'WEEKLY',
            'UNTIL' => new DateTime('2024-12-31', new \DateTimeZone('UTC')),
            'INTERVAL' => 2,
        ];
        $event->setRrule($rrule);

        $result = $event->createRrule();

        $this->assertIsString((string)$result);
        $this->assertStringStartsWith('RRULE:', (string)$result);
    }

    /**
     * Test RecurFactory::parseRexrule() is removed in v2.41.x.
     */
    public function testParseRexruleIsRemoved(): void
    {
        $this->assertFalse(
            method_exists(RecurFactory::class, 'parseRexrule'),
            'RecurFactory::parseRexrule() was removed in v2.41.x'
        );
    }

    /**
     * Test RecurFactory::recur2date() still works.
     */
    public function testRecur2dateWorks(): void
    {
        $rules = [
            'FREQ' => 'WEEKLY',
            'COUNT' => 5,
        ];

        $startDate = new DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $endDate = new DateTime('2024-12-31 23:59:59', new \DateTimeZone('UTC'));

        $result = [];
        RecurFactory::recur2date($result, $rules, $startDate, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(4, count($result), 'recur2date generated occurrences');
    }

    /**
     * Test newVevent() returns Vevent instance.
     */
    public function testNewVeventReturnsVevent(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $this->assertInstanceOf(Vevent::class, $event);

        $event->setUid('test');
        $event->setSummary('Test');

        $this->assertEquals('test', $event->getUid());
        $this->assertEquals('Test', $event->getSummary());
    }

    /**
     * Test vtimezonePopulate() returns Vcalendar (fluent interface).
     */
    public function testVtimezonePopulateReturnsVcalendar(): void
    {
        $calendar = new Vcalendar();
        $result = $calendar->vtimezonePopulate('Europe/Berlin');

        $this->assertInstanceOf(Vcalendar::class, $result);
    }

    /**
     * Test parse() returns Vcalendar (fluent interface).
     */
    public function testParseReturnsVcalendar(): void
    {
        $icsData = "BEGIN:VCALENDAR\r\n"
            . "VERSION:2.0\r\n"
            . "PRODID:-//Test//Test//EN\r\n"
            . "BEGIN:VEVENT\r\n"
            . "UID:test@example.com\r\n"
            . "DTSTAMP:20240115T100000Z\r\n"
            . "DTSTART:20240115T100000Z\r\n"
            . "SUMMARY:Test Event\r\n"
            . "END:VEVENT\r\n"
            . "END:VCALENDAR\r\n";

        $calendar = new Vcalendar();
        $result = $calendar->parse($icsData);

        $this->assertInstanceOf(Vcalendar::class, $result);
    }

    /**
     * Test createCalendar() returns string.
     */
    public function testCreateCalendarReturnsString(): void
    {
        $calendar = new Vcalendar();
        $calendar->setXprop('X-WR-CALNAME', 'Test Calendar');

        $event = $calendar->newVevent();
        $event->setUid('test@example.com');
        $event->setSummary('Test Event');

        $dt = new DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $event->setDtstart($dt);
        $event->setDtend($dt);

        $result = $calendar->createCalendar();

        $this->assertIsString($result);
        $this->assertStringStartsWith('BEGIN:VCALENDAR', $result);
    }

    /**
     * Test getUid() returns string.
     */
    public function testGetUidReturnsString(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();
        $event->setUid('test-uid');

        $result = $event->getUid();

        $this->assertIsString($result);
        $this->assertEquals('test-uid', $result);
    }

    /**
     * Test getRecurrenceid() returns DateTime.
     */
    public function testGetRecurrenceidReturnsDateTime(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $dt = new DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $event->setRecurrenceid($dt);

        $result = $event->getRecurrenceid();

        $this->assertInstanceOf(DateTime::class, $result);
    }

    /**
     * Test sort() returns Vcalendar (fluent interface).
     */
    public function testSortReturnsVcalendar(): void
    {
        $calendar = new Vcalendar();
        $result = $calendar->sort();

        $this->assertInstanceOf(Vcalendar::class, $result);
    }

    /**
     * Test createRrule() can be parsed like original code expects.
     */
    public function testCreateRruleCanBeParsed(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $rrule = [
            'FREQ' => 'WEEKLY',
            'INTERVAL' => 2,
            'BYDAY' => ['MO', 'WE'],
        ];
        $event->setRrule($rrule);

        $rruleOutput = $event->createRrule();

        $this->assertIsString((string)$rruleOutput);

        $rruleParts = explode(':', (string)$rruleOutput);
        $rruleStr = trim(end($rruleParts));

        $this->assertStringStartsWith('FREQ=', $rruleStr);
        $this->assertStringContainsString('INTERVAL=2', $rruleStr);
    }

    /**
     * Test Pc object structure from getDtstart(true).
     */
    public function testPcObjectStructure(): void
    {
        $calendar = new Vcalendar();
        $event = $calendar->newVevent();

        $startTime = new DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $event->setDtstart($startTime);

        $result = $event->getDtstart(true);

        $this->assertInstanceOf(Pc::class, $result);

        $value = $result->getValue();
        $this->assertInstanceOf(DateTime::class, $value);
        $this->assertEquals('2024-01-15 10:00:00', $value->format('Y-m-d H:i:s'));
    }
}
