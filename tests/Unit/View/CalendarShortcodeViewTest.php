<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\CalendarShortcodeView;
use Osec\Tests\Utilities\TestBase;

/**
 * Tests for [osec] shortcode attribute handling.
 *
 * Renders the shortcode and captures the view arguments (including the
 * parsed RequestParser) via the osec_calendar_view_args_alter filter to
 * assert which taxonomy filters actually reach the calendar query.
 *
 * @group shortcode
 */
class CalendarShortcodeViewTest extends TestBase
{
    private array $captured_view_args = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->captured_view_args = [];
        add_filter('osec_calendar_view_args_alter', function ($view_args) {
            $this->captured_view_args = $view_args;
            return $view_args;
        });
    }

    /**
     * Render the shortcode and return the captured view args.
     */
    private function render_shortcode(array $atts): array
    {
        global $osec_app;

        CalendarShortcodeView::factory($osec_app)->shortcode($atts);
        $this->assertNotEmpty(
            $this->captured_view_args,
            'osec_calendar_view_args_alter did not fire'
        );

        return $this->captured_view_args;
    }

    /**
     * Register a custom event taxonomy plus the request parser rule an
     * add-on would provide for it.
     */
    private function register_venue_taxonomy(): void
    {
        register_taxonomy('venue', [OSEC_POST_TYPE]);
        add_action('osec_request_parser_rules_added', function ($parser) {
            $parser->add_rule('osec_venue_ids', false, 'int', null, ',');
        });
    }

    public function test_cat_name_and_tag_name_land_in_request()
    {
        $cat_id = self::factory()->term->create(
            [
            'taxonomy' => 'osec_events_categories',
            'name' => 'Aktivitet',
            ]
        );
        $tag_id = self::factory()->term->create(
            [
            'taxonomy' => 'osec_events_tags',
            'name' => 'Music',
            ]
        );

        $view_args = $this->render_shortcode(
            [
            'cat_name' => 'Aktivitet',
            'tag_name' => 'Music',
            ]
        );

        $this->assertSame([$cat_id], $view_args['cat_ids']);
        $this->assertSame([$tag_id], $view_args['tag_ids']);
    }

    public function test_cat_id_and_tag_id_land_in_request()
    {
        $cat_id = self::factory()->term->create(
            [
            'taxonomy' => 'osec_events_categories',
            'name' => 'Aktivitet',
            ]
        );
        $tag_id = self::factory()->term->create(
            [
            'taxonomy' => 'osec_events_tags',
            'name' => 'Music',
            ]
        );

        $view_args = $this->render_shortcode(
            [
            'cat_id' => (string) $cat_id,
            'tag_id' => (string) $tag_id,
            ]
        );

        $this->assertSame([$cat_id], $view_args['cat_ids']);
        $this->assertSame([$tag_id], $view_args['tag_ids']);
    }

    public function test_name_and_id_values_accumulate()
    {
        $cat_a = self::factory()->term->create(
            [
            'taxonomy' => 'osec_events_categories',
            'name' => 'Aktivitet',
            ]
        );
        $cat_b = self::factory()->term->create(
            [
            'taxonomy' => 'osec_events_categories',
            'name' => 'Mote',
            ]
        );

        $view_args = $this->render_shortcode(
            [
            'cat_name' => 'Aktivitet',
            'cat_id' => (string) $cat_b,
            ]
        );

        $this->assertEqualsCanonicalizing([$cat_a, $cat_b], $view_args['cat_ids']);
    }

    public function test_unresolvable_cat_name_warns_and_is_dropped()
    {
        $this->setExpectedIncorrectUsage(CalendarShortcodeView::class . '::shortcode');

        $view_args = $this->render_shortcode(['cat_name' => 'Doesnotexist']);

        $this->assertSame([], $view_args['cat_ids']);
    }

    public function test_non_positive_tag_id_warns_and_is_dropped()
    {
        $this->setExpectedIncorrectUsage(CalendarShortcodeView::class . '::shortcode');

        $view_args = $this->render_shortcode(['tag_id' => '0']);

        $this->assertSame([], $view_args['tag_ids']);
    }

    public function test_custom_taxonomy_filter_lands_in_request()
    {
        $this->register_venue_taxonomy();
        $venue_id = self::factory()->term->create(
            [
            'taxonomy' => 'venue',
            'name' => 'Grand Hall',
            ]
        );

        $view_args = $this->render_shortcode(['venue_name' => 'Grand Hall']);

        $this->assertSame([$venue_id], $view_args['request']->get('osec_venue_ids'));
    }

    public function test_unresolvable_custom_taxonomy_warns_without_fatal()
    {
        $this->register_venue_taxonomy();
        $this->setExpectedIncorrectUsage(CalendarShortcodeView::class . '::shortcode');

        $view_args = $this->render_shortcode(['venue_name' => 'Nowhere']);

        $this->assertSame([], $view_args['request']->get('osec_venue_ids'));
    }

    public function test_events_limit_passes_through_without_warning()
    {
        // No setExpectedIncorrectUsage() here: an unexpected
        // _doing_it_wrong() fails the test.
        $view_args = $this->render_shortcode(['events_limit' => '3']);

        $this->assertSame(3, $view_args['events_limit']);
    }

    public function test_non_numeric_events_limit_stays_silent()
    {
        // A non-numeric limit degrades to an empty value (pre-existing
        // behavior); the point of this test is that events_limit is never
        // treated as a taxonomy filter, so no term lookup happens and no
        // warning is emitted.
        $view_args = $this->render_shortcode(['events_limit' => 'abc']);

        $this->assertEmpty($view_args['events_limit']);
    }
}
