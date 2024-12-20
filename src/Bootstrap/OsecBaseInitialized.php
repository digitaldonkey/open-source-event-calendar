<?php

namespace Osec\Bootstrap;

/**
 * Base application model.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Frontend
 * @replaces Ai1ec_App
 */
class OsecBaseInitialized extends OsecBaseClass
{
    /**
     * Initiate base objects.
     *
     * @param  App  $app
     *
     * @internal param App $system Injectable system object.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->initialize();
    }

    /**
     * Post construction routine.
     *
     * Override this method to perform post-construction tasks.
     *
     * @return void Return from this method is ignored.
     */
    protected function initialize()
    {
    }
}
