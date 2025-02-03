<?php

namespace Osec\App\Controller;

use Osec\App\Model\SettingsView;
use Osec\App\View\Calendar\CalendarPageView;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Request\RequestParser;

class BlockController extends OsecBaseClass
{
    private array $blockFile;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->blockFile = json_decode(
            file_get_contents(OSEC_PATH . 'calendar_block/build/block.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function registerCalendarBlock()
    {
        wp_register_script(
            'osec-calendar-block-classic',
            plugins_url(OSEC_PLUGIN_NAME . '/calendar_block/build/index.js', OSEC_PLUGIN_NAME),
            [
                // Dependencies
                'wp-blocks',
                'wp-i18n',
                'wp-block-editor',
                'wp-data',
                'wp-core-data',
                'wp-components'
                // wp-api-fetch??
                // Data??
                // @wordpress/data
            ]
        );

        register_block_type(
            $this->blockFile['name'],
            array_merge_recursive(
                $this->blockFile,
                [
                    'editor_script' => 'osec-calendar-block-classic',
                    'render_callback' => function (array $attributes, string $content, \WP_Block $wpBlock): string {
                        $content .= '<div ' . get_block_wrapper_attributes() . '>';
                        $content .= $this->getContent($this->transformAttributes($attributes));
                        $content .= '</div>';

                        return $content;
                    },
                ]
            )
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
            'cat_ids' => isset($taxonomies['events_categories']) ? $taxonomies['events_categories'] : [],
            'tag_ids' => isset($taxonomies['events_tags']) ? $taxonomies['events_tags'] : [],
            'post_ids' => implode(',', $atts['postIds']),
            'display_filters' => 'true',
            'display_subscribe' => 'true',
            'display_view_switch' => 'true',
            'display_date_navigation' => 'true',
            'events_limit' => isset($atts['events_limit'])
                // definition above casts values as array, so we take first element,
                // as there won't be others
                ? (int)$atts['events_limit'] : null,
        ];

        //    TODO Custom taxonomies.
        //     foreach ($taxonomies as $taxonomy => $terms) {
        //          if (! in_array($taxonomy, ['events_categories', 'events_tags'])) {
        //             // $query add custom Taxo???
        //          }
        //     }

        // Booleans
        foreach ([
            'displayFilters' => 'display_filters',
            'displaySubscribe' => 'display_subscribe',
            'displayViewSwitch' => 'display_view_switch',
            'displayDateNavigation' => 'display_date_navigation',
        ] as $jsProp => $query_prop) {
            if (isset($atts[$jsProp])) {
                $query[$query_prop] = CalendarPageView::booleanStringArg($atts[$jsProp]);
            }
        }

        if (isset($atts['exact_date'])) {
            $query['exact_date'] = $atts['exact_date'];
        }
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
