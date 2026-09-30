<?php

namespace Osec\App\Controller;

use Osec\App\View\Event\EventContentView;
use Osec\Bootstrap\OsecBaseClass;

/**
 * React Big Calendar block (early development).
 *
 * Renders an empty container; view.js mounts the calendar into it and loads events from the
 * osec/v1/days REST route.
 */
class BigCalendarBlockController extends OsecBaseClass
{
    public function registerCalendarBlock()
    {
        register_block_type(
            OSEC_PATH . 'blocks/build/react-big-calendar',
            [
                'render_callback' => function (array $attributes, string $content): string {
                    if (EventContentView::factory($this->app)->is_filtering_content()) {
                        return '';
                    }
                    $id = wp_unique_id('osec-react-big-calendar-');
                    $attributes['id'] = $id;

                    return $content . '<div '
                        . get_block_wrapper_attributes([
                            'id'    => $id,
                            'class' => 'osec-react-big-calendar',
                        ])
                        . ' data-props="' . esc_attr(wp_json_encode($attributes)) . '"></div>';
                },
            ]
        );
    }
}
