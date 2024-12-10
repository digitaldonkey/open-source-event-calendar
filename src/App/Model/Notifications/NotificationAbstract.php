<?php

namespace Osec\App\Model\Notifications;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Abstract class for notifications.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Notifications
 * @replaces Ai1ec_Notification
 */
abstract class NotificationAbstract extends OsecBaseClass
{
    /**
     * @var string The message to send.
     */
    protected $message;

    /**
     * @var array A list of recipients.
     */
    protected array $recipients = [];

    /**
     * This function performs the actual sending of the message.
     *
     * Must be implemented in child classes.
     *
     * @return bool Success.
     */
    abstract public function send();
}
