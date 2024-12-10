<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * @group event
 * Sample test case.
 */
class EventTest extends TestBase
{
    public function test_event_defaults()
    {
        global $osec_app;
        $event  = new Event($osec_app);
        // TODO
        //. This prints Event(Entity) default values.
        // var_dump($event->prepare_store_entity());

        // $this->assertEquals($XX, $event);

        $this->assertFalse(Event::is_geo_value(0));
        $this->assertFalse(Event::is_geo_value('0'));
    }
}
