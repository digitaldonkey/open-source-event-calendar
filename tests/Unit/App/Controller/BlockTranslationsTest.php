<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\BlockController;
use Osec\Tests\Utilities\TestBase;
use WP_Block_Type_Registry;

/**
 * The calendar block's editor strings are translatable.
 */
class BlockTranslationsTest extends TestBase
{
    public function test_editor_script_loads_the_plugin_text_domain()
    {
        global $osec_app;
        if (! WP_Block_Type_Registry::get_instance()->is_registered('open-source-event-calendar/osec-calendar-classic')) {
            BlockController::factory($osec_app)->registerCalendarBlock();
        }

        $this->assertSame(
            'open-source-event-calendar',
            wp_scripts()->registered['osec-calendar-block-classic']->textdomain
        );
    }

    public function test_block_build_uses_only_the_plugin_text_domain()
    {
        $build = file_get_contents(OSEC_PATH . 'calendar_block/build/index.js');

        $this->assertStringContainsString('"open-source-event-calendar"', $build);
        $this->assertStringNotContainsString('open source-event-calendar', $build);
    }
}
