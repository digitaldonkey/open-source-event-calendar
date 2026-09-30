<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\BigCalendarBlockController;
use Osec\App\Controller\ClassicBlockController;
use Osec\Tests\Utilities\TestBase;
use WP_Block_Type_Registry;

/**
 * The blocks' editor strings are translatable.
 */
class BlockTranslationsTest extends TestBase
{
    private const BLOCKS = [
        'open-source-event-calendar/osec-calendar-classic' => ClassicBlockController::class,
        'open-source-event-calendar/react-big-calendar'    => BigCalendarBlockController::class,
    ];

    public function test_block_scripts_load_the_plugin_text_domain()
    {
        global $osec_app;
        $registry = WP_Block_Type_Registry::get_instance();
        foreach (self::BLOCKS as $name => $controller) {
            if (! $registry->is_registered($name)) {
                $controller::factory($osec_app)->registerCalendarBlock();
            }
            $block   = $registry->get_registered($name);
            $handles = array_merge($block->editor_script_handles, $block->view_script_handles);
            $this->assertNotEmpty($handles, $name);
            foreach ($handles as $handle) {
                $this->assertSame(
                    'open-source-event-calendar',
                    wp_scripts()->registered[$handle]->textdomain,
                    $handle
                );
            }
        }
    }

    public function test_block_build_uses_only_the_plugin_text_domain()
    {
        $this->assertStringContainsString(
            '"open-source-event-calendar"',
            file_get_contents(OSEC_PATH . 'blocks/build/classic/index.js')
        );
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(OSEC_PATH . 'blocks/build'));
        foreach ($files as $file) {
            if ($file->isFile() && 'js' === $file->getExtension()) {
                $this->assertStringNotContainsString(
                    'open source-event-calendar',
                    file_get_contents($file->getPathname()),
                    $file->getPathname()
                );
            }
        }
    }
}
