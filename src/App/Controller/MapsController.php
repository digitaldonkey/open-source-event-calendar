<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\OsecBaseClass;

/**
 * The class which handles ics feeds tab.
 *
 * @since      2.0
 *
 * @replaces Ai1ecIcsConnectorPlugin, Ai1ec_Connector_Plugin
 * @author     Time.ly Network Inc.
 */
class MapsController extends OsecBaseClass
{
    protected $default_js_params = [
        'map_height'    => '50vh',
        'map_max_zooom' => '19',
        'map_zoom'      => '16',
    ];

    public function register_assets()
    {
        if (! $this->app->settings->get('feature_event_location')) {
            return;
        }

        $this->register_leaflet();

        wp_register_script(
            'event-maps-common.js',
            OSEC_ADMIN_THEME_JS_URL . 'scripts/event-maps-common.js',
            ['leaflet'],
            OSEC_VERSION,
            ['in_footer' => true]
        );

        if (is_admin()) {
            $this->register_leaflet_geocoder();

            wp_register_script(
                'admin-box-event-map.js',
                OSEC_ADMIN_THEME_JS_URL . 'scripts/admin-box-event-map.js',
                [
                    'event-maps-common.js',
                    'leaflet-control-geocoder',
                ],
                OSEC_VERSION . time(),
                ['in_footer' => true]
            );

            // Map search biasing
            $geocodingQueryParams = null;
            if ($this->app->settings->get('geo_region_biasing')) {
                $locale = get_locale();
                if (str_contains($locale, '_')) {
                    $locale_arr = explode('_', $locale, 2);
                    $locale = mb_strtolower(end($locale_arr));
                }
                $geocodingQueryParams = ['countrycodes' => $locale];
            }
            $maps_backend_options = array_merge(
                $this->default_js_params,
                [
                    'disable_autocompletion' => $this->app->settings->get('disable_autocompletion') ? 1 : 0,
                    'placeholder' => __('Search for location', 'open-source-event-calendar'),
                    'geocodingQueryParams' => $geocodingQueryParams,
                    'errorMessage' => __('Nothing found.', 'open-source-event-calendar'),
                    'geocode_to_address_template' => OSEC_GEOCODE_TO_ADDRESS_TEMPLATE,
                ]
            );

            /**
             * Alter Leaflet map options
             *
             * Lets you alter Leaflet map options on Event Edit.
             * You may change zoom levels, limit address search
             * by changing geocodingQueryParams and how address is converted from search result to Wp-form
             * (address_template).
             * Check out console.log(osec_leaflet_admin) at Event edit page.
             * @see: https://www.liedman.net/leaflet-control-geocoder/docs/interfaces/geocoders.NominatimOptions.html
             *
             * @since 1.1
             *
             * @param  array  $maps_backend_options  Javascript options for admin-box-event-map.js.
             */
            $maps_backend_options = apply_filters('osec_maps_backend_options_alter', $maps_backend_options);
            wp_localize_script('admin-box-event-map.js', 'osec_leaflet_admin', $maps_backend_options);
        } else {
            wp_enqueue_script(
                'event-map-public.js',
                OSEC_ADMIN_THEME_JS_URL . 'scripts/event-map-public.js',
                ['event-maps-common.js'],
                OSEC_VERSION,
                ['in_footer' => true]
            );

            /**
             * Alter public Leaflet map options
             *
             * Lets you alter Leaflet map options on Event view.
             *
             * @since 1.1
             *
             * @param  array  $maps_backend_options  Javascript options event-map-public.js.
             */
            $maps_public_options = apply_filters('osec_maps_public_options_alter', $this->default_js_params);
            wp_localize_script('event-map-public.js', 'osec_leaflet', $maps_public_options);
        }
        wp_enqueue_style('leaflet-css');
    }

    protected function register_leaflet(): void
    {
        $leaflet = [
            'script' => 'https://unpkg.com/leaflet@' . OSEC_LEAFLET_VERSION . '/dist/leaflet.js',
            'style'  => 'https://unpkg.com/leaflet@' . OSEC_LEAFLET_VERSION . '/dist/leaflet.css',
        ];
        /**
         * Alter Leaflet Library.
         *
         * Note: leaflet version is defined in OSEC_LEAFLET_VERSION.
         *
         * @since 1.1
         *
         * @param  array  $leaflet Osec leaflet urls.
         *
         */
        $leaflet = apply_filters('osec_leaflet_library_alter', $leaflet);
        wp_register_script(
            'leaflet',
            $leaflet['script'],
            null,
            OSEC_VERSION,
            ['in_footer' => true]
        );
        wp_register_style(
            'leaflet-css',
            $leaflet['style'],
            [],
            OSEC_VERSION
        );
    }

    protected function register_leaflet_geocoder(): void
    {
        $leaflet_geocoder = [
            'script' => 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js',
            'style'  => OSEC_ADMIN_THEME_CSS_URL . 'control-geocoder.css',
        ];
        /**
         * Alter Leaflet geocoder library (leaflet-control-geocoder)
         *
         * @since 1.1
         *
         * @param  array  $leaflet Osec leaflet urls.
         *
         */
        $leaflet_geocoder = apply_filters('osec_leaflet_geocoder_library_alter', $leaflet_geocoder);

        wp_register_script(
            'leaflet-control-geocoder',
            $leaflet_geocoder['script'],
            null,
            OSEC_VERSION,
            ['in_footer' => true]
        );

        // Adopted custom styles
        // @see https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css
        wp_enqueue_style(
            'control-geocoder.css',
            $leaflet_geocoder['style'],
            null,
            OSEC_VERSION,
        );
    }
}
