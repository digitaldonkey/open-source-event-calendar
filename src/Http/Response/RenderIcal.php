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
        $this->render_escped_with_doctype($params['data']);
        ResponseHelper::stop();
    }

    protected function render_escped_with_doctype(string $str): void {
        // There is no way to escape the doctype so we use a static placeholder.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo str_replace(
            '###DOCTYPE_PLACEHOLDER###',
            '<!DOCTYPE html>',
            wp_kses(
                $str,
                [
                    'html' => [
                        'lang' => true,
                    ],
                    'head' => true,
                    'title' => true,
                    'body' => true,
                    'p' => true,
                    'strong' => true,
                    'em' => true,
                    'a' => [
                        'href' => true,
                        'title' => true,
                    ],
                    'br' => true,
                ]
            )
        );
    }
}
