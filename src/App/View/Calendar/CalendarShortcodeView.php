<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\App\Model\SettingsView;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Request\RequestParser;

/**
 * The class that handles rendering the shortcode.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_View_Calendar_Shortcode
 */
class CalendarShortcodeView extends OsecBaseClass
{
    /**
     * Generate replacement content for [osec] shortcode.
     *
     * @param  array  $atts  Attributes provided on shortcode
     * @param  string  $content  Tag internal content (shall be empty)
     * @param  string  $tag  Used tag name (must be 'osec' always)
     *
     * @staticvar $call_count Used to restrict to single calendar per page
     *
     * @return string Replacement for shortcode entry
     */
    public function shortcode($atts, $content = '', $tag = OSEC_SHORTCODE)
    {
        $settings_view   = SettingsView::factory($this->app);
        $view_names_list = array_keys($settings_view->get_all());
        $default_view    = $settings_view->get_default();

        $view_names = [];
        foreach ($view_names_list as $view_name) {
            $view_names[$view_name] = true;
        }

        $view               = $default_view;
        $_events_categories = [];
        $_events_tags = [];
        $post_ids = [];

        if (isset($atts['view'])) {
            // Comes with some 'ly's attached.
            if (str_ends_with((string)$atts['view'], 'ly')) {
                $atts['view'] = substr((string)$atts['view'], 0, -2);
            }

            if ( ! isset($view_names[$atts['view']])) {
                return false;
            }
            $view = $atts['view'];
        }

        $mappings          = [
            'cat_name'     => 'osec_events_categories',
            'osec_events_categories' => 'osec_events_categories',
            'cat_id'       => 'osec_events_categories',
            'osec_events_tags'     => 'osec_events_tags',
            'tag_name'     => 'osec_events_tags',
            'tag_id'       => 'osec_events_tags',
            'post_id'      => 'post_ids',
            'events_limit' => 'events_limit',
        ];
        $matches           = [];
        $custom_taxonomies = [];
        if ( ! empty($atts)) {
            foreach ($atts as $att => $value) {
                if (
                    ! preg_match('/([a-z0-9\_]+)_(id|name)/', $att, $matches) ||
                    isset($mappings[$matches[1] . '_id'])
                ) {
                    continue;
                }
                ${'_' . $matches[1] . '_ids'} = [];
                $custom_taxonomies[]          = $matches[1];

                if ( ! isset($mappings[$matches[1] . '_id'])) {
                    $mappings[$matches[1] . '_id'] = $matches[1];
                }
                if ( ! isset($mappings[$matches[1] . '_name'])) {
                    $mappings[$matches[1] . '_name'] = $matches[1];
                }
            }
        }

        foreach ($mappings as $att_name => $type) {
            if ( ! isset($atts[$att_name])) {
                continue;
            }
            $raw_values = explode(',', (string)$atts[$att_name]);
            foreach ($raw_values as $argument) {
                if ('post_id' === $att_name) {
                    if (is_numeric($argument) && $argument > 0) {
                        $post_ids[] = $argument;
                    }
                } else {
                    if ( ! is_numeric($argument)) {
                        $search_val = trim($argument);
                        $argument   = false;
                        foreach (['name', 'slug'] as $field) {
                            $record = get_term_by(
                                $field,
                                $search_val,
                                $type
                            );
                            if (false !== $record) {
                                $argument = $record;
                                break;
                            }
                        }
                        unset($search_val, $record, $field);
                        if (false === $argument) {
                            continue;
                        }
                        $argument = (int)$argument->term_id;
                    } elseif ((int) $argument <= 0) {
                        continue;
                    }
                    ${'_' . $type}[] = $argument;
                }
            }
        }
        $request_type = $this->app->settings
            ->get('osec_use_frontend_rendering') ? 'json' : 'jsonp';
        $query        = [
            'cat_ids'      => implode(',', $_events_categories),
            'tag_ids'      => implode(',', $_events_tags),
            'post_ids'     => implode(',', $post_ids),
            'action'       => $view,
            'request_type' => $request_type,
            'events_limit' => isset($atts['events_limit'])
                // definition above casts values as array, so we take first element,
                // as there won't be others
                ? (int)$atts['events_limit'] : null,
            'display_filters' => 'true',
            'display_subscribe' => 'true',
            'display_view_switch' => 'true',
            'display_date_navigation' => 'true',
        ];

        foreach ([
            'display_filters',
            'display_subscribe',
            'agenda_toggle',
            'display_view_switch',
            'display_date_navigation',
        ] as $query_prop) {
            if (isset($atts[$query_prop])) {
                $query[$query_prop] = CalendarPageView::booleanStringArg($atts[$query_prop]);
            }
        }


        foreach ($custom_taxonomies as $taxonomy) {
            $query['osec_' . $taxonomy . '_ids'] = implode(',', ${'_' . $taxonomy});
        }
        if (isset($atts['exact_date'])) {
            $query['exact_date'] = $atts['exact_date'];
        }
        $request = new RequestParser($this->app, $query, $view);
        $request->parse();
        $page_content = CalendarPageView::factory($this->app)->get_content($request, 'shortcode');
        FrontendCssController::factory($this->app)
                             ->add_link_to_html_for_frontend();
        ScriptsFrontendController::factory($this->app)->load_frontend_js(true);

        return $page_content['html'];
    }
}
