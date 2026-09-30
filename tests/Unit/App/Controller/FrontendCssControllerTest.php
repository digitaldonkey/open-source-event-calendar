<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FrontendCssController;
use Osec\Tests\Utilities\TestBase;

/**
 * No theme ships precompiled CSS, so the CSS is always compiled on the site.
 *
 * @group css
 */
class FrontendCssControllerTest extends TestBase
{
    private mixed $saved_option;

    private mixed $saved_as_link;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_option  = $osec_app->options->get(FrontendCssController::COMPILED_CSS_KEY);
        $this->saved_as_link = $osec_app->settings->get('render_css_as_link');
    }

    public function tear_down()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_KEY, $this->saved_option, true);
        $osec_app->settings->set('render_css_as_link', $this->saved_as_link);
        parent::tear_down();
    }

    public function test_not_compiled_yet_links_the_compiling_route()
    {
        global $osec_app;

        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_KEY);
        $osec_app->settings->set('render_css_as_link', true);

        $url = FrontendCssController::factory($osec_app)->get_css_url();

        $this->assertStringNotContainsString('osec_parsed.css', $url);
        $this->assertStringContainsString(FrontendCssController::REQUEST_CSS_PARAM . '=0', $url);
    }

    public function test_invalidate_cache_always_compiles()
    {
        global $osec_app;

        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_KEY);

        $this->assertTrue(FrontendCssController::factory($osec_app)->invalidate_cache(null, false));
        $this->assertIsNumeric($osec_app->options->get(FrontendCssController::COMPILED_CSS_KEY));
    }
}
