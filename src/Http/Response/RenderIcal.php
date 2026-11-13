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
        $this->render_calendar($params['data']);
        ResponseHelper::stop();
    }

    /**
     * Output the serialized calendar without changing its text or line folding.
     *
     * @param string $str Calendar with HTML sanitized before serialization.
     *
     * @return void
     */
    protected function render_calendar(string $str): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is text/calendar; HTML was sanitized before serialization.
        echo $str;
    }
}
