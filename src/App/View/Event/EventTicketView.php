<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\OsecBaseClass;

/**
 * This class renders the html for the event ticket.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Ticket
 */
class EventTicketView extends OsecBaseClass
{
    /**
     * Create readable content for buy tickets/register link
     *
     * @param  bool  $long  Set to false to use short message version
     *
     * @return string Message to be rendered on buy tickets link
     */
    public function get_tickets_url_label(Event $event, $long = true)
    {
        if ($event->is_free()) {
            return ($long)
                ? __('Register Now', 'open-source-event-calendar')
                : __('Register', 'open-source-event-calendar');
        }
        $output = '';
        if ($long) {
            /**
             * Alter buy tickes icon html
             *
             * @since 1.0
             *
             * @param  string  $html  Html do display a ticket icon.
             */
            $output = apply_filters('osec_buy_tickets_url_icon', '<i class="ai1ec-fa ai1ec-fa-shopping-cart"></i>');
            if ( ! empty($output)) {
                $output .= ' ';
            }
        }
        $output .= ($long)
            ? __('Buy Tickets', 'open-source-event-calendar')
            : __('Tickets', 'open-source-event-calendar');

        return $output;
    }

    /**
     * Contact info as HTML
     */
    public function get_contact_html(Event $event)
    {
        $contact      = '<div class="h-card">';
        $has_contents = false;
        if ($event->get('contact_name')) {
            $contact      .=
                '<div class="ai1ec-contact-name p-name">' .
                '<i class="ai1ec-fa ai1ec-fa-fw ai1ec-fa-user"></i> ' .
                esc_html($event->get('contact_name')) .
                '</div> ';
            $has_contents = true;
        }
        if ($event->get('contact_phone')) {
            $contact      .=
                '<div class="ai1ec-contact-phone p-tel">' .
                '<i class="ai1ec-fa ai1ec-fa-fw ai1ec-fa-phone"></i> ' .
                esc_html($event->get('contact_phone')) .
                '</div> ';
            $has_contents = true;
        }
        if ($event->get('contact_email')) {
            $contact      .=
                '<div class="ai1ec-contact-email">' .
                '<a class="u-email" href="mailto:' .
                esc_attr($event->get('contact_email')) . '">' .
                '<i class="ai1ec-fa ai1ec-fa-fw ai1ec-fa-envelope-o"></i> ' .
                __('Email', 'open-source-event-calendar') . '</a></div> ';
            $has_contents = true;
        }
        $contact_url = $event->get('contact_url');
        if ($contact_url) {
            $contact      .=
                '<div class="ai1ec-contact-url">' .
                '<a class="u-url" target="_blank" href="' .
                esc_attr($contact_url) .
                '"><i class="ai1ec-fa ai1ec-fa-fw ai1ec-fa-link"></i> ' .

                /**
                 * Alter contact_url label
                 *
                 * Visible Event single if ´Organizer contact info´
                 * -> Website URL is set.
                 *
                 * @since 1.0
                 *
                 * @param  string  $contact_url  Url in use.
                 *
                 * @param  string  $event_website_link  Multilingual label for the link.
                 */
                apply_filters('osec_contact_url_link', __('Event website', 'open-source-event-calendar'), $contact_url)
                . ' <i class="ai1ec-fa ai1ec-fa-external-link"></i></a></div>';
            $has_contents = true;
        }
        $contact .= '</div>';

        return $has_contents ? $contact : '';
    }
}
