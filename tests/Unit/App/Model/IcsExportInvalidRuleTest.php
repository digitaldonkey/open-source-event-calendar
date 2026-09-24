<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * Exporting an event whose stored recurrence rule iCalcreator refuses.
 *
 * Event::save() refuses such a rule now, so this covers what is already in the
 * database: one bad event used to break the export feed for every other event
 * in it.
 *
 * @group ics
 * @group recurrence
 */
class IcsExportInvalidRuleTest extends TestBase
{
    public function test_export_leaves_out_a_rule_icalcreator_refuses()
    {
        global $osec_app;

        $reported = [];
        add_action(
            'osec_recurrence_rule_not_exportable',
            function ($rule, $message, $event) use (&$reported) {
                $reported[] = $message;
            },
            10,
            3
        );

        $broken = $this->create_event('Rule the export cannot write', 'FREQ=WEEKLY;BYMONTHDAY=15');
        $valid  = $this->create_event('Rule the export can write', 'FREQ=WEEKLY;COUNT=3');

        $ics = IcsImportExportParser::factory($osec_app)->export([
            'events'                    => [$broken, $valid],
            'do_not_export_as_calendar' => false,
        ]);

        $this->assertStringContainsString('Rule the export cannot write', $ics, 'The event is exported.');
        $this->assertStringContainsString('Rule the export can write', $ics);
        $this->assertStringContainsString('RRULE:FREQ=WEEKLY;COUNT=3', $ics, 'The valid rule is written.');
        $this->assertStringNotContainsString('BYMONTHDAY', $ics, 'The refused rule is left out.');
        $this->assertCount(1, $reported);
        $this->assertStringContainsString('BYMONTHDAY', $reported[0], 'The reason is passed on.');
    }

    /**
     * @param  string  $title  Event title.
     * @param  string  $rrule  Recurrence rule to store.
     *
     * @return Event Saved event.
     */
    protected function create_event(string $title, string $rrule): Event
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'  => OSEC_POST_TYPE,
            'post_title' => $title,
        ]);
        $start   = new DT('2026-07-06 09:00:00', 'UTC');
        $end     = new DT('2026-07-06 10:00:00', 'UTC');

        return new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => $start,
                'end'              => $end,
                'allday'           => 0,
                'timezone_name'    => 'UTC',
                'recurrence_rules' => $rrule,
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        );
    }
}
