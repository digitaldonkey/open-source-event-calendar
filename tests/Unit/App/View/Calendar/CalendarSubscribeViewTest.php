<?php

namespace Osec\Tests\Unit\App\View\Calendar;

use Osec\App\View\Calendar\CalendarSubscribeView;
use Osec\App\WpmlHelper;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * Subscribe button URLs: a site language (WPML) is added to the export filter, it never replaces it.
 * Before the fix, the single event's "Add event to..." lost `osec_post_ids` with WPML active and
 * exported the whole calendar.
 */
class CalendarSubscribeViewTest extends TestBase
{
    private array $args = [];

    private $capture;

    private RequestParser $saved_request;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        // A parsed request with the default "display_subscribe" (on), as on a calendar page.
        $this->saved_request = RequestParser::factory($osec_app);
        $request             = new RequestParser($osec_app);
        $request->parse();
        $osec_app->inject_object(RequestParser::class, $request);
        $this->capture = function (array $args): array {
            $this->args = $args;
            return $args;
        };
        add_filter('osec_subscribe_buttons_arguments', $this->capture);
    }

    public function tear_down()
    {
        global $osec_app;

        remove_filter('osec_subscribe_buttons_arguments', $this->capture);
        $osec_app->inject_object(WpmlHelper::class, new WpmlHelper($osec_app));
        $osec_app->inject_object(RequestParser::class, $this->saved_request);
        parent::tear_down();
    }

    public function test_language_is_added_to_the_single_event_filter()
    {
        global $osec_app;

        $osec_app->inject_object(
            WpmlHelper::class,
            new class ($osec_app) extends WpmlHelper {
                public function get_language()
                {
                    return 'de';
                }
            }
        );

        CalendarSubscribeView::factory($osec_app)->render_subscribe('&osec_post_ids=5', false, true);

        $this->assertNotEmpty($this->args, 'Subscribe buttons not rendered.');
        $this->assertStringContainsString('osec_post_ids=5', $this->args['url_webcal']);
        $this->assertStringContainsString('lang=de', $this->args['url_webcal']);
    }

    public function test_without_language_the_filter_stays()
    {
        global $osec_app;

        CalendarSubscribeView::factory($osec_app)->render_subscribe('&osec_post_ids=5', false, true);

        $this->assertStringContainsString('osec_post_ids=5', $this->args['url_webcal']);
        $this->assertStringNotContainsString('lang=', $this->args['url_webcal']);
    }
}
