<?php

namespace Osec\App\Controller;

use Osec\App\Model\Date\DT;
use Osec\App\Model\SettingsView;
use Osec\App\View\Calendar\CalendarPageView;
use Osec\App\View\Event\EventContentView;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Request\RequestParser;

class ClassicBlockController extends OsecBaseClass
{
    public function registerCalendarBlock()
    {
        register_block_type(
            OSEC_PATH . 'blocks/build/classic',
            [
                'render_callback' => function (array $attributes, string $content): string {
                    if (EventContentView::factory($this->app)->is_filtering_content()) {
                        // No calendars inside event content, see EventContentView::is_filtering_content().
                        return '';
                    }
                    $content .= '<div ' . get_block_wrapper_attributes() . '>';
                    $content .= $this->getContent($this->transformAttributes($attributes));
                    $content .= '</div>';

                    return $content;
                },
            ]
        );
    }

    private function transformAttributes(array $atts)
    {
        $taxonomies = [];
        if (is_array($atts['taxonomies'])) {
            foreach ($atts['taxonomies'] as $taxonomy) {
                $val = implode(',', $taxonomy['value']);
                if (strlen($val)) {
                    $taxonomies[$taxonomy['id']] = $val;
                }
            }
        }

        $query = [
            'action' => SettingsView::factory($this->app)->get_configured($atts['view']),
            'request_type' => $this->app->settings->get('osec_use_frontend_rendering') ? 'json' : 'jsonp',
            'cat_ids' => isset($taxonomies['osec_events_categories']) ? $taxonomies['osec_events_categories'] : [],
            'tag_ids' => isset($taxonomies['osec_events_tags']) ? $taxonomies['osec_events_tags'] : [],
            'post_ids' => implode(',', $atts['postIds']),
            'display_filters' => 'true',
            'display_subscribe' => 'true',
            'display_print' => 'true',
            'agenda_toggle' => 'true',
            'display_view_switch' => 'true',
            'display_date_navigation' => 'true',
            'events_limit' => $this->app->settings->get('agenda_events_per_page'),
        ];

        //    TODO
        //      Custom taxonomies are rendered in Block Editor but not yet here
        //      For now you might need to implement using osec_block_query_alter.
        //     foreach ($taxonomies as $taxonomy => $terms) {
        //          if (! in_array($taxonomy, ['osec_events_categories', 'osec_events_tags'])) {
        //             // $query add custom Taxo???
        //          }
        //     }

        // Booleans
        foreach ([
            'displayFilters' => 'display_filters',
            'displaySubscribe' => 'display_subscribe',
            'displayPrint' => 'display_print',
            'displayViewSwitch' => 'display_view_switch',
            'displayDateNavigation' => 'display_date_navigation',
            'agendaToggle' => 'agenda_toggle',

        ] as $jsProp => $query_prop) {
            if (isset($atts[$jsProp])) {
                $query[$query_prop] = CalendarPageView::booleanStringArg($atts[$jsProp]);
            }
        }

        if (isset($atts['fixedDate']) && DT::isValidTimeStamp($atts['fixedDate'])) {
            $query['exact_date'] = $atts['fixedDate'];
        } else {
            $today = new DT('now', 'sys.default');
            $today->set_time(0, 0, 0);
            $query['exact_date'] = $today->format();
        }


        if (isset($atts['limit']) && isset($atts['limitBy'])) {
            $number = (int)$atts['limit'];
            if ($atts['limitBy'] === 'events') {
                $query['events_limit'] = $number;
            }
            if ($atts['limitBy'] === 'days') {
                // Add limit to fixed date.
                if (isset($atts['fixedDate'])) {
                    $dateLimit = new DT($atts['fixedDate']);
                    // Add a day on fixed date to match UI.
                    $dateLimit->adjust_day(1);
                } else {
                    $dateLimit = new DT('now', 'sys.default');
                }
                $dateLimit->adjust_day($number);
                $dateLimit->set_time(0, 0, 0);
                $query['time_limit'] = $dateLimit->format();
            }
        }

        /**
         * Alter Block query.
         *
         * @since 1.0
         * @param  array  $query  Return query variables for request.
         * @param  array  $atts  Input attributes (Block variables)
         */
        $query = apply_filters('osec_block_query_alter', $query, $atts);
        return $query;
    }

    private function getContent(array $query): string
    {
        $request = new RequestParser($this->app, $query, $query['action']);
        $request->parse();
        $page_content = CalendarPageView::factory($this->app)->get_content($request);
        FrontendCssController::factory($this->app)
                             ->add_link_to_html_for_frontend();
        ScriptsFrontendController::factory($this->app)->load_frontend_js(true);

        return $page_content['html'];
    }
}
