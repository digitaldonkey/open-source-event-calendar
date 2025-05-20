<?php

namespace Osec\Settings;

use Osec\App\Model\Date\DateValidator;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\TaxonomyAdapter;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheMemory;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Theme\FileAbstract;
use Osec\Theme\FileTwig;
use Osec\Theme\ThemeLoader;

/**
 * A factory class for html elements
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Factory_Html
 */
class HtmlFactory extends OsecBaseClass
{
    /**
     * @var bool
     */
    protected $pretty_permalinks_enabled = false;

    /**
     * @var string
     */
    protected $page;

    /**
     * The contructor method.
     *
     * @param  App  $app
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $cache                           = CacheMemory::factory($this->app);
        $this->page                      = $cache->get('calendar_base_page');
        $this->pretty_permalinks_enabled = $cache->get('permalinks_enabled');
    }

    /**
     * Create the html element used as the UI control for the datepicker button.
     * The href must keep only active filters.
     *
     * @param  array  $args  Populated args for the view
     * @param  int|string|null  $initial_date  The datepicker's initially set date
     * @param  string  $title  Title to display in datepicker button
     * @param  string  $title_short  Short names in title
     */
    public function create_datepicker_link(
        array $args,
        $initial_date = null,
        $title = '',
        $title_short = ''
    ) {
        $date_system = UIDateFormats::factory($this->app);

        $date_format_pattern = $date_system->get_date_pattern_by_key(
            $this->app->settings->get('input_date_format')
        );

        if (null === $initial_date) {
            // If exact_date argument was provided, use its value to initialize
            // datepicker.
            if (
                isset($args['exact_date']) &&
                $args['exact_date'] !== false &&
                $args['exact_date'] !== null
            ) {
                $initial_date = $args['exact_date'];
            } else {
                // Else default to today's date.
                $initial_date = $date_system->current_time();
            }
        }
        // Convert initial date to formatted date if required.
        if (DateValidator::is_valid_time_stamp($initial_date)) {
            $initial_date = $date_system->format_date(
                $initial_date,
                $this->app->settings->get('input_date_format')
            );
        }

        $href_args = [
            'action'     => $args['action'],
            'cat_ids'    => $args['cat_ids'],
            'tag_ids'    => $args['tag_ids'],
            'exact_date' => '__DATE__',
            'display_filters' => $args['display_filters'],
            'display_subscribe' => $args['display_subscribe'],
            'agenda_toggle' => $args['agenda_toggle'],
            'display_view_switch' => $args['display_view_switch'],
            'display_date_navigation' => $args['display_date_navigation'],
        ];
        /**
         * Alter href arguments for datepicker
         *
         * Rendered in datepicker_link.twig
         *
         * @since 1.00
         *
         * @param  array  $href_args  Return current arguments
         * @param  array  $args  Datepicker Arguments
         */
        $href_args = apply_filters('osec_date_picker_href_args', $href_args, $args);
        $data_href = $this->create_href_helper_instance($href_args);

        $attributes = [
            'data-date'           => $initial_date,
            'data-date-format'    => $date_format_pattern,
            'data-date-weekstart' => $this->app->settings->get('week_start_day'),
            'href'                => '#',
            'data-href'           => $data_href->generate_href(),
            'data-lang'           => str_replace('_', '-', get_locale()),
        ];
        $loader     = ThemeLoader::factory($this->app);
        $file       = $loader->get_file('date-icon.png');

        $args = [
            'attributes'  => $attributes,
            'data_type'   => $args['data_type'],
            'icon_url'    => $file->get_url(),
            'text_date'   => __('Choose a date using calendar', 'open-source-event-calendar'),
            'title'       => $title,
            'title_short' => $title_short,
        ];

        return $loader->get_file('datepicker_link.twig', $args);
    }

    /**
     * Creates an instance of the class which generates href for links.
     *
     * @param  string  $type
     *
     * @return ElementHref
     */
    public function create_href_helper_instance(array $args, $type = 'normal')
    {
        $href = new ElementHref($args, $this->page);
        $href->set_pretty_permalinks_enabled($this->pretty_permalinks_enabled);
        switch ($type) {
            case 'category':
                $href->set_is_category(true);
                break;
            case 'tag':
                $href->set_is_tag(true);
                break;
            case 'author':
                $href->set_is_author(true);
                break;
            default:
                break;
        }

        return $href;
    }

