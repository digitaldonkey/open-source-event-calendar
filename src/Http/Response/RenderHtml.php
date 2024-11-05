<?php

namespace Osec\Http\Response;

use Osec\App\Controller\AppendContentController;

/**
 * Render the request as html.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Html
 * @author     Time.ly Network Inc.
 */
class RenderHtml extends RenderStrategyAbstract
{

    /**
     * Twig page content placeholder.
     */
    const CALENDAR_PLACEHOLDER = '<!-- OSEC_PAGE_CONTENT_PLACEHOLDER -->';

    /**
     * @var string the event html.
     */
    protected $_html;

    /**
     * @var string The html for the footer of the event.
     */
    protected $_html_footer = '';

    /**
     * Caller identifier. Just for paranoid check in append_content method.
     * Expected 'calendar' or none.
     *
     * @var string
     */
    protected $_caller = '';

    /**
     * Registers proper filters for content modifications.
     *
     * @param  array  $params  Function params.
     *
     * @return void Method does not return.
     */
    public function render(array $params)
    {
        $this->_html = $params[ 'data' ];
        if (isset($params[ 'caller' ])) {
            $this->_caller = $params[ 'caller' ];
        }
        if (isset($params[ 'footer' ])) {
            $this->_html_footer = $params[ 'footer' ];
        }
        if (isset($params[ 'is_event' ])) {
            // Filter event post content, in single- and multi-post views
            add_filter('the_content', [$this, 'event_content'], PHP_INT_MAX - 1);

            return;
        }
        // Replace page content - make sure it happens at (almost) the very end of
        add_filter('the_content', [$this, 'append_content'], PHP_INT_MAX - 1);
    }

    /**
     * event_content function
     *
     * Filter event post content by inserting relevant details of the event
     * alongside the regular post content.
     *
     * @param  string  $content  Post/Page content
     *
     * @return string         Post/Page content
     **/
    public function event_content($content)
    {
        if ( ! AppendContentController::factory($this->app)->append_content()) {
            $content = '';
        }
        $to_return = $this->_html.$content;
        if (isset($this->_html_footer)) {
            $to_return .= $this->_html_footer;
        }

        /**
         * Alter Event content html
         *
         * @since 1.00
         *
         * @param  string  $content  Html content.
         */
        return apply_filters('osec_event_content', $to_return);
    }

    /**
     * Append locally generated content to normal page content. By default,
     * first checks if we are in The Loop before outputting to prevent multiple
     * calendar display - unless setting is turned on to skip this check.
     * We should not append full calendar body to single event content as it
     * leads to "calendar" nesting if default calendar page contains calendar
     * shortcode.
     *
     * @param  string  $content  Post/Page content
     *
     * @return string          Modified Post/Page content
     */
    public function append_content($content)
    {

        if ('calendar' === $this->_caller && ! AppendContentController::factory($this->app)
                                                                      ->append_content()) {
            return $content;
        }

        // Include any admin-provided page content in the placeholder specified in
        // the calendar theme template.
        if ($this->app->settings->get('skip_in_the_loop_check') || in_the_loop()) {
            $content = str_replace(
                self::CALENDAR_PLACEHOLDER,
                $content,
                $this->_html
            );
            $content .= $this->_html_footer;
        }

        return $content;
    }

}
