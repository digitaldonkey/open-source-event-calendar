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
    protected $_request_type;

    public function is_this_to_execute()
    {
        $settings = $this->app->settings;
        $calendar_page_id = $settings->get('calendar_page_id');
        if (empty($calendar_page_id)) {
            return false;
        }

        $page_ids_to_match = [$calendar_page_id] +
                             WpmlHelper::factory($this->app)->get_translations_of_page(
                                 $calendar_page_id
                             );
        foreach ($page_ids_to_match as $page_id) {

            if (is_page($page_id)) {
                $this->_request->set_current_page($page_id);
                if ( ! post_password_required($page_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function set_render_strategy(RequestParser $request)
    {
        $this->_request_type = $request->get('request_type');
        switch ($this->_request_type) {
            case 'html':
                FrontendCssController::factory($this->app)
                                     ->add_link_to_html_for_frontend();
                ScriptsFrontendController::factory($this->app)
                                         ->load_frontend_js(true);
                $this->_render_strategy = RenderHtml::factory($this->app);
                break;
            case 'json':
                $this->_render_strategy = RenderJson::factory($this->app);
                break;
            case 'jsonp':
                $this->_render_strategy = RenderJsonP::factory($this->app);
                break;
            case 'ical':
                $this->_render_strategy = RenderIcal::factory($this->app);
                break;
            case 'csv':
                $this->_render_strategy = RenderCsv::factory($this->app);
                break;
            case 'redirect':
                $this->_render_strategy = RenderRedirect::factory($this->app);
                break;
            case 'xml':
                $this->_render_strategy = RenderXml::factory($this->app);
                break;
            case 'void':
                $this->_render_strategy = RenderVoid::factory($this->app);
                break;
            default:
                throw new BootstrapException('Could not resolve render strategy: '.$this->_render_strategy);
        }
    }

    public function do_execute()
    {
        // TODO Shouldn't this be only render Strategy == HTML??

        return [
            'data'     => CalendarPageView::factory($this->app)->get_content($this->_request),
            'callback' => RequestParser::get_param(
                'callback',
                null
            ),
            'caller'   => 'calendar',
        ];
    }

}
