<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Theme\ThemeLoader;

/**
 * This class renders the html for the event ticket.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Ticket
 */
class EventContactView extends OsecBaseClass
{
    /**
     * Contact info as HTML
     * @throws BootstrapException
     * @throws Exception
     */
    public function get_contact_html(Event $event)
    {
        $data = [
            'contact_name'  => esc_html($event->get('contact_name')),
            'contact_phone' => esc_html($event->get('contact_phone')),
            'contact_email' => esc_attr($event->get('contact_email')),
            'contact_url'   => esc_attr($event->get('contact_url')),
        ];
        $has_data = !empty(array_filter($data));
        $args = array_merge($data, [
            'has_data' => $has_data,
            'contact_email_text' => esc_html__('Email', 'open-source-event-calendar'),
            'contact_url_text' => esc_html__('Event website', 'open-source-event-calendar'),
        ]);

        /**
         * Alter or add contact data before render
         *
         * Visible Event single ´Organizer contact info´
         *
         * @since 1.0
         *
         * @param string  $args  Args in use.
         * @param bool $has_data Must be true to render
         * @param Event $event
         *
         * @return array
         */
        $args = apply_filters('osec_contact_url_link', $args, $has_data, $event);
        if ($args['has_data']) {
            return ThemeLoader::factory($this->app)
                          ->get_file('event-contact.twig', $args)
                          ->get_content();
        }
        return '';
    }
}
