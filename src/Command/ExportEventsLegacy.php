<?php

namespace Osec\Command;

use Osec\Http\Request\RequestParser;

/**
 * The concrete command that export events.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Export_Events
 * @author     Time.ly Network Inc.
 */
class ExportEventsLegacy extends ExportEvents
{
    /**
     * @var string The name of the old exporter controller.
     */
    public const EXPORT_CONTROLLER = 'ai1ec_exporter_controller';

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
                'ai1ec_tag_ids',
                false
            );
            $params['cat_ids']  = RequestParser::get_param(
                'ai1ec_cat_ids',
                false
            );
            $params['post_ids'] = RequestParser::get_param(
                'ai1ec_post_ids',
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
}
