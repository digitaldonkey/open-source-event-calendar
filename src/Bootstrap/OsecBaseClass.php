<?php

namespace Osec\Bootstrap;

use Exception;
use Osec\Exception\BootstrapException;

/**
 * The base class which simply sets the registry object.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Frontend
 * @replaces Ai1ec_Base
 */
abstract class OsecBaseClass
{
    /**
     * @var App
     */
    protected App $app;

    /**
     * The contructor method.
     *
     * Stores in object injected registry object.
     *
     * @param  App  $app  Injected registry object.
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param  App  $app
     * @param  mixed|NULL  $arg1
     * @param  mixed|NULL  $arg2
     * @param  mixed|NULL  $arg3
     *
     * @throws BootstrapException
     */
    public static function factory(
        App $app,
        mixed $arg1 = null,
        mixed $arg2 = null,
        mixed $arg3 = null,
        mixed $arg4 = null
    ): static { // Supported only by PHP >= 8.x
        $classname = get_called_class();

        // Return existing object.
        $obj = $app->getService($classname);
        if ($obj && is_a($obj, $classname)) {
            // Check if we have any secondary call using
            // param extra params.
            // They would be ignored if class has already been initialized.
            // Maybe this class needs to be initialized with new?
            if ( ! is_null($arg1) || ! is_null($arg2) || ! is_null($arg3)) {
                throw new Exception(
                    'Existing registry object is initialized with additional params. ' . print_r(
                        [
                            'arg1' => $arg1,
                            'arg2' => $arg2,
                            'arg3' => $arg3,
                        ],
                        true
                    )
                );
            }

            return $obj;
        }
        // Passthrough additional args.
        if ($arg4) {
            $obj = new $classname($app, $arg1, $arg2, $arg3, $arg4);
        }
        if ($arg3) {
            $obj = new $classname($app, $arg1, $arg2, $arg3);
        } elseif ($arg2) {
            $obj = new $classname($app, $arg1, $arg2);
        } elseif ($arg1) {
            $obj = new $classname($app, $arg1);
        } else {
            $obj = new $classname($app);
        }
        $app->inject_object($classname, $obj);

        return $obj;
    }
}
