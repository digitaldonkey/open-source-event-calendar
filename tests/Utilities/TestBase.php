<?php

namespace Osec\Tests\Utilities;

// sys_get_temp_dir() . /wordpress-tests-lib/includes/abstract-testcase.php
use Osec\Cache\CacheMemory;
use ReflectionClass;
use WP_UnitTestCase_Base;

/**
 * Base test.
 *
 * @group osec
 */
abstract class TestBase extends WP_UnitTestCase_Base
{
    /**
     * A configured calendar page is a setup requirement; without it links are built without a base page.
     */
    public function set_up()
    {
        parent::set_up();
        static::set_calendar_page();
    }

    /**
     * Creates a calendar page and sets it like a saved settings page would.
     *
     * Posts are deleted after each test class, so the page is created per test.
     *
     * @return int Calendar page ID.
     */
    protected static function set_calendar_page(): int
    {
        global $osec_app;

        $page_id = static::factory()->post->create(
            [
                'post_type'   => 'page',
                'post_title'  => 'Calendar',
                'post_status' => 'publish',
            ]
        );
        $osec_app->settings->set('calendar_page_id', $page_id);
        // Same as BootstrapController::initialize_router(), which only runs once on init.
        CacheMemory::factory($osec_app)->set('calendar_base_page', get_page_link($page_id));

        return $page_id;
    }

    protected static function getPrivateMethod($class, $name) {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        return $method;
    }
}
