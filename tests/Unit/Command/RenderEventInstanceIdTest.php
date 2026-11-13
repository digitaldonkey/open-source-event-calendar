<?php

namespace Osec\Tests\Unit\Command;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Command\RenderEvent;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * The event page reads instance_id from the URL, where a visitor or crawler can write anything.
 */
class RenderEventInstanceIdTest extends TestBase
{
    private array $saved_request = [];

    public function set_up()
    {
        parent::set_up();
        $this->saved_request = $_REQUEST;
    }

    public function tear_down()
    {
        $_REQUEST = $this->saved_request;
        parent::tear_down();
    }

    /**
     * @dataProvider instance_ids
     *
     * @param  string  $instance_id  Value of the instance_id URL parameter.
     */
    public function test_event_page_renders_with_any_instance_id(string $instance_id)
    {
        global $osec_app;

        $post_id = (new Event(
            $osec_app,
            [
                'post'          => ['post_status' => 'publish', 'post_type' => OSEC_POST_TYPE, 'post_title' => 'Event'],
                'start'         => new DT('2026-07-06 09:00:00', 'UTC'),
                'end'           => new DT('2026-07-06 10:00:00', 'UTC'),
                'allday'        => false,
                'instant_event' => false,
                'timezone_name' => 'UTC',
            ]
        ))->save();
        $this->go_to(get_permalink($post_id));
        $_REQUEST['instance_id'] = $instance_id;

        $request = new RequestParser($osec_app, [], 'agenda');
        $request->parse();
        $command = new RenderEvent($osec_app, $request);
        $command->setRenderStrategy($request);
        $result = $command->do_execute();

        self::assertTrue($result['is_event']);
        self::assertStringContainsString('Event', $result['data']);
    }

    public static function instance_ids(): array
    {
        return [
            'number'             => ['1'],
            'unknown number'     => ['999999'],
            'number, then text'  => ['123junk'],
            'text'               => ['junk'],
            'negative number'    => ['-5'],
            'zero'               => ['0'],
            'empty'              => [''],
        ];
    }
}
