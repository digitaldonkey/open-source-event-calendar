<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\ClassicBlockController;
use Osec\Tests\Utilities\TestBase;
use WP_Block_Type_Registry;

/**
 * The calendar block's "Display print icon" setting (display_print).
 */
class BlockPrintButtonTest extends TestBase
{
    private const CALENDAR_BLOCK = 'open-source-event-calendar/osec-calendar-classic';

    private bool $display_print_button;

    public function set_up()
    {
        parent::set_up();
        global $osec_app;
        $this->display_print_button = (bool) $osec_app->settings->get('display_print_button');
        $osec_app->settings->set('display_print_button', true);
        if (! WP_Block_Type_Registry::get_instance()->is_registered(self::CALENDAR_BLOCK)) {
            ClassicBlockController::factory($osec_app)->registerCalendarBlock();
        }
    }

    public function tear_down()
    {
        global $osec_app;
        $osec_app->settings->set('display_print_button', $this->display_print_button);
        parent::tear_down();
    }

    private function render(string $attributes): string
    {
        return do_blocks('<!-- wp:' . self::CALENDAR_BLOCK . ' ' . $attributes . ' /-->');
    }

    public function test_print_button_shown_by_default()
    {
        $this->assertStringContainsString('ai1ec-print-buttons', $this->render('{}'));
    }

    public function test_block_hides_print_button_and_keeps_it_hidden_when_paging()
    {
        foreach (['agenda', 'month'] as $view) {
            $html = $this->render('{"view":"' . $view . '","displayPrint":false}');
            $this->assertStringNotContainsString('ai1ec-print-buttons', $html, $view);
            $this->assertStringContainsString('display_print~false', $html, $view);
        }
    }

    public function test_setting_off_hides_print_button_in_block()
    {
        global $osec_app;
        $osec_app->settings->set('display_print_button', false);
        $this->assertStringNotContainsString(
            'ai1ec-print-buttons',
            $this->render('{"displayPrint":true}')
        );
    }
}
