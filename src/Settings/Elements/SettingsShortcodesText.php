<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page html.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Html
 */
class SettingsShortcodesText extends SettingsAbstract
{
    public function render($html = '', $wrap = true): string
    {
        $file = ThemeLoader::factory($this->app)->get_file(
            'setting/shortcodes.twig',
            $this->getShortcodesArgs(),
            true
        );

        return $this->warp_in_form_group($file->get_content());
    }

    /*
     * Get embedding arguments
     *
     * @return array
     */
    protected function getShortcodesArgs()
    {
        $args = [
            'base_shortcode'                => OSEC_SHORTCODE,
            'viewing_events_shortcodes'     => '',
            'text_embed_shortcode'          => __('Embed the calendar using a shortcode', 'open-source-event-calendar'),
            'text_insert_shortcode'         => __(
                'Use these shortcodes into your page body to embed the calendar into any arbitrary WordPress Page:',
                'open-source-event-calendar'
            ),
            'text_view_title'               => __('Views', 'open-source-event-calendar'),
            'text_month_view'               => __('Month view:', 'open-source-event-calendar'),
            'text_week_view'                => __('Week view:', 'open-source-event-calendar'),
            'text_day_view'                 => __('Day view:', 'open-source-event-calendar'),
            'text_agenda_view'              => __('Agenda view:', 'open-source-event-calendar'),
            'text_other_view'               => __('Some Other view:', 'open-source-event-calendar'),
            'text_default_view'             => __('Default view as per settings:', 'open-source-event-calendar'),
            'text_general_form'             => __('General form:', 'open-source-event-calendar'),
            'text_filter_title'                 => __('Filters', 'open-source-event-calendar'),
            'text_optional'                 => __('Optional.', 'open-source-event-calendar'),
            'text_filter_category'          => __('Filter by event category name/slug:', 'open-source-event-calendar'),
            'text_filter_category_1'        => __('Holidays', 'open-source-event-calendar'),
            'text_filter_category_2'        => __('Lunar Cycles', 'open-source-event-calendar'),
            'text_filter_category_3'        => __('zodiac-date-ranges', 'open-source-event-calendar'),
            'text_filter_category_comma'    => __(
                'Filter by event category names/slugs (separate names by comma):',
                'open-source-event-calendar'
            ),
            'text_filter_category_id'       => __('Filter by event category ID:', 'open-source-event-calendar'),
            'text_filter_category_id_comma' => __(
                'Filter by event category IDs (separate IDs by comma):',
                'open-source-event-calendar'
            ),
            'text_filter_tag'               => __('Filter by event tag name/slug:', 'open-source-event-calendar'),
            'text_filter_tag_1'             => __('tips-and-tricks', 'open-source-event-calendar'),
            'text_filter_tag_2'             => __('creative writing', 'open-source-event-calendar'),
            'text_filter_tag_3'             => __('performing arts', 'open-source-event-calendar'),
            'text_filter_tag_comma'         => __(
                'Filter by event tag names/slugs (separate names by comma):',
                'open-source-event-calendar'
            ),
            'text_filter_tag_id'            => __('Filter by event tag ID:', 'open-source-event-calendar'),
            'text_filter_tag_id_comma'      => __(
                'Filter by event tag IDs (separate IDs by comma):',
                'open-source-event-calendar'
            ),
            'text_filter_post_id'           => __('Filter by post ID:', 'open-source-event-calendar'),
            'text_filter_post_id_comma'     => __(
                'Filter by post IDs (separate IDs by comma):',
                'open-source-event-calendar'
            ),
            'text_events_limit'             => __('Limit number of events per page:', 'open-source-event-calendar'),
            'text_warning'                  => __('Warning:', 'open-source-event-calendar'),
            'text_single_calendar'          => __(
                'It is currently not supported to embed more than one calendar in the same page. Do not attempt to 
                    embed the calendar via shortcode in a page that already displays the calendar.',
                'open-source-event-calendar'
            ),
            'text_display_options_title' => __('Display options', 'open-source-event-calendar'),
        ];

        /**
         * Allows to alter translated strings in Shortcode embed text.
         *
         * All strings should be translated in current language.
         *
         * @since 1.0
         *
         * @param  array  $args  Translated text to alter.
         */
        return apply_filters('osec_viewing_events_shortcodes_alter', $args);
    }
}
