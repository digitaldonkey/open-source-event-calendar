<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FrontendCssController;
use Osec\Tests\Utilities\CssEngineTrait;
use Osec\Tests\Utilities\TestBase;

/**
 * Where the compiled CSS lands (D4, C3).
 *
 * A page that will show a calendar gets it in <head> (wp_enqueue_scripts). A calendar found only while the content
 * renders, after wp_head (e.g. a classic theme with a shortcode in a widget), gets it with the footer styles.
 * Never nowhere: the inline variant used to add_action('wp_head') after wp_head had run.
 *
 * @group css
 */
class CssEnqueueTest extends TestBase
{
    use CssEngineTrait;

    private const BLOCK = '<!-- wp:open-source-event-calendar/osec-calendar-classic /-->';

    private mixed $saved_as_link;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_as_link = $osec_app->settings->get('render_css_as_link');
    }

    public function tear_down()
    {
        global $osec_app;

        $this->reset_css_engine();
        $osec_app->settings->set('render_css_as_link', $this->saved_as_link);
        $GLOBALS['wp_styles'] = null;
        parent::tear_down();
        $this->commit_css_cleanup();
    }

    public static function pages(): array
    {
        return [
            'shortcode'      => ['post', '[' . OSEC_SHORTCODE . ']'],
            'block'          => ['post', self::BLOCK],
            'single event'   => [OSEC_POST_TYPE, 'An event.'],
            'calendar page'  => ['calendar', ''],
        ];
    }

    /**
     * @dataProvider pages
     */
    public function test_link_in_head_when_the_page_shows_a_calendar(string $type, string $content)
    {
        $this->as_link();
        $this->visit($type, $content);

        [$head, $footer] = $this->render(true);

        $this->assertSame('head', $this->where($head, $footer, 'osec-compiled-'));
    }

    /**
     * @dataProvider pages
     */
    public function test_inline_style_in_head_when_the_page_shows_a_calendar(string $type, string $content)
    {
        $this->as_inline();
        $this->visit($type, $content);

        [$head, $footer] = $this->render(true);

        $this->assertSame('head', $this->where($head, $footer, 'osec-frontend-css-inline-css'));
        $this->assertSame(1, substr_count($head . $footer, 'body{color:red}'), 'printed once');
    }

    public function test_link_in_footer_when_found_only_while_rendering()
    {
        $this->as_link();
        $this->visit('post', 'No calendar here.');

        [$head, $footer] = $this->render(true);

        $this->assertSame('footer', $this->where($head, $footer, 'osec-compiled-'));
    }

    public function test_inline_style_in_footer_when_found_only_while_rendering()
    {
        $this->as_inline();
        $this->visit('post', 'No calendar here.');

        [$head, $footer] = $this->render(true);

        $this->assertSame('footer', $this->where($head, $footer, 'osec-frontend-css-inline-css'));
    }

    public function test_no_css_on_pages_without_a_calendar()
    {
        $this->as_link();
        $this->visit('post', 'No calendar here.');

        [$head, $footer] = $this->render(false);

        $this->assertSame('nowhere', $this->where($head, $footer, 'osec-compiled-'));
    }

    private function as_link(): void
    {
        global $osec_app;

        $osec_app->settings->set('render_css_as_link', true);
        $this->use_css_engine('file');
        FrontendCssController::factory($osec_app)->update_persistence_layer('body{color:red}');
    }

    /**
     * The inline variant: CSS not in a file, render_css_as_link off.
     */
    private function as_inline(): void
    {
        global $osec_app;

        $osec_app->settings->set('render_css_as_link', false);
        $this->use_css_engine('db');
        FrontendCssController::factory($osec_app)->update_persistence_layer('body{color:red}');
    }

    private function visit(string $type, string $content): void
    {
        global $osec_app;

        $post_id = 'calendar' === $type
            ? (int) $osec_app->settings->get('calendar_page_id')
            : self::factory()->post->create(
                ['post_type' => $type, 'post_content' => $content, 'post_status' => 'publish']
            );
        $this->go_to(get_permalink($post_id));
        $GLOBALS['wp_styles'] = null;
    }

    /**
     * wp_head, then the content (a calendar rendered by a shortcode, block or widget, as in a classic theme), then
     * wp_footer.
     *
     * @return string[] Head and footer output.
     */
    private function render(bool $calendar_in_content): array
    {
        global $osec_app;

        ob_start();
        do_action('wp_head');
        $head = ob_get_clean();
        if ($calendar_in_content) {
            // What the shortcode, block and page views call while rendering.
            FrontendCssController::factory($osec_app)->add_link_to_html_for_frontend();
        }
        ob_start();
        do_action('wp_footer');
        $footer = ob_get_clean();

        return [$head, $footer];
    }

    private function where(string $head, string $footer, string $needle): string
    {
        $in_head   = str_contains($head, $needle);
        $in_footer = str_contains($footer, $needle);
        if ($in_head && $in_footer) {
            return 'both';
        }

        return $in_head ? 'head' : ($in_footer ? 'footer' : 'nowhere');
    }
}
