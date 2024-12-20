<?php

namespace Osec\App\View;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ScriptsBackendController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\App\Controller\WidgetController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\View\Calendar\AbstractView;
use Osec\App\View\Calendar\AgendaView;
use Osec\App\View\Calendar\CalendarSubscribeButtonView;
use Osec\Exception\BootstrapException;
use Osec\Helper\IntegerHelper;
use Osec\Http\Request\RequestParser;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * Calendar Widget class
 *
 * A widget that displays the next X upcoming events (similar to Agenda view).
 *
 * @replaces Ai1ec_View_Admin_Widget
 */
class WidgetAgendaView extends WidgetAbstract
{
    /**
     * Constructor for widget.
     */
    public function __construct()
    {
        parent::__construct(
            $this->get_id(),
            __('Upcoming Events', 'open-source-event-calendar'),
            [
                'description'           => __(
                    'Open Source Event Calendar: Lists upcoming events in Agenda view',
                    'open-source-event-calendar'
                ),
                'class'                 => __CLASS__,
                'show_instance_in_rest' => true,
            ]
        );
    }

    /**
     * @return string
     */
    public static function get_id()
    {
        // Must keep name for unknown reason.
        return 'ai1ec_agenda_widget';
    }

    /**
     * Register the widget class.
     */
    public static function register_widget()
    {
        // TODO MAYBE CHECK FIRST IF ALLREADY REGISTERED IN WIDGETCONTROLLER?
        // --> How to ensure single Instance?

        register_widget(__CLASS__);
    }

    public static function uninstall($purge = false)
    {
        unregister_widget(__CLASS__);
        $wpCreatedOpt = 'widget_' . self::get_id();
        if (get_option($wpCreatedOpt)) {
            delete_option($wpCreatedOpt);
        }
    }

    /**
     * @param $id_base
     *
     * @return void
     * @throws BootstrapException
     */
    public function register_javascript_widget($id_base)
    {
        WidgetController::factory($this->app)->add_widget($id_base, $this);
    }

