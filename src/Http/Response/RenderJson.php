<?php

namespace Osec\Http\Response;

/**
 * Render the request as json.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Json
 * @author     Time.ly Network Inc.
 */
class RenderJson extends RenderJsonP
{

    /* (non-PHPdoc)
     * @see RenderStrategyAbstract::render()
     */
    public function render(array $params) : void
    {
        $params[ 'callback' ] = '';
        parent::render($params);
    }
}
