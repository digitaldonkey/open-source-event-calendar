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
        $this->cleanOutputBuffers();
        header('Content-type: text/calendar; charset=utf-8');
        echo wp_kses(
            $params['data'],
            $this->app->kses->allowed_html_frontend()
        );
        ResponseHelper::stop();
    }
}
