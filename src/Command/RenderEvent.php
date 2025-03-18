<?php

namespace Osec\Command;

use Osec\App\Controller\AccessControl;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventSingleView;
use Osec\Http\Request\RequestParser;

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
        // phpcs:ignore WordPress.Security.NonceVerification
        $instance = isset($_REQUEST['instance_id']) ? (int) $_REQUEST['instance_id'] : -1;
        $event       = new Event($this->app, get_the_ID(), $instance);
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
                'callback' => RequestParser::get_param('callback', null),
            ];
        }

        // Else return event details as components.
        return [
            'data'     => $view->get_content($event),
            'is_event' => true,
            'footer'   => $footer_html,
        ];
    }
}
