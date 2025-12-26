<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\WpmlHelper;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Theme\ThemeLoader;

/**
 * This class renders the html for the event location.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Location
 */
class EventLocationView extends OsecBaseClass
{
    /**
     * Return location details in brief format, separated by | characters.
     *
     * @param  Event  $event
     *
     * @return string $string Short location string
     */
    public function get_short_location(Event $event)
    {
        $location_items = [];
        foreach (['venue', 'city', 'province', 'country'] as $field) {
            if ($event->get($field) && is_string($event->get($field))) {
                $location_items[] = $event->get($field);
            }
        }

        return implode(' | ', $location_items);
    }

    /*
     * Return any available location details separated by newlines
    */
    public function get_location(Event $event)
    {
        $location = '';
        $venue    = $event->get('venue');
        if ($venue) {
            $location .= $venue . "\n";
        }
        $address = $event->get('address');
        if ($address) {
            $bits = explode(',', (string)$address);
            $bits = array_map('trim', $bits);

            // If more than three comma-separated values, treat first value as
            // the street address, last value as the country, and everything
            // in the middle as the city, state, etc.
            if (count($bits) >= 3) {
                // Append the street address
                $street_address = array_shift($bits) . "\n";
                if ($street_address) {
                    $location .= $street_address;
                }
                // Save the country for the last line
                $country = array_pop($bits);
                // Append the middle bit(s) (filtering out any zero-length strings)
                $bits = array_filter($bits, 'strval');
                if ($bits) {
                    $location .= implode(', ', $bits) . "\n";
                }
                if ($country) {
                    $location .= $country . "\n";
                }
            } else {
                // There are two or less comma-separated values, so just append
                // them each on their own line (filtering out any zero-length strings)
                $bits     = array_filter($bits, 'strval');
                $location .= implode("\n", $bits);
            }
        }

        return $location;
    }

    /**
     * get_map_view function
     *
     * Returns HTML markup displaying a Google map of the given event, if the event
     * has show_map set to true. Returns a zero-length string otherwise.
     *
     * @return string
     **/
    public function get_map_view(Event $event): string
    {
        $settings = $this->app->settings;
        if ( ! $event->get('show_map')) {
            return '';
        }

        $location = $this->get_latlng($event);
        if ( ! $location) {
            $location = $event->get('address');
        }

        $args = [
            'address'                 => $location,
            'gmap_url_link'           => $this->get_gmap_url($event),
            'hide_maps_until_clicked' => $settings->get('hide_maps_until_clicked'),
            'text_view_map'           => __('Click to view map', 'open-source-event-calendar'),
            'text_full_map'           => __('View Full-Size Map', 'open-source-event-calendar'),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('event-map.twig', $args, false)
                          ->get_content();
    }

    /**
     * Returns the latitude/longitude coordinates as a textual string
     * parsable by the Geocoder API.
     *
     * @param  Event  $event  The event to return data from
     *
     * @return string              The latitude & longitude string, or null
     */
    public function get_latlng(Event $event)
    {
        // If the coordinates are set, use those, otherwise use the address.
        $location = null;
        // If the coordinates are set by hand use them.
        if ($event->get('show_coordinates')) {
            $longitude = floatval($event->get('longitude'));
            $latitude  = floatval($event->get('latitude'));
            $location  = $latitude . ',' . $longitude;
        }

        return $location;
    }

    /**
     * Returns the URL to the Google Map for the given event object.
     *
     * @param  Event  $event
     *    The event object to display a map for
     *
     * @return string
     * @throws BootstrapException
     */
    public function get_gmap_url(Event $event)
    {
        $lang     = WpmlHelper::factory($this->app)->get_language();
        $location = $this->get_latlng($event);
        if ( ! $location) {
            $location = $event->get('address');
        }

        return 'https://www.google.com/maps?f=q&hl=' . rawurlencode((string)$lang) .
               '&source=embed&q=' . rawurlencode((string)$location);
    }
}
