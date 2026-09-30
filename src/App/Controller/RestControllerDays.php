<?php

namespace Osec\App\Controller;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\Model\PostTypeEvent\RestEvent;
use Osec\App\Model\TaxonomyAdapter;
use Osec\Bootstrap\OsecBaseInitialized;
use WP_REST_Request;

class RestControllerDays extends OsecBaseInitialized
{
    /**
     * Largest range one request may ask for. The calendar asks for at most six weeks (month view).
     */
    public const MAX_RANGE_DAYS = 62;

    public function initialize()
    {
        $app = $this->app;

        add_action('rest_api_init', function () use ($app) {
            register_rest_route(
                'osec/v1',
                '/days',
                [
                    'methods'             => 'GET',
                    'callback'            => function (WP_REST_Request $request) use ($app) {
                        return RestControllerDays::factory($app)->getRange($request);
                    },
                    'args'                => $this->getRestArgs(),
                    'permission_callback' => function () {
                        return true;
                    },
                ],
            );
        });
    }

    public function getRange(WP_REST_Request $request)
    {
        if (! is_wp_error($request)) {
            $start = new DT($request->get_param('start'), 'UTC');

            // Single day
            $events = [];
            if (is_null($request->get_param('end'))) {
                // TODO
                $events = EventSearch::factory($this->app)->get_events_for_day(
                    $start,
                    $filter = []
                );
            } else {
                $span = $request->get_param('end') - $request->get_param('start');
                if ($span < 0 || $span > self::MAX_RANGE_DAYS * DAY_IN_SECONDS) {
                    return new \WP_Error(
                        'osec_invalid_range',
                        sprintf(
                            /* translators: %d: number of days */
                            __('The range must be positive and at most %d days.', 'open-source-event-calendar'),
                            self::MAX_RANGE_DAYS
                        ),
                        ['status' => 400]
                    );
                }
                $end = new DT($request->get_param('end'), 'UTC');

                // TODO
                //  - Adjust to day end?
                //  - Swap start/end necessary? -> yes.
                // [$a, $b] = [$b, $a];
                // * @param  DT  $start  Limit to events starting after this.
                // * @param  DT  $end  Limit to events starting before this.



                $events = EventSearch::factory($this->app)->get_events_between(
                    $start,
                    $end,
                    $filter = [],
                    $spanning = true,
                );
            }
            $processed = RestEvent::factory($this->app)->processEvents($events);
            return new \WP_REST_Response([
                'events' => $processed,
            ]);
        }

        return new \WP_Error(401, __('Not allowed', 'open-source-event-calendar'));
    }

    public function updateMeta(array $events): void
    {
        $post_ids = [];
        foreach ($events as $event) {
            $post_ids[] = (int)$event->get('post_id');
        }
        update_meta_cache('post', $post_ids);
        TaxonomyAdapter::factory($this->app)->update_meta($post_ids);
    }

    /**
     * Request args
     *  - if only start is given we assume all events on the given day.
     *  - if start and end are given, we assume between including first and last day.
     *  - dates are Unix timestamps.
     * @return array[]
     */
    public function getRestArgs(): array
    {
        return [
            'start' => [
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => [$this, 'sanitizeDate'],
            ],
            'end'   => [
                'type'              => 'integer',
                'required'          => false,
                'sanitize_callback' => [$this, 'sanitizeDate'],
            ],
        ];
    }

    public function preprocess(array $events): array
    {
        return $events;
    }

    public static function sanitizeDate($value, WP_REST_Request $request, $param): ?int
    {
        if (DT::isValidTimeStamp($value)) {
            return (int) $value;
        }
        return null;
    }
}
