<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\AppendContentController;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventColorView;
use Osec\App\View\Event\EventPostView;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\App\View\Event\EventTicketView;
use Osec\App\View\Event\EventTimeView;
use Osec\Bootstrap\App;

trait ViewRuntimePropsTrait
{
    public static function addRuntimePropertiesStatic(App $app, Event $event)
    {
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
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                'the_title',
                $event->get('post')->post_title,
                $event->get('post_id'),
                true
            )
        );
        $appendController = AppendContentController::factory($app);
        $appendController->set_append_content(false);
        $event->set_runtime(
            'filtered_content',
            apply_filters(
                'osec_the_content',
                apply_filters(
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                    'the_content',
                    $event->get('post')->post_content
                )
            )
        );
        $appendController->set_append_content(true);

        $taxonomyView = EventTaxonomyView::factory($app);
        $ticketView   = EventTicketView::factory($app);

        $event->set_runtime('color_style', $taxonomyView->get_color_style($event));
        $event->set_runtime('category_colors', $taxonomyView->get_category_colors($event));
        $event->set_runtime('ticket_url_label', $ticketView->get_tickets_url_label($event, false));
        $event->set_runtime('edit_post_link', get_edit_post_link($event->get('post_id')));
        $event->set_runtime('edit_post_instance_link', $event->get_instance_edit_link());
        $event->set_runtime('post_excerpt', EventPostView::factory($app)->trim_excerpt($event));
        $color = EventColorView::factory($app);
        $event->set_runtime('faded_color', $color->get_faded_color($event));
        $event->set_runtime('rgba_color', $color->get_rgba_color($event));
        $event->set_runtime('color', $taxonomyView->get_color_for_event($event));
        $event->set_runtime('categories', $taxonomyView->get_category_data($event));
        $event->set_runtime(
            'short_start_time',
            EventTimeView::factory($app)->format_time($event->get('start'))
        );
    }
}
