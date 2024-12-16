<?php

namespace Osec\App\Model;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ShutdownController;
use Osec\Bootstrap\OsecBaseInitialized;
use Osec\Exception\Exception;
use Osec\Exception\SettingsException;
use Osec\Theme\ThemeLoader;

/**
 * Model used for storing/retrieving plugin options.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package App
 * @replaces Ai1ec_Settings
 */
class Settings extends OsecBaseInitialized
{
    /**
     * @constant string Name of WordPress options key used to store settings.
     */
    public const WP_OPTION_KEY = 'osec_settings';

    /**
     * @var array Map of value names and their representations.
     */
    protected $options = [];

    /**
     * @var bool Indicator for modified object state.
     */
    protected $isUpdated = false;

    /**
     * @var array The core options of the plugin.
     */
    protected $defaultOptions;

    public function uninstall(bool $purge = false)
    {
        global $wpdb;
        if ($purge) {
            // DELETE FROM wp_options WHERE `option_name` LIKE 'osec_%'
            $query = $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", 'osec_%');
            $wpdb->query($query);
        }
    }

    /**
     * Just to see what's happening.
     */
    public function getOptionsList()
    {
        $allSettings = [];
        foreach ($this->options as $name => $option) {
            $allSettings[$name] = array_merge(['key' => $name], $this->defaultOptions[$name], $option);
        }
        ksort($allSettings);

        return $allSettings;
    }

