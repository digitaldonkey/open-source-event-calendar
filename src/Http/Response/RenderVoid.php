<?php

namespace Osec\Http\Response;

/**
 * Do not render anything.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Void
 * @author     Time.ly Network Inc.
 */
class RenderVoid extends RenderStrategyAbstract
{
    public function render(array $params)
    {
    }
}
