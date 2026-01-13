<?php

namespace Osec\Exception;

/**
 * In case of database update failure this exception is thrown
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Database_Error
 * @author     Time.ly Network Inc.
 */
class DatabaseErrorException extends Exception
{
    /**
     * Override parent method to include tip.
     *
     * @return string Message to render.
     */
    public function get_html_message()
    {
        $message = '<p>' . __(
            'Database update has failed. Please make sure, that database user, defined in <em>wp-config.php</em> 
                has permissions, to make changes (<strong>ALTER TABLE</strong>) to the database.',
            'open-source-event-calendar'
        ) . '</p>'
        . '<p>' . sprintf(
            /* translators: Database exception error message */
            __('Error encountered: %s', 'open-source-event-calendar'),
            $this->getMessage()
        ) . '</p>';

        return $message;
    }
}
