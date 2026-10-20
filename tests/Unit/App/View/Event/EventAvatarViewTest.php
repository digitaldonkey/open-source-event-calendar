<?php

namespace Osec\Tests\Unit\App\View\Event;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\TaxonomyAdapter;
use Osec\App\View\Event\EventAvatarView;
use Osec\Tests\Utilities\TestBase;

/**
 * EventAvatarView: the avatar's alt and src are escaped once (T7: "D&amp;amp;D Night" since 1.0.7, the data was
 * escaped and the Twig template escaped it again); get_event_avatar_data() keeps returning escaped values. The
 * calendar shows an event's category image whether or not "Use featured image fallbacks" is on (T12, as up to 1.1.8:
 * the setting only makes it the WordPress featured image).
 *
 * @group osec
 */
class EventAvatarViewTest extends TestBase
{
    private const TITLE = 'D&D Night "quoted" & <b>not bold</b>';

    private const IMAGE = 'https://example.org/image.png?a=1&b=2';

    private const CATEGORY_IMAGE = 'https://example.org/category.png';

    private ?TaxonomyAdapter $saved_adapter = null;

    public function tear_down()
    {
        global $osec_app;

        if ($this->saved_adapter) {
            $osec_app->inject_object(TaxonomyAdapter::class, $this->saved_adapter);
        }
        parent::tear_down();
    }

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

    public function test_category_image_shows_with_the_featured_image_fallback_off()
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'  => OSEC_POST_TYPE,
            'post_title' => 'Category only',
        ]);
        $term_id = self::factory()->term->create(['taxonomy' => 'osec_events_categories']);
        wp_set_object_terms($post_id, [$term_id], 'osec_events_categories');
        // The category's image, as stored in the categories' meta table (cached once per request, hence a double).
        $this->saved_adapter = TaxonomyAdapter::factory($osec_app);
        $osec_app->inject_object(TaxonomyAdapter::class, new class ($osec_app, $term_id) extends TaxonomyAdapter {
            public function __construct($app, private int $term_id)
            {
                parent::__construct($app);
            }

            public function get_category_image($term_id)
            {
                return $this->term_id === (int) $term_id ? 'https://example.org/category.png' : null;
            }
        });
        // "Use featured image fallbacks" off: its post_thumbnail_id filter is not hooked.
        remove_all_filters('post_thumbnail_id');

        // A new instance: its per-post caches must not hold an earlier test's post of the same ID (SQLite).
        $html = (new EventAvatarView($osec_app))->get_event_avatar($this->stored_event($post_id));

        $this->assertStringContainsString('ai1ec-category_avatar', $html);
        $this->assertStringContainsString('src="' . self::CATEGORY_IMAGE . '"', $html);
    }

    /**
     * An event of a stored post, without venue.
     *
     * @param  int  $post_id
     *
     * @return Event
     */
    private function stored_event(int $post_id): Event
    {
        global $osec_app;

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
