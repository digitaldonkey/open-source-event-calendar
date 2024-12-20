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
    private array $translations = [];

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
        $this->message    = $message;
        $this->recipients = $recipients;
    }

    /**
     * @param  array  $translations  Translations.
     */
    public function set_translations(array $translations)
    {
        $this->translations = $translations;
    }

    public function send()
    {
        $this->parseText();

        return wp_mail($this->recipients, $this->_subject, $this->message);
    }

    private function parseText()
    {
        $this->message  = strtr($this->message, $this->translations);
        $this->_subject = strtr($this->_subject, $this->translations);
    }
}
