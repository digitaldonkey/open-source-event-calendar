<?php

namespace Osec\Bootstrap;

use Osec\App\Controller\DatabaseController;
use Osec\App\Model\Options;
use Osec\App\Model\Settings;
use Osec\App\View\KsesHelper;
use Osec\Exception\BootstrapException;
use ReflectionClass;

/**
 * Object App: get instance of requested and optionally registered object.
 *
 * Object (instance of a class) is generater, or returned from internal cache
 * if it was requested and instantiated before.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package App
 * @replaces Ai1ec_Registry_Object
 */
class App
{
    public readonly Options $options;
    public readonly Settings $settings;
    public readonly DatabaseController $db;
    public readonly KsesHelper $kses;
    /**
     * @var array Internal objects cache
     *
     * Initializing classes with *::factory(...) will make them
     * globally available here.
     * Non-global classes should be instantiated with new ClassName();
     */
    private array $globalObjects = [];

    /**
     * Initialize global App.
     *
     * @return App
     */
    public static function factory(): App
    {
        global $osec_app;
        $globaleApp = new self();
        $globaleApp->createGlobalReistryObjects($globaleApp);
        $osec_app = $globaleApp;

        return $globaleApp;
    }

    /**
     * Add some core services to the app.
     *
     *  - options: A WP-Options wrapper (DB saved)
     *  - settings: Database settings
     *  - db: Database handler.
     *
     * @throws BootstrapException
     */
    private function createGlobalReistryObjects(App $app): void
    {
        $this->options  = Options::factory($app);
        $this->settings = Settings::factory($app);
        $this->db       = DatabaseController::factory($app);
        $this->kses     = KsesHelper::factory($app);
    }

    /**
     * Allow to set previously created globally accessible class instance.
     *
     * @param  string  $name  Class name to be used.
     * @param  object  $obj  Actual instance of class above.
     *
     * @return void
     */
    public function inject_object($name, $obj)
    {
        if (! is_object($obj) || ! ($obj instanceof $name)) {
            throw new BootstrapException(
                'Attempt to inject not an object / invalid object.'
            );
        }
        $this->globalObjects[$name] = $obj;
    }

    /**
     * Global variables are now Services implementing
     *    \Osec\Bootstrap\App\ServiceInterface.
     *
     * @param  string  $name
     *
     * @return mixed|null
     */
    public function getService(string $name): mixed
    {
        if (isset($this->globalObjects[$name])) {
            return $this->globalObjects[$name];
        }

        return null;
        // So...
        // Let the factory create and regiaster $this->inject_object( $name, $object )
    }

    /**
     * Instanciate the class given the class names and arguments.
     *
     * @param  string  $namespaced_class  The name of the class to instanciate.
     * @param  array  $argv  An array of aguments for construction.
     *
     * @return object A new instance of the requested class
     */
    public function initiate($namespaced_class, array $argv = [])
    {
        switch (count($argv)) {
            case 0:
                return new $namespaced_class();

            case 1:
                return new $namespaced_class($argv[0]);

            case 2:
                return new $namespaced_class($argv[0], $argv[1]);

            case 3:
                return new $namespaced_class($argv[0], $argv[1], $argv[2]);

            case 4:
                return new $namespaced_class($argv[0], $argv[1], $argv[2], $argv[3]);

            case 5:
                return new $namespaced_class($argv[0], $argv[1], $argv[2], $argv[3], $argv[4]);
        }
        $reflected = new ReflectionClass($namespaced_class);

        return $reflected->newInstanceArgs($argv);
    }
}
