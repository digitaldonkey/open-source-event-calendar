<?php

namespace Osec\Command;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\App\View\Calendar\CalendarPageView;
use Osec\App\WpmlHelper;
use Osec\Exception\BootstrapException;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\{RenderCsv,
    RenderHtml,
    RenderIcal,
    RenderJson,
    RenderJsonP,
    RenderRedirect,
    RenderVoid,
    RenderXml,};

/**
 * The concrete command that renders the calendar.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Render_Calendar
 * @author     Time.ly Network Inc.
 */
class RenderCalendar extends CommandAbstract
{
    /**
     * @var string
     */
    protected $requestType;

    public function is_this_to_execute()
    {
        $calendar_page_id = $this->app->settings->get('calendar_page_id');
        if (empty($calendar_page_id)) {
            return false;
        }

        $page_ids_to_match = [$calendar_page_id] +
                             WpmlHelper::factory($this->app)->get_translations_of_page(
                                 $calendar_page_id
                             );
        foreach ($page_ids_to_match as $page_id) {
            if (is_page($page_id)) {
                $this->request->set_current_page($page_id);
                if ( ! post_password_required($page_id)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function setRenderStrategy(RequestParser $request): void
    {
        $this->requestType = $request->get('request_type');
        switch ($this->requestType) {
            case 'html':
                $this->renderStrategy = RenderHtml::factory($this->app);
                break;
            case 'json':
                $this->renderStrategy = RenderJson::factory($this->app);
                break;
            case 'jsonp':
                $this->renderStrategy = RenderJsonP::factory($this->app);
                break;
            case 'ical':
                $this->renderStrategy = RenderIcal::factory($this->app);
                break;
            case 'csv':
                $this->renderStrategy = RenderCsv::factory($this->app);
                break;
            case 'redirect':
                $this->renderStrategy = RenderRedirect::factory($this->app);
                break;
            case 'xml':
                $this->renderStrategy = RenderXml::factory($this->app);
                break;
            case 'void':
                $this->renderStrategy = RenderVoid::factory($this->app);
                break;
            default:
                throw new BootstrapException(
                    esc_html(
                        'Could not resolve render strategy for: ' . $this->requestType
                    )
                );
        }
    }

    public function do_execute()
    {
        FrontendCssController::factory($this->app) ->add_link_to_html_for_frontend();
        ScriptsFrontendController::factory($this->app)->load_frontend_js(true);
        return [
            'data'     => CalendarPageView::factory($this->app)->get_content($this->request),
            'callback' => RequestParser::get_param(
                'callback',
                null
            ),
            'caller'   => 'calendar',
        ];
    }
}
