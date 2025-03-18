<?php

namespace Osec\Http\Response;

/**
 * Render the request as jsonp.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Jsonp
 * @author     Time.ly Network Inc.
 */
class RenderJsonP extends RenderStrategyAbstract
{
    /*
    (non-PHPdoc)
     * @see RenderStrategyAbstract::render()
     */
    public function render(array $params): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $this->cleanOutputBuffers();
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json; charset=UTF-8');
        $data   = ResponseHelper::utf8($params['data']);
        $output = wp_json_encode($data);
        if ( ! empty($params['callback'])) {
            $output = $params['callback'] . '(' . $output . ')';
        // phpcs:disable WordPress.Security.NonceVerification
        } elseif (isset($_GET['callback'])) {
            $output = sanitize_key($_GET['callback']) . '(' . $output . ')';
        }
        // phpcs:enable
        // No way to escape this html/JS mix here.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
        ResponseHelper::stop();
    }
}
