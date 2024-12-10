<?php

namespace Osec\Http\Response;

/**
 * Render the request as ical.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Ical
 * @author     Time.ly Network Inc.
 */
class RenderIcal extends RenderStrategyAbstract
{
    public function render(array $params)
    {
        $this->_dump_buffers();
        header('Content-type: text/calendar; charset=utf-8');
        echo $params['data'];
        ResponseHelper::stop();
    }
}
