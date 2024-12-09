<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\AppendContentController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\TaxonomyAdapter;
use Osec\App\View\Event\EventColorView;
use Osec\App\View\Event\EventPostView;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\App\View\Event\EventTicketView;
use Osec\App\View\Event\EventTimeView;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Request\RequestParser;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * The abstract class for a view.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 *
 * @replaces Ai1ec_Calendar_View_Abstract
 */
abstract class AbstractView extends OsecBaseClass
{
    /**
     * @var RequestParser The request object
     */
    protected RequestParser $request;

    /**
     * Public constructor
     *
     * @param  App  $app
     * @param  RequestParser  $request
     */
    public function __construct(App $app, RequestParser $request)
    {
        parent::__construct($app);
        $this->request = $request;
    }

    /**
     * Get extra arguments specific for the view
     *
     * @param  int|bool  $exact_date  the exact date used to display the view.
     *
     * @return array The view arguments with the extra parameters added.
     */
    public function get_extra_arguments(array $view_args, $exact_date)
    {
        $offset             = $this->get_name() . '_offset';
        $view_args[$offset] = $this->request->get($offset);
        if (false !== $exact_date) {
            $view_args['exact_date'] = $exact_date;
        }

        return $view_args;
    }

    /**
     * Get the machine name for the view
     *
     * @return string The machine name of the view.
     */
    abstract public function get_name();

    /**
     * Get extra arguments specific for the view's template
     *
     * @return array The template arguments with the extra parameters added.
     */
    public function get_extra_template_arguments(array $args)
    {
        /**
         * Change the Twig arguments
         *
         * Before template rendering you may want to add stuff
         *
         * Used when rendering calendar page or widget.
         *
         * @since 1.0
         *
         * @param  array  $args  Event location.
         */
        return apply_filters('osec_calendar_view_template_alter', $args);
    }

    /**
     *
     * @param  string  $exact_date
     */
    protected function _create_link_for_day_view($exact_date)
    {
        $href = HtmlFactory::factory($this->app)
                           ->create_href_helper_instance(
                               [
                                   'action'     => 'oneday',
                                   'exact_date' => $exact_date,
                               ]
                           );

        return $href->generate_href();
    }

    /**
     * Get the view html
     *
     * @return string
     */
    protected function _get_view(array $view_args)
    {
        $view = $this->get_name();
        $file = ThemeLoader::factory($this->app)
                           ->get_file($view . '.twig', $view_args, false);

        /**
         * Alter View Html output
         *
         * @since 1.0
         *
         * @param  array  $args  View Arguments.
         *
         * @param  string  $view  Calendar View Name
         */
        return apply_filters('osec_get_' . $view . '_view_content_alter', $file->get_content(), $view_args);
    }

    /**
     * Render the view and return the content
     *
     * @return string the html of the view
     */
    abstract public function get_content(array $view_args);

    /**
     * Applies filters to view args for front end rendering
     */
    protected function _apply_filters_to_args(array $args): array
    {
        $view = $this->get_name();

        return ThemeLoader::factory($this->app)
                          ->apply_filters_to_args($args, $view . '.twig', false);
    }

    /**
     * Prepare week specific event start/end timestamps.
     *
     * @param  Event  $event  Instance of event.
     *
     * @return array Start and end respectively in 0 and 1 positions.
     */
    protected function _get_view_specific_timestamps(Event $event)
    {
        if ($event->is_allday()) {
            // reset to be day-contained with respect to current timezone
            $event_start = (new DT($event->get('start'), 'sys.default'))
                ->set_time(0, 0, 0)->format();
            $event_end   = (new DT($event->get('end'), 'sys.default'))
                ->set_time(0, 0, 0)->format();
        } else {
            $event_start = $event->get('start')->format();
            $event_end   = $event->get('end')->format();
        }

        return [$event_start, $event_end];
    }

    /**
     * Update metadata for retrieved events.
     *
     * This speeds up further metadata requests.
     *
     * @param  array  $events  List of events retrieved.
     *
     * @return void
     */
    protected function _update_meta(array $events)
    {
        $post_ids = [];
        foreach ($events as $event) {
            $post_ids[] = (int)$event->get('post_id');
        }
        update_meta_cache('post', $post_ids);
        TaxonomyAdapter::factory($this->app)->update_meta($post_ids);
    }

    /**
     * Gets the navigation bar HTML.
     *
     * @param  array  $nav_args  Args for the navigation bar template, including
     *                       'no_navigation' which determines whether to show it
     *
     * @return string
     */
    protected function _get_navigation(array $nav_args)
    {
        /**
         * Add Html at calendar navigarion header.
         *
         * Used when rendering calendar page or widget.
         *
         * @since 1.0
         *
         * @param  array  $event  Current Event Object.
         *
         * @param  array  $html  Event location.
         */
        $nav_args['contribution_buttons'] = apply_filters('osec_contribution_buttons', '', 'html', 'render-command');
        if ($nav_args['no_navigation']) {
            return '';
        }

        return ThemeLoader::factory($this->app)
                          ->get_file('navigation.twig', $nav_args, false)
                          ->get_content();
    }