    /**
     * @return array[]
     */
    public function get_configurable_for_widget_creation()
    {
        $defaults = $this->get_js_widget_configurable_defaults();

        return [
            'events_seek_type'                         => [
                'renderer' => [
                    'class'   => 'Osec\Settings\Elements\SettingsSelect',
                    'label'   => __(
                        'Limit by',
                        'open-source-event-calendar'
                    ),
                    'options' => [
                        [
                            'text'  => __('Events', 'open-source-event-calendar'),
                            'value' => 'events',
                        ],
                        [
                            'text'  => __('Days', 'open-source-event-calendar'),
                            'value' => 'days',
                        ],
                    ],
                ],
                'value'    => $defaults['events_seek_type'],
            ],
            'events_per_page'                          => [
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'label'  => __('Number of events to show', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'events',
                ],
                'value'    => $defaults['events_per_page'],
            ],
            'days_per_page'                            => [
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'label'  => __('Number of days to show', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'days',
                ],
                'value'    => $defaults['days_per_page'],
            ],
            'upcoming_widgets_default_tags_categories' => [
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCatsTagsFilter',
                    'label' => __(
                        'Show events filtered for the following tags/categories',
                        'open-source-event-calendar'
                    ),
                    'help'  => __(
                        'To clear, hold &#8984;/<abbr class="initialism">CTRL</abbr> and click selection.',
                        'open-source-event-calendar'
                    ),
                ],
                'value'    => [
                    'categories' => [],
                    'tags'       => [],
                ],
            ],
            'show_subscribe_buttons'                   => [
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'label' => __('Show the subscribe button in the widget', 'open-source-event-calendar'),
                ],
                'value'    => $defaults['show_subscribe_buttons'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function get_js_widget_configurable_defaults()
    {
        $def = $this->get_defaults();
        unset($def['title']);
        unset($def['link_for_days']);

        return $def;
    }

    /**
     * @return array
     */
    public function get_defaults()
    {
        return [
            'title'                  => __('Upcoming Events', 'open-source-event-calendar'),
            'events_seek_type'       => 'events',
            'events_per_page'        => 10,
            'days_per_page'          => 10,
            'show_subscribe_buttons' => true,
            'show_calendar_button'   => true,
            'hide_on_calendar_page'  => true,
            'limit_by_cat'           => false,
            'limit_by_tag'           => false,
            'cat_ids'                => [],
            'tag_ids'                => [],
            'link_for_days'          => true,

        ];
    }

    public function get_name()
    {
        return 'Upcoming Events';
    }

    /**
     * The icon class associated with the widget.
     *
     * @return string
     */
    public function get_icon()
    {
        return 'ai1ec-fa ai1ec-fa-clock-o';
    }

    /**
     * Form function.
     *
     * Renders the widget's configuration form for the Manage Widgets page.
     *
     * @param  array  $instance  The data array for the widget instance being
     *  configured.
     *
     * @return void
     */
    public function form($instance)
    {
        $default  = $this->get_defaults();
        $instance = wp_parse_args((array)$instance, $default);

        // Get available cats, tags, events to allow user to limit widget to certain categories
        $events_categories = get_terms(
            'events_categories',
            [
                'orderby'    => 'name',
                'hide_empty' => false,
            ]
        );
        $events_tags       = get_terms(
            'events_tags',
            [
                'orderby'    => 'name',
                'hide_empty' => false,
            ]
        );

        // Generate unique IDs and NAMEs of all needed form fields
        $fields = [
            'title'                  => ['value' => $instance['title']],
            'events_seek_type'       => ['value' => $instance['events_seek_type']],
            'events_per_page'        => ['value' => $instance['events_per_page']],
            'days_per_page'          => ['value' => $instance['days_per_page']],
            'show_subscribe_buttons' => ['value' => $instance['show_subscribe_buttons']],
            'show_calendar_button'   => ['value' => $instance['show_calendar_button']],
            'hide_on_calendar_page'  => ['value' => $instance['hide_on_calendar_page']],
            'limit_by_cat'           => ['value' => $instance['limit_by_cat']],
            'limit_by_tag'           => ['value' => $instance['limit_by_tag']],
            'cat_ids'                => [
                'value'   => (array)$instance['cat_ids'],
                'options' => $events_categories,
            ],
            'tag_ids'                => [
                'value'   => (array)$instance['tag_ids'],
                'options' => $events_tags,
            ],
        ];
        foreach ($fields as $field => $data) {
            $fields[$field]['id']    = $this->get_field_id($field);
            $fields[$field]['name']  = $this->get_field_name($field);
            $fields[$field]['value'] = $data['value'];
            if (isset($data['options'])) {
                $fields[$field]['options'] = $data['options'];
            }
        }

        // TODO We need to add some Admin CSS/JS here.
        // So that the  "Events with these Categories" Chackbox toggles selector
        ScriptsFrontendController::factory($this->app)->load_admin_js();
        ScriptsBackendController::factory($this->app)->admin_enqueue_scripts('widgets.php');

        // Display theme
        ThemeLoader::factory($this->app)
                   ->get_file('agenda-widget-form.php', $fields, true)
                   ->render();
    }
    // WIDGET_CREATOR

    /**
     * Update function.
     *
     * Called when a user submits the widget configuration form.
     * The data should be validated and returned.
     *
     * @param  array  $new_instance  The new data that was submitted.
     * @param  array  $old_instance  The widget's old data.
     *
     * @return array               The new data to save for this widget instance.
     */
    public function update($new_instance, $old_instance)
    {
        // Save existing data as a base to modify with new data
        $instance                           = $old_instance;
        $instance['title']                  = wp_strip_all_tags((string)$new_instance['title']);
        $instance['events_per_page']        = IntegerHelper::index(
            $new_instance['events_per_page'],
            1,
            1
        );
        $instance['days_per_page']          = IntegerHelper::index(
            $new_instance['days_per_page'],
            1,
            1
        );
        $instance['events_seek_type']       = $this->ensureSeekType(
            $new_instance['events_seek_type']
        );
        $instance['show_subscribe_buttons'] = isset($new_instance['show_subscribe_buttons']) ? true : false;
        $instance['show_calendar_button']   = isset($new_instance['show_calendar_button']) ? true : false;
        $instance['hide_on_calendar_page']  = isset($new_instance['hide_on_calendar_page']) ? true : false;

        // For limits, set the limit to False if no IDs were selected,
        // or set the respective IDs to empty if "limit by" was unchecked
        $instance['limit_by_cat'] = false;
        $instance['cat_ids']      = [];
        if (isset($new_instance['cat_ids']) && $new_instance['cat_ids'] != false) {
            $instance['limit_by_cat'] = true;
        }
        if (isset($new_instance['limit_by_cat']) && $new_instance['limit_by_cat'] != false) {
            $instance['limit_by_cat'] = true;
        }
        if (isset($new_instance['cat_ids']) && $instance['limit_by_cat'] === true) {
            $instance['cat_ids'] = $new_instance['cat_ids'];
        }

        $instance['limit_by_tag'] = false;
        $instance['tag_ids']      = [];
        if (isset($new_instance['tag_ids']) && $new_instance['tag_ids'] != false) {
            $instance['limit_by_tag'] = true;
        }
        if (isset($new_instance['limit_by_tag']) && $new_instance['limit_by_tag'] != false) {
            $instance['limit_by_tag'] = true;
        }
        if (isset($new_instance['tag_ids']) && $instance['limit_by_tag'] === true) {
            $instance['tag_ids'] = $new_instance['tag_ids'];
        }

        return $instance;
    }

    /**
     * Ensure valid seek type.
     *
     * Return valid seek type for given user input (selection).
     *
     * @param  string  $value  User selection for seek type
     *
     * @return string        Seek type to use
     */
    protected function ensureSeekType($value): string
    {
        static $list = ['events', 'days'];
        if (! in_array($value, $list)) {
            return (string)reset($list);
        }

        return $value;
    }

    /**
     * @return void
     * @throws BootstrapException
     */
    public function add_js()
    {
        ScriptsFrontendController::factory($this->app)->add_link_to_render_js(
            ScriptsFrontendController::LOAD_ONLY_FRONTEND_SCRIPTS,
            false
        );
    }

    /**
     * @param  array  $args_for_widget
     * @param $remote
     *
     * @return void
     * @throws BootstrapException
     */
    public function get_content(array $args_for_widget, $remote = false)
    {
        $addInlineCss = ($remote || $this->isBlockEditor());
        $request      = RequestParser::factory($this->app);
        $agendaView   = new AgendaView($this->app, $request);

        $time     = new DT('now');
        $search   = EventSearch::factory($this->app);
        $settings = $this->app->settings;

        $is_calendar_page = is_page($settings->get('calendar_page_id'));
        if (
            $args_for_widget['hide_on_calendar_page'] &&
            $is_calendar_page
        ) {
            return;
        }

        // Add params to the subscribe_url for filtering by Limits (category, tag)
        $subscribe_filter = '';
        if (! is_array($args_for_widget['cat_ids'])) {
            $args_for_widget['cat_ids'] = explode(',', (string)$args_for_widget['cat_ids']);
        }

        if (! is_array($args_for_widget['tag_ids'])) {
            $args_for_widget['tag_ids'] = explode(',', (string)$args_for_widget['tag_ids']);
        }

        $subscribe_filter .= $args_for_widget['cat_ids'] ?
            '&osec_cat_ids=' . implode(',', $args_for_widget['cat_ids']) : '';

        $subscribe_filter .= $args_for_widget['tag_ids'] ?
            '&osec_tag_ids=' . implode(',', $args_for_widget['tag_ids']) : '';

        // Get localized time
        $timestamp = $time->format_to_gmt();

        // Set $limit to the specified category/tag
        $filter = [
            'cat_ids' => $args_for_widget['cat_ids'],
            'tag_ids' => $args_for_widget['tag_ids'],
        ];

        /**
         * Add or alter filters in Agenda widget.
         *
         * @since 1.0
         *
         * @param  array  $limit  Array of Less variables
         */
        $filter = apply_filters('osec_filters_upcoming_widget_alter', $filter);

        // Get events, then classify into date array
        // JB: apply seek check here
        $seek_days  = ('days' === $args_for_widget['events_seek_type']);
        $seek_count = $args_for_widget['events_per_page'];
        $last_day   = false;
        if ($seek_days) {
            $seek_count = $args_for_widget['days_per_page'] * 5;
            $last_day   = strtotime(
                '+' . $args_for_widget['days_per_page'] . ' days'
            );
        }

        $event_results = $search->get_events_relative_to(
            $timestamp,
            $seek_count,
            0,
            $filter
        );
        if ($seek_days) {
            foreach ($event_results['events'] as $ek => $event) {
                if ($event->get('start')->format() >= $last_day) {
                    unset($event_results['events'][$ek]);
                }
            }
        }

        $dates = $agendaView->get_agenda_like_date_array($event_results['events'], $request);

        $args_for_widget['dates'] = $dates;
        // load CSS just once for all widgets.
        // Do not load it on the calendar page as it's already loaded.
        if (false === $this->cssIsLoaded && ! $is_calendar_page) {
            if ($addInlineCss) {
                $args_for_widget['css'] = FrontendCssController::factory($this->app)
                                                               ->get_compiled_css();
            }
            $this->cssIsLoaded = true;
        }
        $args_for_widget['show_location_in_title']    = $settings->get('show_location_in_title');
        $args_for_widget['show_year_in_agenda_dates'] = $settings->get('show_year_in_agenda_dates');
        $args_for_widget['calendar_url']              = HtmlFactory::factory($this->app)
                                                                   ->create_href_helper_instance($filter)
                                                                   ->generate_href();
        $args_for_widget['subscribe_url']             = OSEC_EXPORT_URL . $subscribe_filter;
        $args_for_widget['subscribe_url_no_html']     = OSEC_EXPORT_URL . '&no_html=true' . $subscribe_filter;
        $args_for_widget['text_upcoming_events']      = __(
            'There are no upcoming events.',
            'open-source-event-calendar'
        );
        $args_for_widget['text_all_day']              = __('all-day', 'open-source-event-calendar');
        $args_for_widget['text_view_calendar']        = __('View Calendar', 'open-source-event-calendar');

        // TODO Just disabled that

        // $args_for_widget['calendar_url'] = get_page_uri($settings->get('calendar_page_id'));
        $args_for_widget['text_edit']              = __('Edit', 'open-source-event-calendar');
        $args_for_widget['text_venue_separator']   = AbstractView::get_venue_separator_text();
        $args_for_widget['text_subscribe_label']   = __('Add', 'open-source-event-calendar');
        $args_for_widget['subscribe_buttons_text'] = CalendarSubscribeButtonView::factory($this->app)->get_labels();

        // Display theme
        return ThemeLoader::factory($this->app)
                          ->get_file('agenda-widget.twig', $args_for_widget)
                          ->get_content();
    }

    /*
    (non-PHPdoc)
     * @see \Ai1ec_Embeddable::check_requirements()
     */

    /**
     *  Add support for "X3P0 Legacy Widget" or similar.   *
     */
    private function isBlockEditor()
    {
        // e.g: wp-json/wp/v2/widget-types/osec_scheduler_hooks/encode
        return str_contains($_SERVER['REQUEST_URI'], $this->get_id());
    }

    /**
     * @param $args
     *
     * @return mixed
     */
    public function javascript_widget($args)
    {
        $args['show_calendar_button'] = false;
        $args['link_for_days']        = false;

        return parent::javascript_widget($args);
    }

    public function check_requirements()
    {
        return null;
    }
}
