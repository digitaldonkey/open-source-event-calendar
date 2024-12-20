<?php

namespace Osec\Http\Response;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Abstract strategy class to render the Request.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Http_Response_Render_Strategy
 * @author     Time.ly Network Inc.
 */
abstract class RenderStrategyAbstract extends OsecBaseClass
{
    /**
     * Render the output.
     */
    abstract public function render(array $params);

    /**
     * Dump output buffers before starting output
     *
     * @return bool True unless an error occurs
     */
    protected function cleanOutputBuffers()
    {
        $this->app->db->disable_debug();
        $success = true;
        while (ob_get_level()) {
            $success = ob_end_flush();
        }
        return $success;
    }
}
