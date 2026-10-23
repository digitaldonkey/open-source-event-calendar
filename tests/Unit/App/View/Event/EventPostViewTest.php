<?php

namespace Osec\Tests\Unit\App\View\Event;

use Osec\App\View\Event\EventPostView;
use Osec\Tests\Utilities\TestBase;

/**
 * The admin notices after saving an event (post_updated_messages) carry working links.
 *
 * @group event
 */
class EventPostViewTest extends TestBase
{
    /**
     * Message 9 ("Event scheduled for") had href=">%2$s", so its "Preview event" link pointed to ">https://...".
     */
    public function test_every_link_points_to_the_event()
    {
        global $osec_app, $post, $post_ID;

        $post_ID = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => 'future',
            'post_date' => '2030-01-01 10:00:00']);
        $post    = get_post($post_ID);

        $messages = EventPostView::factory($osec_app)->post_updated_messages([])[OSEC_POST_TYPE];

        $this->assertStringContainsString('Event scheduled for', $messages[9]);
        foreach ($messages as $index => $message) {
            preg_match_all('/href="([^"]*)"/', (string) $message, $links);
            foreach ($links[1] as $href) {
                $this->assertStringStartsWith('http', $href, "Message $index links to \"$href\".");
            }
        }
    }
}
