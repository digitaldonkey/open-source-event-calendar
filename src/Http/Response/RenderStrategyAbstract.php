<?php

namespace Osec\Http\Response;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;

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
     * Dump all output buffers before starting output.
     *
     * @return bool True unless an error occurs
     * @throws Exception
     */
    protected function cleanOutputBuffers(): void
    {
        $this->app->db->disable_debug();
        $level   = ob_get_level();
        $success = true;
        while ( $level ) {
            ob_end_clean();
            $new_level = ob_get_level();
            if ($new_level === $level) {
                $success = false;
                break;
            }
            $level = $new_level;
        }
        if (!$success) {
            throw new Exception(
                'Output buffer is not empty and can not be cleaned'
            );
        }
    }
}
