<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Controller\AppendContentController;
use Osec\App\Controller\BlockController;
use Osec\App\View\Event\EventContentView;
use Osec\Tests\Utilities\TestBase;
use RuntimeException;
use WP_Block_Type_Registry;
use WP_Post;

/**
 * Event content must be filtered in the event's own post context (#59).
 *
 * @group osec
 */
class EventContentFilterTest extends TestBase
{
    private const CALENDAR_BLOCK = 'open-source-event-calendar/osec-calendar-classic';

    private int $page_id;
    private int $event_id;

    protected function setUp(): void
    {
        parent::setUp();
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', false);
        $osec_app->settings->set('feature_shortcodes', true);
        $this->page_id  = self::factory()->post->create([
            'post_type'    => 'page',
            'post_content' => 'PAGE_BODY',
        ]);
        $this->event_id = self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_content' => 'EVENT_BODY',
        ]);
        $this->set_global_post(get_post($this->page_id));
    }

    protected function tearDown(): void
    {
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', false);
        parent::tearDown();
    }

    private function set_global_post(?WP_Post $value): void
    {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['post'] = $value;
    }

    private function content_view(): EventContentView
    {
        global $osec_app;
        // Fresh instance: no memoized content from other tests.
        return new EventContentView($osec_app);
    }

    /**
     * Event post as returned by EventSearch: stdClass with all wp_posts columns.
     */
    private function event_row(): object
    {
        return (object) get_object_vars(get_post($this->event_id));
    }

    /**
     * Simulates builders like Elementor or SiteOrigin: the output is the layout of the current post.
     */
    private function add_builder_filter(): void
    {
        add_filter(
            'the_content',
            fn($content) => get_post()->post_content === 'PAGE_BODY' ? 'PAGE_LAYOUT' : $content,
            9
        );
    }

    public function test_builder_keyed_on_current_post_renders_event_content()
    {
        $this->add_builder_filter();

        $content = $this->content_view()->get_filtered_content($this->event_row());

        $this->assertStringContainsString('EVENT_BODY', $content);
        $this->assertStringNotContainsString('PAGE_LAYOUT', $content);
    }

    public function test_get_the_content_inside_loop_matches_event()
    {
        // Calendar rendered inside the page loop: the_post already fired for the page.
        setup_postdata(get_post($this->page_id));
        $seen = null;
        add_filter('the_content', function ($content) use (&$seen) {
            $seen = get_the_ID() . ':' . get_the_content();
            return $content;
        });

        $this->content_view()->get_filtered_content($this->event_row());

        $this->assertSame($this->event_id . ':EVENT_BODY', $seen);
        $this->assertSame($this->page_id . ':PAGE_BODY', get_the_ID() . ':' . get_the_content());
    }

    public function test_the_post_fires_once_per_event()
    {
        setup_postdata(get_post($this->page_id));
        $fired = 0;
        add_action('the_post', function () use (&$fired) {
            $fired++;
        });

        $this->content_view()->get_filtered_content($this->event_row());

        $this->assertSame(1, $fired);
    }

    public function test_postdata_globals_restored_without_previous_post()
    {
        $this->set_global_post(null);
        unset($GLOBALS['pages'], $GLOBALS['id']);

        $this->content_view()->get_filtered_content($this->event_row());

        $this->assertNull($GLOBALS['post']);
        $this->assertArrayNotHasKey('pages', $GLOBALS);
        $this->assertArrayNotHasKey('id', $GLOBALS);
    }

    public function test_state_restored_when_filter_throws()
    {
        global $osec_app;
        $append = AppendContentController::factory($osec_app);
        $append->set_append_content(true);
        $view = $this->content_view();
        add_filter('the_content', function () {
            throw new RuntimeException('boom');
        });

        try {
            $view->get_filtered_content($this->event_row());
            $this->fail('Exception expected.');
        } catch (RuntimeException) {
            $this->assertSame($this->page_id, get_the_ID());
            $this->assertTrue($append->append_content());
            $this->assertFalse($view->is_filtering_content());
        }
    }

    public function test_append_content_disabled_while_filtering_and_restored()
    {
        global $osec_app;
        $append = AppendContentController::factory($osec_app);
        $append->set_append_content(true);
        $seen = null;
        add_filter('the_content', function ($content) use (&$seen, $append) {
            $seen = $append->append_content();
            return $content;
        });

        $this->content_view()->get_filtered_content($this->event_row());

        $this->assertFalse($seen);
        $this->assertTrue($append->append_content());
    }

    public function test_memoized_per_post_and_content()
    {
        $calls = 0;
        add_filter('the_content', function ($content) use (&$calls) {
            $calls++;
            return $content;
        });
        $view = $this->content_view();

        $view->get_filtered_content($this->event_row());
        $view->get_filtered_content($this->event_row());
        $this->assertSame(1, $calls, 'Same post and content is filtered once.');

        $changed               = $this->event_row();
        $changed->post_content = 'EVENT_BODY <img data-ai1ec-hidden src="x.png">';
        $view->get_filtered_content($changed);
        $this->assertSame(2, $calls, 'Changed content is filtered again.');
    }

    public function test_strict_mode_uses_strict_list_without_touching_the_content_hook()
    {
        global $osec_app, $wp_filter;
        $osec_app->settings->set('strict_compatibility_content_filtering', true);
        $this->add_builder_filter();
        $hook      = $wp_filter['the_content'];
        $callbacks = $hook->callbacks;

        $content = $this->content_view()->get_filtered_content($this->event_row());

        $this->assertSame("<p>EVENT_BODY</p>\n", $content);
        $this->assertSame($hook, $wp_filter['the_content']);
        $this->assertSame($callbacks, $wp_filter['the_content']->callbacks);
    }

    public function test_strict_mode_inside_outer_the_content_keeps_outer_chain()
    {
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', true);
        $view  = $this->content_view();
        $row   = $this->event_row();
        $inner = null;
        add_filter('the_content', fn($content) => $content . '|P5', 5);
        add_filter('the_content', function ($content) use ($view, $row, &$inner) {
            // Like a calendar shortcode rendered by do_shortcode at priority 11.
            $inner = $view->get_filtered_content($row);
            return $content . '|P11';
        }, 11);
        add_filter('the_content', fn($content) => $content . '|P20', 20);

        $outer = apply_filters('the_content', 'page');

        $this->assertStringNotContainsString('|P', $inner);
        $this->assertStringContainsString('|P20', $outer);
        $this->assertStringContainsString('|P20', apply_filters('the_content', 'later'));
        $this->assertNotFalse(has_filter('the_content', 'do_blocks'));
    }

    public function test_strict_filter_list_is_filterable()
    {
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', true);
        add_filter('osec_event_the_content_strict_filters', fn() => ['strtoupper']);

        $this->assertSame('EVENT_BODY', $this->content_view()->get_filtered_content($this->event_row()));
    }

    public function test_strict_mode_not_applied_when_disallowed()
    {
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', true);
        add_filter('the_content', fn($content) => $content . '|THIRD_PARTY');

        $content = $this->content_view()->get_filtered_content($this->event_row(), false);

        $this->assertStringContainsString('|THIRD_PARTY', $content);
    }

    public function test_calendar_shortcode_renders_nothing_inside_event_content()
    {
        global $osec_app;
        $this->set_global_post(null);
        $event_id = self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_content' => 'BEFORE [' . OSEC_SHORTCODE . ' view="agenda"] AFTER',
        ]);

        $content = EventContentView::factory($osec_app)->get_filtered_content(get_post($event_id));

        $this->assertStringContainsString('BEFORE', $content);
        $this->assertStringContainsString('AFTER', $content);
        $this->assertStringNotContainsString('[' . OSEC_SHORTCODE, $content);
        $this->assertStringNotContainsString('ai1ec-calendar', $content);
    }

    public function test_calendar_block_renders_nothing_inside_event_content()
    {
        global $osec_app;
        if (! WP_Block_Type_Registry::get_instance()->is_registered(self::CALENDAR_BLOCK)) {
            BlockController::factory($osec_app)->registerCalendarBlock();
        }
        $this->set_global_post(null);
        $event_id = self::factory()->post->create([
            'post_type'    => OSEC_POST_TYPE,
            'post_content' => '<!-- wp:paragraph --><p>BEFORE</p><!-- /wp:paragraph -->'
                . '<!-- wp:' . self::CALENDAR_BLOCK . ' {"view":"agenda","taxonomies":[],"postIds":[]} /-->',
        ]);

        $content = EventContentView::factory($osec_app)->get_filtered_content(get_post($event_id));

        $this->assertStringContainsString('BEFORE', $content);
        $this->assertStringNotContainsString('wp-block-open-source-event-calendar', $content);
    }

    public function test_is_filtering_content_only_during_filtering()
    {
        $view   = $this->content_view();
        $during = null;
        add_filter('the_content', function ($content) use ($view, &$during) {
            $during = $view->is_filtering_content();
            return $content;
        });

        $this->assertFalse($view->is_filtering_content());
        $view->get_filtered_content($this->event_row());

        $this->assertTrue($during);
        $this->assertFalse($view->is_filtering_content());
    }
}