    /**
     * Calls the get_*_pagination_links method for the current view type and
     * renders its result, returning the rendered pagination links.
     *
     * @param  array  $args  Current request arguments
     * @param  string  $title  Title to display in datepicker button
     *
     * @return string
     */
    protected function _get_pagination(array $args, $title, $title_short = '')
    {
        /* @uses OnedayView:get_oneday_pagination_links() */
        $method = 'get_' . $this->get_name() . '_pagination_links';
        $args   = [
            'links'     => $this->$method($args, $title, $title_short),
            'data_type' => $args['data_type'],
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('pagination.twig', $args, false)
                          ->get_content();
    }

    /**
     * Adds runtime properties to the event.
     */
    protected function _add_runtime_properties(Event $event)
    {
        global $post;
        $original_post      = $post;
        $post               = $event->get('post');
        $instance_permalink = get_permalink(
            $event->get('post_id')
        );
        $instance_permalink = add_query_arg(
            'instance_id',
            $event->get('instance_id'),
            $instance_permalink
        );
        $event->set_runtime('instance_permalink', $instance_permalink);

        $event->set_runtime(
            'filtered_title',
            apply_filters(
                'the_title',
                $event->get('post')->post_title,
                $event->get('post_id'),
                true
            )
        );
        $appendController = AppendContentController::factory($this->app);
        $appendController->set_append_content(false);
        $event->set_runtime(
            'filtered_content',
            apply_filters(
                'osec_the_content',
                apply_filters(
                    'the_content',
                    $event->get('post')->post_content
                )
            )
        );
        $appendController->set_append_content(true);

        $taxonomyView = EventTaxonomyView::factory($this->app);
        $ticketView   = EventTicketView::factory($this->app);

        $event->set_runtime('color_style', $taxonomyView->get_color_style($event));
        $event->set_runtime('category_colors', $taxonomyView->get_category_colors($event));
        $event->set_runtime('ticket_url_label', $ticketView->get_tickets_url_label($event, false));
        $event->set_runtime('edit_post_link', get_edit_post_link($event->get('post_id')));
        $event->set_runtime(
            'edit_post_instance_link',
            admin_url(
                'post.php?post=' . $event->get('post_id') .
                '&action=edit&instance=' . $event->get('instance_id', 1)
            )
        );
        $event->set_runtime('post_excerpt', EventPostView::factory($this->app)->trim_excerpt($event));
        $color = EventColorView::factory($this->app);
        $event->set_runtime('faded_color', $color->get_faded_color($event));
        $event->set_runtime('rgba_color', $color->get_rgba_color($event));
        $event->set_runtime(
            'short_start_time',
            EventTimeView::factory($this->app)->format_time($event->get('start'))
        );
        $this->_add_view_specific_runtime_properties($event);
        $post = $original_post;
    }

    /**
     * If some views have specific runtime properties they must extend this method
     */
    protected function _add_view_specific_runtime_properties(Event $event)
    {
    }

    protected function getFilterDefaults($view_args): array
    {
        /**
         * Do something mysterious to prepare the filter.
         *
         * You should you really know what you are doing, because I don't.
         *
         * @since 1.0
         *
         * @param  array  $filter  Default filters
         * @param  array  $view_args  View args
         * @param  bool  $show_unique_only  If only unique events should be shown
         * @param  string  $callingFrom  Class name of the view requesting default filtsers.
         */
        return apply_filters(
            'osec_events_relative_to_filter_defaults',
            [
                'post_ids'     => $view_args['post_ids'],
                'auth_ids'     => $view_args['auth_ids'],
                'cat_ids'      => $view_args['cat_ids'],
                'tag_ids'      => $view_args['tag_ids'],
                'instance_ids' => $view_args['instance_ids'],
            ],
            $view_args,
            /**
             * Show only unique events
             *
             * TODO: Not sure about the actual effects of this.
             * Default seems false, but I didn't see any non uniques showing up yet.
             *
             * @since 1.0
             *
             * @param  bool  $show_unique_only
             */
            apply_filters('osec_show_unique_events', false)
        );
    }

    protected function getBelowToolbarHtml(string $type, array $view_args): string
    {
        /**
         * Add Html below the toolbar on calendar views.
         *
         * @param  string  $html  Return any html string.
         * @param  string  $type
         * πlaram array $view_args
         */
        return apply_filters(
            'osec_below_toolbar_html',
            '',
            $type,
            $view_args
        );
    }
}
