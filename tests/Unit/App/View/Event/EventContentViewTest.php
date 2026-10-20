<?php

namespace Osec\Tests\Unit\App\View\Event;

use Osec\App\View\Event\EventContentView;
use Osec\Tests\Utilities\TestBase;

/**
 * EventContentView::get_excerpt(): an event without content has an empty excerpt (it was '&nbsp;', an empty line
 * in the event list's Extended view and an empty box in the popup).
 *
 * @group osec
 */
class EventContentViewTest extends TestBase
{
    public function test_no_content_gives_an_empty_excerpt()
    {
        global $osec_app;
        $post = get_post(self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_content' => '',
            'post_excerpt' => '',
        ]));

        $this->assertSame('', EventContentView::factory($osec_app)->get_excerpt($post));
    }

    public function test_content_gives_its_excerpt()
    {
        global $osec_app;
        $post = get_post(self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_content' => '<p>Bring a <strong>blanket</strong>.</p>',
        ]));

        $this->assertSame('Bring a blanket.', EventContentView::factory($osec_app)->get_excerpt($post));
    }
}
