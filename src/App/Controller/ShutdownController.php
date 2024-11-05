<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Controller that handles shutdown functions.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Shutdown_Controller
 * @author     Time.ly Network Inc.
 */
class ShutdownController extends OsecBaseClass
{

    /**
     * @var array Map of object names and class names they represent to preserve
     */
    protected $_preserve = [
        'wpdb'            => 'wpdb',
        'wp_object_cache' => 'WP_Object_Cache',
    ];

    /**
     * @var array Map of object names and their representation from global scope
     */
    protected $_restorables = [];

    /**
     * @var array List of callbacks to be executed during shutdown sequence
     */
    protected $_callbacks = [];

    /**
     * Constructor
     *
     * Here global variables are referenced locally to ensure their preservation
     *
     * @return void Constructor does not return
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        foreach ($this->_preserve as $name => $class) {
            $this->_restorables[ $name ] = $GLOBALS[ $name ];
        }
    }

    /**
     * Destructor
     *
     * Here processing of globals is made - values are replaced, callbacks
     * are executed and globals are restored to the previous state.
     *
     * @return void Destructor does not return
     */
    public function __destruct()
    {
        // replace globals from our internal store
        $restore = [];
        foreach ($this->_preserve as $name => $class) {
            if (
                ! isset($GLOBALS[ $name ]) ||
                ! ($GLOBALS[ $name ] instanceof $class)
            ) {
                $restore[ $name ] = null;
                if (isset($GLOBALS[ $name ])) {
                    $restore[ $name ] = $GLOBALS[ $name ];
                }
                $GLOBALS[ $name ] = $this->_restorables[ $name ];
            }
        }
        // execute callbacks
        foreach ($this->_callbacks as $callback) {
            call_user_func($callback);
        }
        // restore globals to previous state
        foreach ($restore as $name => $object) {
            if (null === $object) {
                unset($GLOBALS[ $name ]);
            } else {
                $GLOBALS[ $name ] = $object;
            }
        }
        // destroy local references
        foreach ($this->_restorables as $name => $object) {
            unset($object, $this->_restorables[ $name ]);
        }
        if (OSEC_DEBUG) {
            // __destruct is called twice if facebook extension is installed
            // still can't find the reason, this fixes it but prevent other plugins
            // __destruct() so let's just use it in dev until we fix this.
            exit(0);
        }
    }

    /**
     * Register a callback to be executed during shutdown sequence
     *
     * @param  callback  $callback  Valid PHP callback
     *
     * @return ShutdownController Self instance for chaining
     */
    public function register($callback)
    {
        $this->_callbacks[] = $callback;

        return $this;
    }

}
