<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
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
            $bits = explode(',', (string) $address);
            $bits = array_map('trim', $bits);
            $location .= implode("\n", $bits);
        }
        return nl2br($location);
    }

    /**
     * get_map_view function
     *
     * Returns HTML markup displaying a Google map of the given event, if the event
     * has show_map set to true. Returns a zero-length string otherwise.
     *
     * @return string
     **/
    public function get_map_public_view(Event $event): string
    {
        if ( ! $event->get('show_map')) {
            return '';
        }

        $location = $this->get_latlng($event);
        if ( ! $location) {
            $location = $event->get('address');
        }

        $args = [
            'text_full_map_gmap'           => __('View on Google maps', 'open-source-event-calendar'),
            'gmap_url_link'           => esc_url($this->get_gmap_url($event)), // Deprecated, legacy.
            'text_full_map_osm'           => __('View on OpenStreetMap', 'open-source-event-calendar'),
            'osm_link_url'            => esc_url($this->get_osm_url($event)),
            'hide_maps_until_clicked' => $this->app->settings->get('hide_maps_until_clicked'),
            'text_view_map'           => __('Click to view map', 'open-source-event-calendar'),
            'height'                  => '20em',
            'data'                    => [
                'venue'                   => esc_attr($event->get('venue')),
                'address'                 => esc_attr($event->get('address')),
                'lat'                     => floatval($event->get('latitude')),
                'long'                    => floatval($event->get('longitude')),
                'maxzoom'                 => intval($this->app->settings->get('location_maps_max_zoom')),
                'zoom'                    => intval($this->app->settings->get('location_maps_zoom')),
                'attribution'             => esc_attr(
                    '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                ),
            ],
        ];
        /**
         * Alter maps data before rendering.
         *
         * @since 1.0
         *
         * @param  array  $args  Debug or not.
         */
        $args = apply_filters('osec_event_map_public_alter', $args);
        return ThemeLoader::factory($this->app)
                          ->get_file('event-map-public.twig', $args, false)
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
        if (!$this->app->settings->get('display_gmap_link')) {
            return '';
        }
        $location = $event->get('address');
        $url = 'https://www.google.com//maps/search/?api=1&iwloc=addr&query=' . rawurlencode((string)$location);

        /**
         * Alter google maps link
         *
         * @since 1.1
         *
         * @param  string  $url  Url.
         *
         * @param  array  $event  Event.
         */
        $url = apply_filters('osec_gmaps_link_alter', $url, $event);
        return esc_url($url);
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
    public function get_osm_url(Event $event)
    {
        if (!$this->app->settings->get('display_osm_link')) {
            return '';
        }

        $lat = $event->get('latitude');
        $long = $event->get('longitude');
        $zoom = '15';
        $url = "https://www.openstreetmap.org/?mlat=$lat&mlon=$long#map=$zoom/$lat/$long";

        /**
         * Alter OpenStreetMaps link
         *
         * @since 1.1
         *
         * @param  string  $url  Url.
         *
         * @param  array  $event  Event.
         */
        $url = apply_filters('osec_osm_link_alter', $url, $event);
        return esc_url($url);
    }
}
