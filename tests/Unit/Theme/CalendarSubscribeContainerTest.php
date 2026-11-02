<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;

/**
 * The calendar script replaces the subscribe buttons after a filter change by AJAX with
 * `.subscribe-container` (public/js/pages/calendar.js). Every bundled theme's calendar.twig must wrap the
 * buttons in an element with that class, or the button keeps offering the unfiltered calendar
 * (plana used only `osec-subscribe-container`).
 *
 * @group theme
 */
class CalendarSubscribeContainerTest extends TestBase
{
    private array $saved = [];

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        foreach (
            [
                'osec_current_theme',
                LessController::DB_KEY_FOR_LESS_VARIABLES,
                FrontendCssController::COMPILED_CSS_CACHE_KEY,
            ] as $key
        ) {
            $this->saved[$key] = $osec_app->options->get($key);
        }
    }

    public function tear_down()
    {
        global $osec_app;

        foreach ($this->saved as $key => $value) {
            $osec_app->options->set($key, $value, true);
        }
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        parent::tear_down();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function bundled_themes(): array
    {
        return [
            'vortex' => ['vortex'],
            'plana'  => ['plana'],
            'umbra'  => ['umbra'],
            'gamma'  => ['gamma'],
        ];
    }

    /**
     * @dataProvider bundled_themes
     */
    public function test_subscribe_buttons_sit_in_subscribe_container(string $theme)
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to($theme, false);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));

        $html = ThemeLoader::factory($osec_app)
            ->get_file('calendar.twig', ['subscribe_buttons' => '<span id="osec-test-subscribe"></span>'], false)
            ->get_content();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        $buttons = $doc->getElementById('osec-test-subscribe');
        $this->assertNotNull($buttons, "$theme: subscribe buttons not rendered");

        $classes = preg_split('/\s+/', (string) $buttons->parentNode->getAttribute('class'));
        $this->assertContains('subscribe-container', $classes, "$theme: wrapper lacks the class the calendar script replaces");
    }
}
