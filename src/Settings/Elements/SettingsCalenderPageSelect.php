<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page Calendar page selection snippet.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Element_Calendar_Page_Selector
 */
class SettingsCalenderPageSelect extends SettingsAbstract
{
    /**
     * @var string HTML id attribute for selector.
     */
    public const ELEMENT_ID = 'calendar_page_id';

    /**
     * @var array Map of pages defined in system, use `get_pages()` WP call.
     */
    protected $pages = [];

    /**
     * Generate HTML snippet for inclusion in settings page.
     *
     * @param  string  $html
     * @param  bool  $wrap
     *
     * @return string HTML snippet for page selection.
     */
    public function render($html = '', $wrap = true): string
    {
        $output = '<label class="ai1ec-control-label ai1ec-col-sm-3" for="' .
                  self::ELEMENT_ID . '">' . __('Calendar page', 'open-source-event-calendar') . '</label>'
                  . '<div class="ai1ec-col-sm-6">' .
                  $this->getPageSelector() . $this->getPageViewLink() . '</div>';

        return $this->warp_in_form_group($output);
    }

    /**
     * Generate dropdown selector to choose page.
     *
     * @return string HTML snippet.
     */
    protected function getPageSelector()
    {
        $html = '<select id="' . self::ELEMENT_ID .
                '" class="ai1ec-form-control" name="' . self::ELEMENT_ID . '">';
        $list = $this->getPages();
        foreach ($list as $key => $value) {
            $html .= '<option value="' . esc_attr($key) . '"';
            if ($this->args['value'] === $key) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . esc_html($value) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Make a map of page IDs and titles for selection snippet.
     *
     * @return array Map of page keys and titles.
     */
    protected function getPages()
    {
        $pages = get_pages();
        if ( ! is_array($pages)) {
            $pages = [];
        }
        $output = [
            '__auto_page:Calendar' => __(
                '- Auto-Create New Page -',
                'open-source-event-calendar'
            ),
        ];
        foreach ($pages as $key => $value) {
            $output[$value->ID] = $value->post_title;
        }

        return $output;
    }

    /**
     * Generate link to open selected page in new window.
     *
     * @return string HTML snippet.
     */
    protected function getPageViewLink()
    {
        if (empty($this->args['value'])) {
            return '';
        }
        $post = get_post($this->args['value']);
        if (empty($post->ID)) {
            return '';
        }
        $args = [
            'view'  => __('View', 'open-source-event-calendar'),
            'link'  => get_permalink($post->ID),
            'title' => apply_filters(
                'the_title',
                $post->post_title,
                $post->ID
            ),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('setting/calendar-page-selector.twig', $args, true)
                          ->get_content();
    }
}