    /**
     * Creates a select2 Multiselect.
     *
     * @param  array  $args  The arguments for the select.
     * @param  array  $options  The options of the select
     * @param  array|null  $view_args  The args used in the front end.
     *
     * @return FileAbstract
     *
     * @throws BootstrapException
     * @throws Exception
     * @staticvar $cached_flips    Maps of taxonomy identifiers.
     * @staticvar $checkable_types Map of types and taxonomy identifiers.
     */
    public function create_select2_multiselect(array $args, array $options, array $view_args = null)
    {
        // if no data is present and we are in the frontend, return a blank
        // element.
        if (empty($options) && null !== $view_args) {
            // WHAT?
            throw new Exception('Something unexpected happened here.');
        }

        static $cached_flips = [];
        static $checkable_types = [
            'category' => 'cat_ids',
            'tag'      => 'tag_ids',
            'author'   => 'auth_ids',
        ];

        $use_id         = isset($args['use_id']);
        $options_to_add = [];
        foreach ($options as $term) {
            $option_arguments = [];
            $color            = false;
            if ($args['type'] === 'category') {
                $color = TaxonomyAdapter::factory($this->app)
                                        ->get_category_color($term->term_id);
            }
            if ($color) {
                $option_arguments['data-color'] = $color;
            }
            if (null !== $view_args) {
                // create the href for ajax loading
                $href = $this->create_href_helper_instance(
                    $view_args,
                    $args['type']
                );
                $href->set_term_id($term->term_id);
                $option_arguments['data-href'] = $href->generate_href();
                // check if the option is selected
                $type_to_check = '';
                // first let's check the correct type
                if (isset($checkable_types[$args['type']])) {
                    $type_to_check = $checkable_types[$args['type']];
                }
                // let's flip the array. Just once for performance sake,
                // the categories doesn't change in the same request
                if ( ! isset($cached_flips[$type_to_check])) {
                    $cached_flips[$type_to_check] = array_flip(
                        $view_args[$type_to_check]
                    );
                }
                if (isset($cached_flips[$type_to_check][$term->term_id])) {
                    $option_arguments['selected'] = 'selected';
                }
            }
            if (true === $use_id) {
                $options_to_add[] = [
                    'text'  => $term->name,
                    'value' => $term->term_id,
                    'args'  => $option_arguments,
                ];
            } else {
                $options_to_add[] = [
                    'text'  => $term->name,
                    'value' => $term->name,
                    'args'  => $option_arguments,
                ];
            }
        }
        $select2_args = [
            'multiple'         => 'multiple',
            'data-placeholder' => $args['placeholder'],
            'class'            => 'ai1ec-select2-multiselect-selector span12',
        ];
        if (isset($args['class'])) {
            $select2_args['class'] .= ' ' . $args['class'];
        }
        $container_class = false;
        if (isset($args['type'])) {
            $container_class = 'ai1ec-' . $args['type'] . '-filter';
        }
        $select2 = ThemeLoader::factory($this->app)->get_file(
            'select2_multiselect.twig',
            [
                'name'            => $args['name'],
                'id'              => $args['id'],
                'container_class' => $container_class,
                'select2_args'    => $select2_args,
                'options'         => $options_to_add,
            ],
            true
        );

        return $select2;
    }

    /**
     * Creates a select2 input.
     *
     * @param  array  $args  The arguments of the input.
     *
     * @return FileTwig
     */
    public function create_select2_input(array $args): FileTwig
    {
        if ( ! isset($args['name'])) {
            $args['name'] = $args['id'];
        }
        // Get tags.
        $tags = get_terms([
            'taxonomy'   => 'events_tags',
            'orderby'    => 'name',
            'hide_empty' => false,
        ]);

        // Build tags array to pass as JSON.
        $tags_json = [];
        foreach ($tags as $term) {
            $tags_json[] = $term->name;
        }
        $tags_json    = wp_json_encode($tags_json);
        $tags_json    = _wp_specialchars($tags_json, 'single', 'UTF-8');
        $select2_args = [
            'data-placeholder' => __('Tags (optional)', 'open-source-event-calendar'),
            'class'            => 'ai1ec-tags-selector span12',
            'data-ai1ec-tags'  => $tags_json,
        ];
        $select2      = ThemeLoader::factory($this->app)->get_file(
            'select2_input.twig',
            [
                'name'         => $args['name'],
                'id'           => $args['id'],
                'select2_args' => $select2_args,
            ],
            true
        );

        return $select2;
    }
}
