<?php

namespace Osec\App\View;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\WidgetController;
use Osec\Bootstrap\App;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Theme\ThemeLoader;
use WP_Widget;

/**
 * Widget base class
 *
 * @replaces Ai1ec_Embeddable
 */
abstract class WidgetAbstract extends WP_Widget
{
    /**
     * @var App
     */
    protected App $app;

    protected ?WidgetController $widgetController;

    /**
     * @var bool
     */
    protected bool $cssIsLoaded = false;

    /**
     * @param $_id
     * @param $name
     * @param  array  $widget_options
     * @param  array  $control_options
     *
     * @throws BootstrapException
     */
    public function __construct(protected $_id, $name, $widget_options = [], $control_options = [])
    {
        global $osec_app;
        $this->app              = $osec_app;
        $this->widgetController = WidgetController::factory($this->app);

        // Return if it is allready there
        if ($this->widgetController->getIfRegistered($_id)) {
            // This is weird.
            // TODO Let's remove this widget soon and replace it with a block.
            //
            return;
        }

        parent::__construct($this->_id, $name, $widget_options, $control_options);
        add_shortcode($this->_id, $this->shortcode(...));
        /* Filter doc @see \Osec\App\Model\PostTypeEvent\EventEditing->strip_shortcode_tag() */
        add_filter(
            'osec_content_remove_shortcode_' . $this->_id,
            $this->is_this_to_remove_from_event(...)
        );

        $this->register_javascript_widget($this->_id, $this);
        add_action('osec_js_translations', $this->add_js_translations(...));
    }

    /**
     * Register the widget to the controller.
     *
     * @param  string  $id_base
     */
    abstract public function register_javascript_widget($id_base);

    /**
     * Register widget class with current WP instance.
     * This must be static as otherwise the class would be instantiated twice,
     * one to register it and the other from WordPress.
     *
     * @return string
     */
    public static function register_widget()
    {
        throw new Exception('This should be implemented in child class');
    }

    /**
     * Return options needed for thw "Widget creator page
     *
     * @return array
     */
    abstract public function get_configurable_for_widget_creation();

    /**
     * The human-readable name of the widget.
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * The icon class associated with the widget. Defaults to calendar.
     *
     * @return string
     */
    public function get_icon()
    {
        return 'ai1ec-fa ai1ec-fa-calendar';
    }

    /**
     * Checks and returns widget requirements.
     *
     * @return string
     */
    abstract public function check_requirements();

    /**
     * @return array
     */
    public function add_js_translations(array $translations)
    {
        $translations['javascript_widgets'][$this->_id] = $this->get_js_widget_configurable_defaults();

        return $translations;
    }

    /**
     * Get values which are configurable in the Javascript widget.
     * Some things might not be configurable.
     *
     * @return array
     */
    abstract public function get_js_widget_configurable_defaults();

    /**
     * Widget function.
     *
     * Outputs the given instance of the widget to the front-end.
     *
     * @param  array  $args  Display arguments passed to the widget
     * @param  array  $instance  The settings for this widget instance
     *
     * @return void
     */
    public function widget($args, $instance)
    {
        $defaults = $this->get_defaults();
        $instance = wp_parse_args($instance, $defaults);
        $this->add_js();
        $args['widget_html'] = $this->get_content($instance);
        if ( ! empty($args['widget_html'])) {
            $args['title'] = $instance['title'];
            $args          = $this->filterArgs($args);
            FrontendCssController::factory($this->app)
                                 ->add_link_to_html_for_frontend();
            // Display theme
            ThemeLoader::factory($this->app)
                       ->get_file('widget.twig', $args)
                       ->render();
        }
    }

    /**
     * Get default values for shortcode or widget.
     *
     * @return array
     */
    abstract public function get_defaults();

    /**
     * Add the required javascript for the widget. Needed for shortcode and
     * WordPress widget
     */
    abstract public function add_js();

    /**
     * Create the html for the widget. Shared by all versions.
     *
     * @param  bool  $remote_request  whether the request is for a remote site or
     *  not (useful to inline CSS)
     */
    abstract public function get_content(array $args_for_widget, $remote = false);

    /**
     * Filters default widget parameters like classes, html elements before and
     * after title or widget. Useful for Feature Events widget which has
     * different title styling.
     *
     * @param  array  $args  Widget arguments.
     *
     * @return array Filtered arguments.
     */
    protected function filterArgs($args)
    {
        return $args;
    }

    /**
     * Renders shortcode
     *
     * @param  array  $atts
     * @param  string  $content
     */
    public function shortcode($atts, $content = null)
    {
        $defaults = $this->get_defaults();
        $atts     = shortcode_atts($defaults, $atts);
        $this->add_js();

        return $this->get_content($atts);
    }

    /**
     * Renders js widget
     *
     * @param  array  $args
     */
    public function javascript_widget($args)
    {
        $defaults = $this->get_defaults();
        $args     = wp_parse_args($args, $defaults);

        return $this->get_content($args, true);
    }

    /**
     * Returns whether this shortcode should be removed from event content.
     *
     * @return bool True.
     */
    public function is_this_to_remove_from_event()
    {
        return true;
    }
}
