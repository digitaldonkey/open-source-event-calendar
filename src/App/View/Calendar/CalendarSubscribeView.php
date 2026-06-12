<?php

namespace Osec\App\View\Calendar;

use Osec\App\WpmlHelper;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Request\Request;
use Osec\Http\Request\RequestParser;
use Osec\Theme\ThemeLoader;

/**
 * Generate translation entities for subscription buttons.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_View_Calendar_SubscribeButton
 */
class CalendarSubscribeView extends OsecBaseClass
{
    /**
     * Render the HTML for the `subscribe' section.
     *
     * @param  array  $view_args  Args to pass.
     *
     * @return string Rendered HTML to include in output.
     */
    public function render_subscribe(string $url_args, $is_filtered)
    {
        if (! RequestParser::factory($this->app)->get('display_subscribe')) {
            return '';
        }

        $httx_url = str_replace(
            'webcal://',
            Request::get_protocoll() . '://',
            OSEC_EXPORT_URL
        );

        $download_url = $httx_url . '&download_true';

        if (! str_contains($url_args, '&lang=')) {
            $use_lang = WpmlHelper::factory($this->app)->get_language();
            if (!is_null($use_lang)) {
                $url_args = '&lang=' . $use_lang;
            }
        }

        $args = array_merge(
            $this->get_labels(),
            [
                'is_filtered'             => $is_filtered,
                'url_webcal'              => esc_url_raw(OSEC_EXPORT_URL . $url_args),
                'url_webcal_no_html'      => esc_url_raw(OSEC_EXPORT_URL . '&no_html=true' . $url_args),
                'url_httx'                => esc_url_raw($httx_url . $url_args),
                'url_httx_no_html'        => esc_url_raw($httx_url . '&no_html=true' . $url_args),
                'url_download'            => esc_url_raw($download_url . $url_args),
                'url_download_no_html'    => esc_url_raw($download_url . '&no_html=true' . $url_args),
            ]
        );

        /**
         * Subscribe buttons alter
         *
         * Alter arguments for subscribe-buttons.twig template
         *
         * @since 1.0
         *
         * @param  array  $args  Twig args
         */
        $args = apply_filters('osec_subscribe_buttons_arguments', $args);
        return ThemeLoader::factory($this->app)
                          ->get_file('subscribe-buttons.twig', $args, false)
                          ->get_content();
    }
    /**
     * Get a list of texts for subscribtion buttons.
     *
     * @return array Map of labels.
     */
    public function get_labels()
    {
        return [
            'subscribe_text' => [
                'filtered'  => __('Subscribe to filtered calendar', 'open-source-event-calendar'),
                'unfiltered' => __('Subscribe', 'open-source-event-calendar'),
            ],
            'label' => [
                'google'    => __('Add to Google', 'open-source-event-calendar'),
                'outlook'   => __('Add to Outlook', 'open-source-event-calendar'),
                'apple'     => __('Add to Apple Calendar', 'open-source-event-calendar'),
                'plaintext' => __('Add to other calendar', 'open-source-event-calendar'),
                'download'  => __('Download ics', 'open-source-event-calendar'),
            ],
            'title' => [
                'google'    => __('Subscribe to this calendar in your Google Calendar', 'open-source-event-calendar'),
                'outlook'   => __('Subscribe to this calendar in MS Outlook', 'open-source-event-calendar'),
                'apple'     => __('Subscribe to this calendar in Apple Calendar/iCal', 'open-source-event-calendar'),
                'plaintext' => __(
                    'Subscribe to this calendar in another calendar',
                    'open-source-event-calendar'
                ),
                'download' => __('Download ICS file', 'open-source-event-calendar'),
            ],
        ];
    }
}