    /**
     * Get field options as registered.
     *
     * @param  string  $option  Name of option field to describe.
     *
     * @return array|null Description or null if nothing is found.
     */
    public function describe($option)
    {
        if ( ! isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * Remove an option if is set.
     *
     * @param  string  $option
     */
    public function remove_option($option)
    {
        if (isset($this->options[$option])) {
            unset($this->options[$option]);
            $this->_change_update_status(true);
        }
    }

    /**
     * Change `updated` flag value.
     *
     * @param  bool  $new_status  Status to change to.
     *
     * @return bool Previous status flag value.
     */
    protected function _change_update_status($new_status)
    {
        $previous        = $this->isUpdated;
        $this->isUpdated = (bool)$new_status;

        return $previous;
    }

    /**
     * Hide an option by unsetting it's renderer
     *
     * @param  string  $option
     */
    public function hide_option($option)
    {
        if (isset($this->options[$option])) {
            unset($this->options[$option]['renderer']);
            $this->_change_update_status(true);
        }
    }

    /**
     * Show an option by setting it's renderer
     *
     * @param  string  $option
     */
    public function show_option($option, array $renderer)
    {
        if (isset($this->options[$option])) {
            $this->options[$option]['renderer'] = $renderer;
            $this->_change_update_status(true);
        }
    }

    /**
     * Check object state and update it's database representation as needed.
     *
     * @return void Destructor does not return.
     */
    public function shutdown()
    {
        if ($this->isUpdated) {
            $this->persist();
        }
    }

    /**
     * Write object representation to persistence layer.
     *
     * Upon successful write to persistence layer the objects internal
     * state {@see self::$updated} is updated respectively.
     *
     * @return bool Success.
     */
    public function persist()
    {
        $success = $this->app->options
            ->set(self::WP_OPTION_KEY, $this->options, true);
        if ($success) {
            $this->_change_update_status(false);
        }

        return $success;
    }

    /**
     * Set new value for previously initialized option.
     *
     * @param  string  $option  Name of option to update.
     * @param  mixed  $value  Actual value to be used for option.
     *
     * @return Settings Instance of self for chaining.
     */
    public function set($option, mixed $value)
    {
        if ( ! isset($this->options[$option])) {
            throw new SettingsException(
                'Option "' . $option . '" was not registered'
            );
        }
        if ('array' === $this->options[$option]['type']) {
            if (
                ! is_array($this->options[$option]['value']) ||
                ! is_array($value) ||
                $value != $this->options[$option]['value']
            ) {
                $this->options[$option]['value'] = $value;
                $this->_change_update_status(true);
            }
        } elseif (
            (string)$value !== (string)$this->options[$option]['value']
        ) {
            $this->options[$option]['value'] = $value;
            $this->_change_update_status(true);
        }

        return $this;
    }

    /**
     * Observes wp_options changes. If any matches related setting then
     * updates that setting.
     *
     * @param  string  $option  Name of the updated option.
     * @param  mixed  $old_value  The old option value.
     * @param  mixed  $value  The new option value.
     *
     * @return void Method does not return.
     */
    public function wp_options_observer($option, mixed $old_value, mixed $value)
    {
        $options = $this->get_options();
        if (
            self::WP_OPTION_KEY === $option ||
            empty($options)
        ) {
            return;
        }

        if (
            isset($options[$option]) &&
            'wp_option' === $options[$option]['type'] &&
            $this->get($option) !== $value
        ) {
            $this->set($option, $value);
        }
    }

    /**
     * Gets the options.
     *
     * @return array:
     */
    public function get_options()
    {
        return $this->options;
    }

    /**
     * Get value for option.
     *
     * @param  string  $option  Name of option to get value for.
     * @param  mixed  $default  Value to return if option is not found.
     *
     * @return mixed Value or $default if none is found.
     */
    public function get($option, mixed $default = null)
    {
        // notice, that `null` is not treated as a value
        if ( ! isset($this->options[$option])) {
            return $default;
        }

        return $this->options[$option]['value'];
    }

    /**
     * Initiate options map from storage.
     *
     * @return void Return from this method is ignored.
     */
    protected function _initialize()
    {
        // TODO
        // Add doc when and how this call is cached and how to disable caching.

        $this->_set_standard_values();
        $values = $this->app->options->get(self::WP_OPTION_KEY, []);
        $this->_change_update_status(false);
        $test_version = false;
        if (is_array($values)) { // always assign existing values, if any
            $this->options = $values;
            if (isset($values['calendar_page_id'])) {
                $test_version = $values['calendar_page_id']['version'];
            }
        }
        $upgrade = false;
        // check for updated translations
        $this->_register_standard_values();
        if (
            // process meta updates changes
            empty($values) || (
                false !== $test_version &&
                OSEC_VERSION !== $test_version
            )
        ) {
            $this->_register_standard_values();
            $this->_update_name_translations();
            $this->_change_update_status(true);
            $upgrade = true;
        } elseif ($values instanceof Settings) {
            // TODO REMOVE process legacy...
            throw new Exception('Legacy settings are not supported anymore.');
        }
        if (true === $upgrade) {
            $this->perform_upgrade_actions();
        }
        ShutdownController::factory($this->app)->register(
            $this->shutdown(...)
        );
    }

    /**
     * Set the standard values for the options of the core plugin.
     */
    protected function _set_standard_values()
    {
        // Renderer-> class must be in this namespace (Osec\Html\Settings\XXX).
        $this->defaultOptions = [
            'osec_db_version'                => [
                'type'    => 'string',
                'default' => false,
            ],
            'feeds_page'                     => [
                'type'    => 'string',
                'default' => false,
            ],
            'settings_page'                  => [
                'type'    => 'string',
                'default' => false,
            ],
            'less_variables_page'            => [
                'type'    => 'string',
                'default' => false,
            ],
            // TODO Is this correct?
            // This is the WP default format commented out.
            // 'input_date_format' => ['type' => 'string', 'default' => 'd/m/yyyy'],
            'plugins_options'                => [
                'type'    => 'array',
                'default' => [],
            ],
            'show_tracking_popup'            => [
                'type'    => 'deprecated',
                'default' => true,
            ],
            'calendar_page_id'               => [
                'type'     => 'mixed',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCalenderPageSelect',
                    'tab'   => 'viewing-events',
                    'label' => __('Calendar page', 'open-source-event-calendar'),
                ],
                'default'  => false,
            ],
            'week_start_day'                 => [
                'type'     => 'int',
                'renderer' => [
                    'class'   => 'Osec\Settings\Elements\SettingsSelect',
                    'tab'     => 'viewing-events',
                    'item'    => 'viewing-events',
                    'label'   => __('Week starts on', 'open-source-event-calendar'),
                    'options' => 'get_weekdays_settings',
                ],
                'default'  => $this->app->options->get(
                    'start_of_week'
                ),
            ],
            'enabled_views'                  => [
                'type'     => 'array',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsEnabledViews',
                    'tab'   => 'viewing-events',
                    'label' => __('Available views', 'open-source-event-calendar'),
                ],
                'default'  => [
                    'agenda' => [
                        'enabled'        => true,
                        'default'        => true,
                        'enabled_mobile' => true,
                        'default_mobile' => true,
                        'longname'       => _n_noop(
                            'Agenda',
                            'Agenda',
                            'open-source-event-calendar'
                        ),
                    ],
                    'oneday' => [
                        'enabled'        => true,
                        'default'        => false,
                        'enabled_mobile' => true,
                        'default_mobile' => false,
                        'longname'       => _n_noop(
                            'Day',
                            'Day',
                            'open-source-event-calendar'
                        ),
                    ],
                    'month'  => [
                        'enabled'        => true,
                        'default'        => false,
                        'enabled_mobile' => true,
                        'default_mobile' => false,
                        'longname'       => _n_noop(
                            'Month',
                            'Month',
                            'open-source-event-calendar'
                        ),
                    ],
                    'week'   => [
                        'enabled'        => true,
                        'default'        => false,
                        'enabled_mobile' => true,
                        'default_mobile' => false,
                        'longname'       => _n_noop(
                            'Week',
                            'Week',
                            'open-source-event-calendar'
                        ),
                    ],
                ],
            ],
            // THis is actually an ALIAS
            'timezone_string'                => [
                'type'     => 'wp_option',
                'renderer' => [
                    'class'   => 'Osec\Settings\Elements\SettingsSelect',
                    'tab'     => 'viewing-events',
                    'item'    => 'viewing-events',
                    'label'   => __('Timezone', 'open-source-event-calendar'),
                    'options' => 'Osec\App\Model\Date\Timezones::get_timezones',
                    'help'    => __(
                        'This is an alias to wp-settings timezone and could also be 
                            changed on /wp-admin/options-general.php.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => $this->app->options->get(
                    'timezone_string'
                ),
            ],
            'default_tags_categories'        => [
                'type'     => 'array',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCatsTagsFilter',
                    'tab'   => 'viewing-events',
                    'label' => __('Preselected calendar filters', 'open-source-event-calendar'),
                    'help'  => __(
                        'To clear, hold &#8984;/<abbr class="initialism">CTRL</abbr> and click selection.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => [
                    'categories' => [],
                    'tags'       => [],
                ],
            ],
            'exact_date'                     => [
                'type'     => 'string',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsInput',
                    'tab'   => 'viewing-events',
                    'label' => __('Default calendar start date (optional)', 'open-source-event-calendar'),
                    'type'  => 'date',
                ],
                'default'  => '',
            ],
            'agenda_events_per_page'         => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  => __('Agenda pages show at most', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'events',
                ],
                'default'  => 10,
            ],
            'week_view_starts_at'            => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  => __('Week/Day view starts at', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'hrs',
                ],
                'default'  => 8,
            ],
            'week_view_ends_at'              => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  => __('Week/Day view ends at', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'hrs',
                ],
                'default'  => 24,
            ],
            'month_word_wrap'                => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __('<strong>Word-wrap event stubs</strong> in Month view', 'open-source-event-calendar'),
                    'help' => __('Only applies to events that span a single day.', 'open-source-event-calendar'),
                ],
                'default'  => false,
            ],
            'agenda_include_entire_last_day' => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        'In <span class="ai1ec-tooltip-toggle"
                            data-original-title="These include Agenda view,
                            the Upcoming Events widget, and some extended views.">
                            Agenda-like views</span>, <strong>include all events
                            from last day shown</strong>',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'agenda_events_expanded'         => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        'Keep all events <strong>expanded</strong> in Agenda view (disables toggler).',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'show_year_in_agenda_dates'      => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        '<strong>Show year</strong> in calendar date labels',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'show_location_in_title'         => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        '<strong>Show location in event titles</strong> in calendar views',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => true,
            ],
            'exclude_from_search'            => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        '<strong>Exclude</strong> events from search results',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'turn_off_subscription_buttons'  => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        'Hide <strong>Subscribe</strong>/<strong>Add to Calendar</strong> 
                            buttons in calendar and single event views ',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'hide_maps_until_clicked'        => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        ' Hide <strong>Google Maps</strong> until clicked',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],

            'affix_filter_menu'                      => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        ' <strong>Affix filter menu</strong> to top of window when it scrolls out of view',
                        'open-source-event-calendar'
                    ),
                    'help'  => __(
                        'Only applies to first visible calendar found on the page.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'affix_vertical_offset_md'               => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  => __('Offset affixed filter bar vertically by', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'pixels',
                ],
                'default'  => 0,
            ],
            'affix_vertical_offset_lg'               => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  =>
                        '<i class="ai1ec-fa ai1ec-fa-lg ai1ec-fa-fw ai1ec-fa-desktop"></i> ' .
                        __('Wide screens only (&#8805; 1200px)', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'pixels',
                ],
                'default'  => 0,
            ],
            'affix_vertical_offset_sm'               => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  =>
                        '<i class="ai1ec-fa ai1ec-fa-lg ai1ec-fa-fw ai1ec-fa-tablet"></i> ' .
                        __('Tablets only (< 980px)', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'pixels',
                ],
                'default'  => 0,
            ],
            'affix_vertical_offset_xs'               => [
                'type'     => 'int',
                'renderer' => [
                    'class'  => 'Osec\Settings\Elements\SettingsInput',
                    'tab'    => 'viewing-events',
                    'item'   => 'viewing-events',
                    'label'  =>
                        '<i class="ai1ec-fa ai1ec-fa-lg ai1ec-fa-fw ai1ec-fa-mobile"></i> ' .
                        __('Phones only (< 768px)', 'open-source-event-calendar'),
                    'type'   => 'append',
                    'append' => 'pixels',
                ],
                'default'  => 0,
            ],
            'strict_compatibility_content_filtering' => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        'Strict compatibility content filtering',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'hide_featured_image'                    => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __(
                        ' <strong>Hide featured image</strong> from event details page',
                        'open-source-event-calendar'
                    ),
                    'help'  => __(
                        "Select this option if your theme already displays each post's featured image.",
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'input_date_format'                      => [
                'type'     => 'string',
                'renderer' => [
                    'class'   => 'Osec\Settings\Elements\SettingsSelect',
                    'tab'     => 'editing-events',
                    'label'   => __('Input date format', 'open-source-event-calendar'),
                    'options' => [
                        [
                            'text'  => __('Default (d/m/yyyy)', 'open-source-event-calendar'),
                            'value' => 'def',
                        ],
                        [
                            'text'  => __('US (m/d/yyyy)', 'open-source-event-calendar'),
                            'value' => 'us',
                        ],
                        [
                            'text'  => __('ISO 8601 (yyyy-m-d)', 'open-source-event-calendar'),
                            'value' => 'iso',
                        ],
                        [
                            'text'  => __('Dotted (m.d.yyyy)', 'open-source-event-calendar'),
                            'value' => 'dot',
                        ],
                    ],
                ],
                'default'  => 'def',
            ],
            'input_24h_time'                         => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'editing-events',
                    'label' => __(
                        ' Use <strong>24h time</strong> in time pickers',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'disable_autocompletion'                 => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'editing-events',
                    'label' => __(
                        '<strong>Disable address autocomplete</strong> function',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'geo_region_biasing'                     => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'editing-events',
                    'label' => __(
                        'Use the configured <strong>region</strong> (WordPress locale) 
                            to bias the address autocomplete function ',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'show_publish_button'                    => [
                'type'     => 'deprecated',
                'renderer' => null,
                'default'  => false,
            ],
            'shortcodes'                             => [
                'type'     => 'html',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsShortcodesText',
                    'tab'   => 'advanced',
                    'item'  => 'shortcodes',
                ],
                'default'  => null,
            ],
            'calendar_css_selector'                  => [
                'type'     => 'string',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsInput',
                    'tab'   => 'advanced',
//                    'item'  => 'advanced',
                    'label' => __('Move calendar into this DOM element', 'open-source-event-calendar'),
                    'type'  => 'normal',
                    'help'  => __(
                        'Optional. Use this JavaScript-based shortcut to place the
                            calendar a DOM element other than the usual page content container
                            if you are unable to create an appropriate page template
                             for the calendar page. To use, enter a
                            <a target="_blank" href="https://api.jquery.com/category/selectors/">
                            jQuery selector</a> that evaluates to a single DOM element.
                            Any existing markup found within the target will be replaced
                            by the calendar. Will only work if selector is <strong>outside</strong> the page content loop.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => '',
            ],
            'skip_in_the_loop_check'                 => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'advanced',
//                    'item'  => 'advanced',
                    'label' => __(
                        '<strong>Skip <tt>in_the_loop()</tt> check </strong> 
                            that protects against multiple calendar output',
                        'open-source-event-calendar'
                    ),
                    'help'  => __(
                        'Try enabling this option if your calendar does not appear on the calendar page. 
                            It is needed for compatibility with a small number of themes that call
                            <code>the_content()</code> from outside of The Loop. Leave disabled otherwise.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
            'osec_use_frontend_rendering'            => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'advanced',
                    'label' => __('Use frontend rendering.', 'open-source-event-calendar'),
                    'help'  => __(
                        'Renders calendar views on the client rather than the server; 
                            significantly improvees performance.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => true,
            ],
            'render_css_as_link'                     => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'advanced',
//                    'item'  => 'advanced',
                    'label' => __(
                        '<strong>Link CSS</strong> in <code>&lt;head&gt;</code> section when file cache is unavailable.',
                        'open-source-event-calendar'
                    ),
                    'help'  => __(
                        'Serve CSS as a link if file cache is not enabled rather than have  
                            it output inline (recommended).',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => true,
            ],
            'edit_robots_txt'                        => [
                'type'     => 'string',
                'renderer' => [
                    'class'    => 'Osec\Settings\Elements\SettingsTextarea',
                    'tab'      => 'advanced',
//                    'item'     => 'advanced',
                    'label'    => __('Current <strong>robots.txt</strong> on this site', 'open-source-event-calendar'),
                    'type'     => 'normal',
                    'rows'     => 6,
                    'readonly' => 'readonly',
                    'help'     => __(
                        'The Robot Exclusion Standard, also known as the Robots Exclusion Protocol or
						<code><a href="https://en.wikipedia.org/wiki/Robots.txt" target="_blank">robots.txt</a></code>
						protocol, is a convention for cooperating web crawlers and other web robots
						about accessing all or part of a website that is otherwise publicly viewable.
						You can change it manually by editing <code>robots.txt</code> in your root WordPress directory.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => '',
            ],
            'ics_cron_freq'                          => [
                'type'    => 'string',
                'default' => 'hourly',
            ],
            'twig_cache'                             => [
                'type'     => 'string',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCache',
                    'tab'   => 'cache',
//                    'item'  => 'cache',
                    'label' => __('Templates cache improves site performance', 'open-source-event-calendar'),
                ],
                'default'  => '',
            ],
            'always_use_calendar_timezone'           => [
                'type'     => 'bool',
                'renderer' => [
                    'class' => 'Osec\Settings\Elements\SettingsCheckbox',
                    'tab'   => 'viewing-events',
                    'label' => __('Display events in <strong>calendar time zone</strong>', 'open-source-event-calendar'),
                    'help'  => __(
                        'If this box is checked events will appear in the calendar time zone with time zone 
                            information displayed on the event details page.',
                        'open-source-event-calendar'
                    ),
                ],
                'default'  => false,
            ],
        ];
    }

    /**
     * Register the standard setting values.
     *
     * @return void Method doesn't return.
     */
    protected function _register_standard_values()
    {
        foreach ($this->defaultOptions as $key => $option) {
            $renderer = null;
            $value    = $option['default'];
            if (isset($option['renderer'])) {
                $renderer = $option['renderer'];
            }
            $this->register(
                $key,
                $value,
                $option['type'],
                $renderer,
                OSEC_VERSION
            );
        }
    }

    /**
     * Register new option to be used.
     *
     * @param  string  $option  Name of option.
     * @param  mixed  $value  The value.
     * @param  string  $type  Option type to be used for validation.
     * @param  string  $renderer  Name of class to render the option.
     *
     * @return Settings Instance of self for chaining.
     */
    public function register(
        $option,
        mixed $value,
        $type,
        $renderer,
        $version = '2.0.0'
    ) {
        if ('deprecated' === $type) {
            unset($this->options[$option]);
        } elseif (
            ! isset($this->options[$option]) ||
            ! isset($this->options[$option]['version']) ||
            (string)$this->options[$option]['version'] !== (string)$version ||
            (
                isset($renderer['label']) &&
                isset($this->options[$option]['renderer']) &&
                (string)$this->options[$option]['renderer']['label'] !== (string)$renderer['label']
            ) ||
            (
                isset($renderer['help']) &&
                ( ! isset($this->options[$option]['renderer']['help']) || // handle the case when you are adding help
                  (string)$this->options[$option]['renderer']['help'] !== (string)$renderer['help'])
            )
        ) {
            $this->options[$option] = [
                'value'   => (isset($this->options[$option]))
                    ? $this->options[$option]['value']
                    : $value,
                'type'    => $type,
                'version' => $version,
            ];
            if (null !== $renderer) {
                $this->options[$option]['renderer'] = $renderer;
            }
        }

        return $this;
    }

    /**
     * Update translated strings, after introduction of `_noop` functions.
     *
     * @return void
     */
    protected function _update_name_translations()
    {
        $translations = $this->defaultOptions['enabled_views']['default'];
        $current      = $this->get('enabled_views');
        foreach ($current as $key => $view) {
            if (isset($translations[$key])) {
                $current[$key]['longname'] = $translations[$key]['longname'];
            }
        }
        $this->set('enabled_views', $current);
    }

    /*
     * Remove any (temp) content created by this class.
     */

    /**
     * Do things needed on every plugin upgrade.
     *
     * // TODO WE WILL NEED THIS SOMEWHERE ELSE
     */
    public function perform_upgrade_actions()
    {
        $option = $this->app->options;
        $option->set('osec_force_flush_rewrite_rules', true, true);
        $option->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);
        $option->set(ThemeLoader::OPTION_FORCE_CLEAN, true, true);
    }
}
