<?php

/**
 * Define global functions
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 */

/**
 * Executed after initialization of Front Controller.
 *
 * @return void
 */
function osec_output_buffering_start()
{
    ob_start();
}

/**
 * Executed before script shutdown, when WP core objects are present.
 *
 * @return void
 */
function osec_output_buffering_finalize()
{
    if (ob_get_length()) {
        ob_get_clean();
    }
}
