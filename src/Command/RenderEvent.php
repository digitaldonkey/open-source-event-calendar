<?php

namespace Osec\Command;

use Osec\App\Controller\AccessControl;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventSingleView;
use Osec\Http\Request\ParamType;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderRedirect;

/**
 * The concrete command that renders the event.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Render_Event
 * @author     Time.ly Network Inc.
 */
class RenderEvent extends RenderCalendar
{
    public function is_this_to_execute()
    {
        global $post;
        if (
            ! isset($post) ||
            ! is_object($post) ||
            $post->ID <= 0 ||
            post_password_required($post->ID)
        ) {
            return false;
        }

        return AccessControl::is_our_post_type();
    }

    public function do_execute()
    {
        // If not on the single event page, return nothing.
        if ( ! is_single()) {
            return [
                'data'     => '',
                'is_event' => true,
            ];
        }

        // Else proceed with rendering valid event. Fetch all relevant details.
        $instance = RequestParser::get_param('instance_id', -1, ParamType::Int);
        // Present and not empty, but below 1. An array reads as the defaults: null text, -1.
        if (
            RequestParser::has_param('instance_id')
            && '' !== RequestParser::get_param('instance_id', null, ParamType::Text)
            && $instance < 1
        ) {
            wp_die(
                esc_html__('Bad request: instance_id must be a positive number.', 'open-source-event-calendar'),
                esc_html__('Bad Request', 'open-source-event-calendar'),
                ['response' => 400]
            );
        }
        $event = new Event($this->app, get_the_ID(), $instance);
        // Not a date of this event (any more): show the event itself, with a notice.
        if ($instance > 0 && null === $event->get('instance_id')) {
            RenderRedirect::local_redirect(remove_query_arg('instance_id'), ['osec_date_not_found' => 1], 301);
        }
        $view        = EventSingleView::factory($this->app);
        $footer_html = $view->get_footer($event);
        FrontendCssController::factory($this->app)->add_link_to_html_for_frontend();
        ScriptsFrontendController::factory($this->app)->load_frontend_js(false);

        // If requesting event by JSON (remotely), return fully rendered event.
        if ('html' !== $this->requestType) {
            return [
                'data'     => [
                    'html' => $view->get_full_article($event, $footer_html),
                ],
                'callback' => RequestParser::get_param('callback', null, ParamType::Callback),
            ];
        }

        $notice = '';
        if (RequestParser::has_param('osec_date_not_found')) {
            $notice = '<div class="ai1ec-alert ai1ec-alert-info">' . esc_html__(
                "We couldn't find the specific date for this event your URL requested. Showing the main event instead.",
                'open-source-event-calendar'
            ) . '</div>';
        }

        // Else return event details as components.
        return [
            'data' => $notice . wp_kses(
                $view->get_content($event),
                $this->app->kses->allowed_html_frontend()
            ),
            'is_event' => true,
            'footer'   => $footer_html,
        ];
    }
}
