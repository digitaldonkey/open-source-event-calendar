<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\CalendarShortcodeView;
use Osec\Tests\Utilities\TestBase;

/**
 * The print button is rendered once in every calendar view when enabled.
 *
 * @group osec
 */
class PrintButtonTest extends TestBase
{
    protected function tearDown(): void
    {
        global $osec_app;
        $osec_app->settings->set('display_print_button', false);
        parent::tearDown();
    }

    public static function viewProvider(): array
    {
        return [
            'month'  => ['monthly'],
            'week'   => ['weekly'],
            'oneday' => ['oneday'],
            'agenda' => ['agenda'],
        ];
    }

    private function render_view(string $view, bool $print_enabled, array $atts = []): string
    {
        global $osec_app;
        $osec_app->settings->set('display_print_button', $print_enabled);

        $html = CalendarShortcodeView::factory($osec_app)->shortcode(['view' => $view] + $atts);
        $this->assertIsString($html);

        return $html;
    }

    /**
     * @dataProvider viewProvider
     */
    public function test_print_button_rendered_once_when_enabled(string $view)
    {
        $html = $this->render_view($view, true);

        $this->assertSame(1, substr_count($html, 'id="ai1ec-print-button"'));
        $this->assertStringContainsString('aria-label="Print"', $html);
    }

    /**
     * @dataProvider viewProvider
     */
    public function test_no_print_button_when_disabled(string $view)
    {
        $html = $this->render_view($view, false);

        $this->assertStringNotContainsString('ai1ec-print-button', $html);
    }

    /**
     * @dataProvider viewProvider
     */
    public function test_print_button_ignores_navigation_attributes(string $view)
    {
        $atts = [
            'display_date_navigation' => 'false',
            'display_view_switch'     => 'false',
        ];
        $html = $this->render_view($view, true, $atts);

        $this->assertSame(1, substr_count($html, 'id="ai1ec-print-button"'));
    }
}
