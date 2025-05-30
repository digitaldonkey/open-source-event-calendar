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
    protected $controllerId = 'front';

    protected $action;

    protected string $nonceName;

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
        if ( ! is_array($args['action'])) {
            $args['action'] = [$args['action'] => true];
        }
        $this->action      = $args['action'];
        $this->nonceName   = $args['nonce_name'];
    }

    public function is_this_to_execute()
    {
        $params = $this->get_parameters();
        if (false === $params) {
            return false;
        }
        if (
            $params['controller'] === $this->controllerId
            && isset($this->action[$params['action']])
            && isset($_POST[$this->nonceName])
        ) {
            $pass = wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[$this->nonceName])),
                key($this->action)
            );
            if ( ! $pass) {
                /** @noinspection ForgottenDebugOutputInspection */
                wp_die('Failed security check');
            }

            return true;
        }

        return false;
    }

    public function setRenderStrategy(RequestParser $request): void
    {
        $this->renderStrategy = RenderRedirect::factory($this->app);
    }
}
