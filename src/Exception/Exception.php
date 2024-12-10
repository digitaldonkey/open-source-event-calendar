<?php

namespace Osec\Exception;

/**
 * Abstract base class for all our excpetion.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Exception
 * @author     Time.ly Network Inc.
 */
class Exception extends \Exception
{
    /**
     * A message to be displayed for admin
     *
     * Specific Exceptions should override this.
     *
     * @return string Message to be displayed for admin
     */
    public function get_html_message()
    {
        return $this->getMessage();
    }

    /**
     * Return the èath of the plugin to disable it.
     * If empty it disables core.
     *
     * @return string
     */
    public function plugin_to_disable()
    {
        return '';
    }

    /**
     * Returns destination URL if exception handler redirects.
     *
     * @return string Result.
     */
    public function get_redirect_url()
    {
        return get_admin_url();
    }

    /**
     * Defined whether exception handler should attach backtrace or not.
     *
     * @return bool Value.
     */
    public function display_backtrace()
    {
        return true;
    }
}
