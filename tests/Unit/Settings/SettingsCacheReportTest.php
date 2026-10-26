<?php

namespace Osec\Tests\Unit\Settings;

use Osec\App\Controller\FrontendCssController;
use Osec\Settings\Elements\SettingsCache;
use Osec\Tests\Utilities\TestBase;

/**
 * The cache report (Settings) says where the compiled CSS is and how the page gets it.
 *
 * @group cache
 */
class SettingsCacheReportTest extends TestBase
{
    private array $saved = [];

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved = [
            'state' => $osec_app->options->get(FrontendCssController::CSS_OPTION),
            'link'  => $osec_app->settings->get('render_css_as_link'),
        ];
        $osec_app->inject_object(FrontendCssController::class, new FrontendCssController($osec_app));
    }

    public function tear_down()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::CSS_OPTION, $this->saved['state'], true);
        $osec_app->settings->set('render_css_as_link', $this->saved['link']);
        $osec_app->inject_object(FrontendCssController::class, new FrontendCssController($osec_app));
        parent::tear_down();
    }

    /**
     * The URL row of the report, by label.
     */
    private function url_row(): array
    {
        global $osec_app;

        $rows = SettingsCache::factory($osec_app)->get_twig_cache_args()['css_location'];
        foreach ($rows as $row) {
            if (__('URL', 'open-source-event-calendar') === $row['label']) {
                return $row;
            }
        }
        $this->fail('The cache report has no URL row.');
    }

    /**
     * A stored state whose file is gone, with "Link CSS" off: the page prints the CSS inline. The report showed an
     * empty "URL:" and said nothing about it.
     */
    public function test_inline_css_is_named_instead_of_an_empty_url()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::CSS_OPTION, [
            'engine'  => 'file',
            'root'    => 'uploads',
            'file'    => 'osec-missing-' . wp_generate_password(6, false) . '.css',
            'ver'     => 'abc1234',
            'started' => microtime(true),
        ], true);
        $osec_app->settings->set('render_css_as_link', false);

        $row = $this->url_row();

        $this->assertNotSame('', $row['value']);
        $this->assertStringContainsString('inline', $row['value']);
        $this->assertFalse($row['code']);
    }

    /**
     * With "Link CSS" on, the URL is the PHP route and stays a code value.
     */
    public function test_route_url_is_shown_as_code()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::CSS_OPTION, [
            'engine'  => 'db',
            'ver'     => 'abc1234',
            'started' => microtime(true),
        ], true);
        $osec_app->settings->set('render_css_as_link', true);

        $row = $this->url_row();

        $this->assertStringContainsString(FrontendCssController::REQUEST_CSS_PARAM . '=abc1234', $row['value']);
        $this->assertTrue($row['code']);
    }
}
