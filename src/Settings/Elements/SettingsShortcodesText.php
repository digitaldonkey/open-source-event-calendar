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

    public function render($html = '', $wrap = true) : string
    {
        $file = $this->_args[ 'id' ].'.twig';
        $method = 'get_'.$this->_args[ 'id' ].'_args';
        $args = [];
        if (method_exists($this, $method)) {
            $args = $this->{$method}();
        }
        $file = ThemeLoader::factory($this->app)->get_file('setting/'.$file, $args, true);

        return $this->warp_in_form_group($file->get_content());
    }

    /*
     * Get embedding arguments
     *
     * @return array
     */
    protected function get_embedding_args()
    {
        $args = [
			'base_shortcode' => OSEC_SHORTCODE,
            'viewing_events_shortcodes'     => '',
            'text_embed_shortcode'          => __('Embed the calendar using a shortcode', OSEC_TXT_DOM),
            'text_insert_shortcode'         => __('Use these shortcodes into your page body to embed the calendar into any arbitrary WordPress Page:', OSEC_TXT_DOM),
            'text_month_view'               => __('Month view:', OSEC_TXT_DOM),
            'text_week_view'                => __('Week view:', OSEC_TXT_DOM),
            'text_day_view'                 => __('Day view:', OSEC_TXT_DOM),
            'text_agenda_view'              => __('Agenda view:', OSEC_TXT_DOM),
            'text_other_view'               => __('Some Other view:', OSEC_TXT_DOM),
            'text_default_view'             => __('Default view as per settings:', OSEC_TXT_DOM),
            'text_general_form'             => __('General form:', OSEC_TXT_DOM),
            'text_optional'                 => __('Optional.', OSEC_TXT_DOM),
            'text_filter_label'             => __('Add options to display a filtered calender. (You can find out category and tag IDs by inspecting the URL of your filtered calendar page.)', OSEC_TXT_DOM),
            'text_filter_category'          => __('Filter by event category name/slug:', OSEC_TXT_DOM),
            'text_filter_category_1'        => __('Holidays', OSEC_TXT_DOM),
            'text_filter_category_2'        => __('Lunar Cycles', OSEC_TXT_DOM),
            'text_filter_category_3'        => __('zodiac-date-ranges', OSEC_TXT_DOM),
            'text_filter_category_comma'    => __('Filter by event category names/slugs (separate names by comma):', OSEC_TXT_DOM),
            'text_filter_category_id'       => __('Filter by event category ID:', OSEC_TXT_DOM),
            'text_filter_category_id_comma' => __('Filter by event category IDs (separate IDs by comma):', OSEC_TXT_DOM),
            'text_filter_tag'               => __('Filter by event tag name/slug:', OSEC_TXT_DOM),
            'text_filter_tag_1'             => __('tips-and-tricks', OSEC_TXT_DOM),
            'text_filter_tag_2'             => __('creative writing', OSEC_TXT_DOM),
            'text_filter_tag_3'             => __('performing arts', OSEC_TXT_DOM),
            'text_filter_tag_comma'         => __('Filter by event tag names/slugs (separate names by comma):', OSEC_TXT_DOM),
            'text_filter_tag_id'            => __('Filter by event tag ID:', OSEC_TXT_DOM),
            'text_filter_tag_id_comma'      => __('Filter by event tag IDs (separate IDs by comma):', OSEC_TXT_DOM),
            'text_filter_post_id'           => __('Filter by post ID:', OSEC_TXT_DOM),
            'text_filter_post_id_comma'     => __('Filter by post IDs (separate IDs by comma):', OSEC_TXT_DOM),
            'text_events_limit'             => __('Limit number of events per page:', OSEC_TXT_DOM),
            'text_warning'                  => __('Warning:', OSEC_TXT_DOM),
            'text_single_calendar'          => __('It is currently not supported to embed more than one calendar in the same page. Do not attempt to embed the calendar via shortcode in a page that already displays the calendar.', OSEC_TXT_DOM),
        ];

        /**
         * Allows to alter translated strings in Shortcode embed text.
         *
         * All strings should be translated in current language.
         *
         * @since 1.0
         *
         * @param  array  $args  Translated text to alter.
         *
         */
        return apply_filters('osec_viewing_events_shortcodes_alter', $args);
    }

}
