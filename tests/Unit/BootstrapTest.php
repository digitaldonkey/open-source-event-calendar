<?php

namespace Osec\Tests\Unit;

use Closure;
use Osec\App\Controller\BootstrapController;
use Osec\Bootstrap\App;
use Osec\Tests\Utilities\TestBase;
use ReflectionFunction;

/**
 * Plugin bootstrap in open-source-event-calendar.php.
 *
 * PHPUnit loads the Composer autoloader before WordPress includes the plugin, like vendor/bin/wp.
 * The app must still be created, and only once.
 *
 * @group osec
 */
class BootstrapTest extends TestBase
{
    public function test_entry_file_registers_bootstrap_on_init_although_autoloader_is_preloaded()
    {
        $this->assertTrue(class_exists(BootstrapController::class, false));

        $entry_file = realpath(dirname(__DIR__, 2) . '/open-source-event-calendar.php');
        $found      = 0;
        foreach ($this->get_callbacks('init') as $callback) {
            if ($callback instanceof Closure && (new ReflectionFunction($callback))->getFileName() === $entry_file) {
                ++$found;
            }
        }

        $this->assertSame(1, $found);
    }

    public function test_app_is_bootstrapped_once()
    {
        global $wp_filter, $osec_app;

        $this->assertInstanceOf(App::class, $osec_app);

        $controllers = [];
        foreach (array_keys($wp_filter) as $hook) {
            foreach ($this->get_callbacks($hook) as $callback) {
                $owner = null;
                if ($callback instanceof Closure) {
                    $owner = (new ReflectionFunction($callback))->getClosureThis();
                } elseif (is_array($callback) && is_object($callback[0])) {
                    $owner = $callback[0];
                }
                if ($owner instanceof BootstrapController) {
                    $controllers[spl_object_id($owner)] = true;
                }
            }
        }

        $this->assertCount(1, $controllers);
    }

    /**
     * All callbacks registered for a hook, over all priorities.
     *
     * @param  string  $hook  Hook name.
     *
     * @return array
     */
    private function get_callbacks(string $hook): array
    {
        global $wp_filter;

        if (! isset($wp_filter[$hook])) {
            return [];
        }
        $callbacks = [];
        foreach ($wp_filter[$hook]->callbacks as $by_priority) {
            foreach ($by_priority as $registered) {
                $callbacks[] = $registered['function'];
            }
        }

        return $callbacks;
    }
}
