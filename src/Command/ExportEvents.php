<?php

namespace Osec\Command;

use Osec\App\Controller\ImportExportController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\WpmlHelper;
use Osec\Helper\IntegerHelper;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderIcal;

/**
 * The concrete command that export events.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Export_Events
 * @author     Time.ly Network Inc.
 */
class ExportEvents extends CommandAbstract
{
    /**
     * @var string The name of the old exporter controller.
     */
    public const EXPORT_CONTROLLER = 'osec_exporter_controller';

    /**
     * @var string The name of the old export method.
     */
    public const EXPORT_METHOD = 'export_events';

    /**
     * @var array Request parameters
     */
    protected array $params;

    public function is_this_to_execute()
    {
        $params = $this->get_parameters();
        if (false === $params) {
            return false;
        }
        if (
            $params['action'] === self::EXPORT_METHOD &&
            $params['controller'] === self::EXPORT_CONTROLLER
        ) {
            $params['tag_ids']  = RequestParser::get_param(
                'osec_tag_ids',
                false
            );
            $params['cat_ids']  = RequestParser::get_param(
                'osec_cat_ids',
                false
            );
            $params['post_ids'] = RequestParser::get_param(
                'osec_post_ids',
                false
            );
            $params['lang']     = RequestParser::get_param(
                'lang',
                false
            );
            $params['no_html']  = (bool)RequestParser::get_param(
                'no_html',
                false
            );
            $this->params      = $params;

            return true;
        }

        return false;
    }

    public function setRenderStrategy(RequestParser $request): void
    {
        $this->renderStrategy = RenderIcal::factory($this->app);
    }

    public function do_execute()
    {
        $cat_ids  = $this->params['cat_ids'];
        $tag_ids  = $this->params['tag_ids'];
        $post_ids = $this->params['post_ids'];
        if ( ! empty($this->params['lang'])) {
            WpmlHelper::factory($this->app)->set_language($this->params['lang']);
        }
        $args   = ['do_not_export_as_calendar' => false];
        $filter = [];
        if ($cat_ids) {
            $filter['cat_ids'] = IntegerHelper::convert_to_int_list(
                ',',
                $cat_ids
            );
        }
        if ($tag_ids) {
            $filter['tag_ids'] = IntegerHelper::convert_to_int_list(
                ',',
                $tag_ids
            );
        }
        if ($post_ids) {
            $args['do_not_export_as_calendar'] = true;
            $filter['post_ids']                = IntegerHelper::convert_to_int_list(
                ',',
                $post_ids
            );
        }

        /**
         * Alter export events filter.
         *
         * @since 1.0
         *
         * @param  array  $filter
         */
        $filter = apply_filters('osec_export_filter', $filter);
        // when exporting events by post_id, do not look up the event's start/end date/time
        $start  = ($post_ids !== false)
            // TODO hidden constants foe default export Range is +-3 years.
            ? new DT('-3 years') // Include any events ending today
            : new DT(time() - 24 * 60 * 60); // Include any events ending today
        $end    = new DT('+3 years');
        $search = EventSearch::factory($this->app);
        $params = ['no_html' => $this->params['no_html']];

        $export_controller = new ImportExportController($this->app, ['ics'], $params);

        $args['events'] = $this->unique_events(
            $search->get_events_between($start, $end, $filter)
        );
        $ics            = $export_controller->export_events('ics', $args);

        return ['data' => $ics];
    }

    /**
     * Return unique events list.
     *
     * @param  array  $events  List of Event objects.
     *
     * @return array Unique Events from input.
     */
    public function unique_events(array $events)
    {
        $ids    = [];
        $output = [];
        foreach ($events as $event) {
            $id = (int)$event->get('post_id');
            if ( ! isset($ids[$id])) {
                $output[] = $event;
                $ids[$id] = true;
            }
        }

        return $output;
    }
}
