<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * B12: a plain cost that is also valid JSON ("10", "0", "true") was taken for the stored aggregate
 * and failed with "Trying to access array offset on int" - from the editor and from a feed's X-COST.
 *
 * @group event
 */
class EventCostTest extends TestBase
{
    public static function costs(): array
    {
        return [
            'number'       => ['10', '10', false],
            'zero'         => ['0', '0', true],
            'decimal'      => ['12.50', '12.50', false],
            'json true'    => ['true', 'true', false],
            'json string'  => ['"x"', '"x"', false],
            'text'         => ['5 EUR', '5 EUR', false],
            'empty'        => ['', '', true],
        ];
    }

    /**
     * @dataProvider costs
     */
    public function test_plain_cost(string $input, string $cost, bool $is_free)
    {
        global $osec_app;

        $event = new Event($osec_app, ['cost' => $input]);

        $this->assertSame($cost, $event->get('cost'));
        $this->assertSame($is_free, $event->get('is_free'));
    }

    /**
     * @dataProvider costs
     */
    public function test_cost_survives_save_and_load(string $input, string $cost, bool $is_free)
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event($osec_app, [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT('2026-11-01 10:00:00', 'Europe/Berlin'),
            'end'              => new DT('2026-11-01 11:00:00', 'Europe/Berlin'),
            'allday'           => 0,
            'timezone_name'    => 'Europe/Berlin',
            'recurrence_rules' => '',
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => '',
            'cost'             => $input,
        ]))->save(false);

        $event = new Event($osec_app, $post_id);
        $this->assertSame($cost, $event->get('cost'));
        $this->assertSame($is_free, $event->get('is_free'));
    }
}
