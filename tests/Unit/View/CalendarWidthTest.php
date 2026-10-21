<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\CalendarPageView;
use Osec\Tests\Utilities\TestBase;

/**
 * Calendar width (plan calendar-width.md): the calendar page and the shortcode carry a WordPress alignment class like a
 * block (wide by default, the shortcode's `align` attribute), a full-width calendar keeps its content off the window
 * edges (WordPress' root padding in block themes, the plugin's own padding in classic themes), and the calendar page
 * renders no calendar block or shortcode besides its own calendar.
 *
 * @group theme
 */
class CalendarWidthTest extends TestBase
{
    private const BLOCK = '<!-- wp:open-source-event-calendar/osec-calendar-classic {"view":"month"%s} /-->';

    private string $saved_stylesheet;

    public function set_up()
    {
        parent::set_up();
        $this->saved_stylesheet = get_stylesheet();
    }

    public function tear_down()
    {
        switch_theme($this->saved_stylesheet);
        parent::tear_down();
    }

    public static function alignments(): array
    {
        return [
            'none'    => ['none', ''],
            'wide'    => ['wide', 'alignwide'],
            'full'    => ['full', 'alignfull osec-edge-padding'],
            'unknown' => ['center', 'alignwide'],
        ];
    }

    /**
     * @dataProvider alignments
     */
    public function test_alignment_classes_in_a_classic_theme(string $align, string $expected)
    {
        $this->assertFalse(wp_is_block_theme());
        $this->assertSame($expected, CalendarPageView::alignment_classes($align));
    }

    public function test_full_width_uses_root_padding_in_a_block_theme()
    {
        switch_theme('twentytwentyfive');
        $this->assertTrue(wp_is_block_theme());
        $this->assertSame('alignfull has-global-padding', CalendarPageView::alignment_classes('full'));
    }

    public static function shortcodeAlignments(): array
    {
        return [
            'default'      => ['[osec view="month"]', 'alignwide'],
            'none'         => ['[osec view="month" align="none"]', ''],
            'full'         => ['[osec view="month" align="FULL"]', 'alignfull osec-edge-padding'],
            'invalid'      => ['[osec view="month" align="left"]', 'alignwide'],
        ];
    }

    /**
     * @dataProvider shortcodeAlignments
     */
    public function test_shortcode_alignment(string $shortcode, string $expected)
    {
        $this->go_to(get_permalink(self::factory()->post->create(['post_type' => 'page'])));

        $this->assertSame($expected, $this->container_alignment(do_shortcode($shortcode)));
    }

    public function test_full_width_block_wrapper_gets_edge_padding()
    {
        $this->go_to(get_permalink(self::factory()->post->create(['post_type' => 'page'])));

        $html = do_blocks(sprintf(self::BLOCK, ',"align":"full"'));

        $this->assertMatchesRegularExpression(
            '#<div class="[^"]*\bosec-edge-padding\b[^"]*\balignfull\b[^"]*wp-block-open-source-event-calendar#',
            $html
        );
        // The block's wrapper carries the alignment; the calendar inside has none of its own.
        $this->assertSame('', $this->container_alignment($html));
    }

    public function test_calendar_page_renders_no_block_or_shortcode()
    {
        global $osec_app;
        $this->go_to(get_permalink($osec_app->settings->get('calendar_page_id')));

        $this->assertSame('', do_shortcode('[osec view="month"]'));
        $this->assertSame('', trim(do_blocks(sprintf(self::BLOCK, ''))));
    }

    public function test_other_pages_render_block_and_shortcode()
    {
        $this->go_to(get_permalink(self::factory()->post->create(['post_type' => 'page'])));

        $this->assertStringContainsString('id="osec-container"', do_shortcode('[osec view="month"]'));
        $this->assertStringContainsString('id="osec-container"', do_blocks(sprintf(self::BLOCK, '')));
    }

    /**
     * Alignment classes of the calendar's #osec-container, in the order they are rendered.
     *
     * @param  string  $html  Rendered calendar.
     *
     * @return string Space-separated classes out of alignwide, alignfull, has-global-padding, osec-edge-padding.
     */
    private function container_alignment(string $html): string
    {
        $this->assertMatchesRegularExpression('#id="osec-container"\s+class="([^"]*)"#', $html);
        preg_match('#id="osec-container"\s+class="([^"]*)"#', $html, $match);

        return implode(' ', array_values(array_intersect(
            preg_split('/\s+/', $match[1]),
            ['alignwide', 'alignfull', 'has-global-padding', 'osec-edge-padding']
        )));
    }
}
