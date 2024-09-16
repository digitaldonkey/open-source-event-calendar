<?php

/**
 * Define global functions
 *
 * @author     Time.ly Network Inc.
 * @since      2.0
 *
 * @package    AI1EC
 * @subpackage AI1EC.Lib
 */

/**
 * Executed after initialization of Front Controller.
 *
 * @return void
 */
function osec_output_buffering_start() {
	ob_start();
}

/**
 * Executed before script shutdown, when WP core objects are present.
 *
 * @return void
 */
function osec_output_buffering_finalize() {
  if (ob_get_level()) {
    echo ob_get_clean();
    ob_end_clean();
  }
}


