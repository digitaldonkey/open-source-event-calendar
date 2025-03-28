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
        $this->assertFalse(Event::is_geo_value(0));
        $this->assertFalse(Event::is_geo_value('0'));
    }
}
