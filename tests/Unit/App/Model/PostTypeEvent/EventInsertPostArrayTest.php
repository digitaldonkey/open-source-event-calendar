<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * Filters on wp_insert_post_data receive the post data exactly as passed, which WordPress documents as an array.
 */
class EventInsertPostArrayTest extends TestBase
{
    public function test_new_event_passes_post_data_as_array()
    {
        global $osec_app;

        $received = null;
        // Advanced Custom Fields and Secure Custom Fields read the unsanitized post data like this.
        $filter = static function ($data, $postarr, $unsanitized_postarr) use (&$received) {
            $received = $unsanitized_postarr;
            if (isset($unsanitized_postarr['post_content'])) {
                $data['post_content'] = $unsanitized_postarr['post_content'];
            }
            return $data;
        };
        add_filter('wp_insert_post_data', $filter, 10, 3);
        try {
            $post_id = (new Event(
                $osec_app,
                [
                    'post'          => [
                        'post_status'  => 'publish',
                        'post_type'    => OSEC_POST_TYPE,
                        'post_title'   => 'Event',
                        'post_content' => 'Event description',
                    ],
                    'start'         => new DT('2026-07-06 09:00:00', 'UTC'),
                    'end'           => new DT('2026-07-06 10:00:00', 'UTC'),
                    'allday'        => false,
                    'instant_event' => false,
                    'timezone_name' => 'UTC',
                ]
            ))->save();
        } finally {
            remove_filter('wp_insert_post_data', $filter, 10);
        }

        self::assertIsArray($received);
        self::assertGreaterThan(0, $post_id);
        self::assertSame('Event description', get_post_field('post_content', $post_id));
    }
}
