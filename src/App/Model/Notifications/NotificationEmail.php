<?php

namespace Osec\App\Model\Notifications;

use Osec\Bootstrap\App;

/**
 * Concrete implementation for email notifications.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Notifications
 * @replaces Ai1ec_Email_Notification
 */
class NotificationEmail extends NotificationAbstract
{

    // TODO This class is not in use. Remove?

    /**
     * @var array
     */
    private $_translations = [];

    /**
     * @param  App  $app
     * @param $message
     * @param  array  $recipients
     * @param $_subject
     */
    public function __construct(
        App $app,
        $message,
        array $recipients,
        private $_subject
    ) {
        parent::__construct($app);
        $this->_message = $message;
        $this->_recipients = $recipients;
    }

    /**
     * @param  array $translations Translations.
     */
    public function set_translations(array $translations)
    {
        $this->_translations = $translations;
    }

    public function send()
    {
        $this->_parse_text();

        return wp_mail($this->_recipients, $this->_subject, $this->_message);
    }

    private function _parse_text()
    {
        $this->_message = strtr($this->_message, $this->_translations);
        $this->_subject = strtr($this->_subject, $this->_translations);
    }

}
