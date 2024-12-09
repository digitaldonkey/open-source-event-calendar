<?php

namespace Osec\Exception;

/**
 * Exceptions occuring during bootstrap
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Bootstrap_Exception
 * @author     Time.ly Network Inc.
 */
class BootstrapException extends Exception
{
    public function get_html_message()
    {
        return '<p>Failure in Open Source Event Calendar core:<br />' .
               $this->getMessage() . '</p>';
    }
}
