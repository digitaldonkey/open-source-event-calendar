<?php

namespace Osec\Tests\Unit\App\View\Event;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventAvatarView;
use Osec\Tests\Utilities\TestBase;

/**
 * EventAvatarView: the avatar's alt and src are escaped once (T7: "D&amp;amp;D Night" since 1.0.7, the data was
 * escaped and the Twig template escaped it again); get_event_avatar_data() keeps returning escaped values.
 *
 * @group osec
 */
class EventAvatarViewTest extends TestBase
{
    private const TITLE = 'D&D Night "quoted" & <b>not bold</b>';

    private const IMAGE = 'https://example.org/image.png?a=1&b=2';

    public function test_avatar_alt_and_src_are_escaped_once()
    {
        global $osec_app;

        $html = EventAvatarView::factory($osec_app)->get_event_avatar($this->event(), ['content_img']);

        $this->assertMatchesRegularExpression('/ alt="([^"]*)"/', $html);
        preg_match('/ alt="([^"]*)"/', $html, $alt);
        preg_match('/ src="([^"]*)"/', $html, $src);
        $this->assertSame('D&D Night "quoted" & not bold', html_entity_decode($alt[1], ENT_QUOTES));
        $this->assertSame(self::IMAGE, html_entity_decode($src[1], ENT_QUOTES));
    }

    public function test_avatar_data_stays_escaped()
    {
        global $osec_app;

        $data = EventAvatarView::factory($osec_app)->get_event_avatar_data($this->event(), ['content_img']);

        $this->assertSame(esc_attr('D&D Night "quoted" & not bold'), $data['alt']);
        $this->assertSame(esc_attr(self::IMAGE), $data['url']);
    }

    /**
     * An event whose only image is in its content (fallback content_img), without venue.
     *
     * @return Event
     */
    private function event(): Event
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_title'   => self::TITLE,
            'post_content' => '<p><img src="' . self::IMAGE . '" alt=""></p>',
        ]);

        return new Event(
            $osec_app,
            [
                'post_id'       => $post_id,
                'post'          => get_post($post_id),
                'start'         => new DT('2026-07-06 09:00:00', 'UTC'),
                'end'           => new DT('2026-07-06 10:00:00', 'UTC'),
                'allday'        => 0,
                'timezone_name' => 'UTC',
            ]
        );
    }
}
