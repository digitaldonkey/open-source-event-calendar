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
        // A browser must not read the feed as HTML.
        header('X-Content-Type-Options: nosniff');
        $this->render_calendar($params['data']);
        ResponseHelper::stop();
    }

    /**
     * Output the serialized calendar without changing its text or line folding.
     *
     * The text is iCalendar, escaped by iCalcreator for that format, not HTML: running it through wp_kses() turned
     * every "&" into "&amp;" in titles, locations, contacts and categories of every subscribed calendar. The HTML
     * description (X-ALT-DESC) is filtered before serialization, in IcsImportExportParser.
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
