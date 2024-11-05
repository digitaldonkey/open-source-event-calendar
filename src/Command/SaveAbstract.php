<?php

namespace Osec\Command;

use Osec\Bootstrap\App;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderRedirect;

/**
 * The abstract command that save something in the admin.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Save_Abstract
 * @author     Time.ly Network Inc.
 */
abstract class SaveAbstract extends CommandAbstract
{

    protected $_controller = 'front';

    protected $_action;

    protected $_nonce_name;

    protected $_nonce_action;

    /**
     * Public constructor, set the strategy according to the type.
     *
     * @param  App  $app
     * @param  RequestParser  $request
     * @param  array  $args
     */
    public function __construct(App $app, RequestParser $request, array $args)
    {
        parent::__construct($app, $request);
        if ( ! is_array($args[ 'action' ])) {
            $args[ 'action' ] = [$args[ 'action' ] => true];
        }
        $this->_action = $args[ 'action' ];
        $this->_nonce_action = $args[ 'nonce_action' ];
        $this->_nonce_name = $args[ 'nonce_name' ];
    }

    public function is_this_to_execute()
    {
        $params = $this->get_parameters();
        if (false === $params) {
            return false;
        }
        if ($params[ 'controller' ] === $this->_controller &&
            isset($this->_action[ $params[ 'action' ] ])) {
            $pass = wp_verify_nonce(
                $_POST[ $this->_nonce_name ],
                $this->_nonce_action
            );
            if ( ! $pass) {
                wp_die("Failed security check");
            }

            return true;
        }

        return false;
    }

    public function set_render_strategy(RequestParser $request)
    {
        $this->_render_strategy = RenderRedirect::factory($this->app);
    }
}
