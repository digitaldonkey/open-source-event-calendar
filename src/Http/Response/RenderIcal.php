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
        $this->render_with_doctype($params['data']);
        ResponseHelper::stop();
    }

    /**
     * Prints the iCalendar text, with the HTML description's doctype in place of its placeholder.
     *
     * The text is iCalendar, escaped by iCalcreator for that format, not HTML: running it through wp_kses() turned
     * every "&" into "&amp;" in titles, locations, contacts and categories of every subscribed calendar.
     *
     * @param  string  $str  The iCalendar text.
     *
     * @return void
     */
    protected function render_with_doctype(string $str): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/calendar, escaped by iCalcreator.
        echo str_replace('###DOCTYPE_PLACEHOLDER###', '<!DOCTYPE html>', $str);
    }
}
