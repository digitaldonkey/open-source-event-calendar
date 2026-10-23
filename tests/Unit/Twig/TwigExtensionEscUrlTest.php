<?php

namespace Osec\Tests\Unit\Twig;

use Osec\Theme\ThemeLoader;
use Osec\Tests\Utilities\TestBase;

/**
 * The |esc_url Twig filter checks the protocol and is not escaped again.
 *
 * @group escaping
 * @group security
 */
class TwigExtensionEscUrlTest extends TestBase
{
    private function render(?string $url): string
    {
        global $osec_app;

        $template = ThemeLoader::factory($osec_app)
                               ->get_twig_instance()
                               ->createTemplate('<a href="{{ url|esc_url }}">');

        return $template->render(['url' => $url]);
    }

    public function test_escapes_ampersand_once()
    {
        $this->assertSame('<a href="https://example.com/?a=1&#038;b=2">', $this->render('https://example.com/?a=1&b=2'));
    }

    public function test_rejects_javascript_protocol()
    {
        $this->assertSame('<a href="">', $this->render('javascript:alert(1)'));
    }

    public function test_null_gives_empty_string()
    {
        $this->assertSame('<a href="">', $this->render(null));
    }

    public function test_quotes_cannot_break_the_attribute()
    {
        $html = $this->render('https://example.com/?a="><script>');

        $this->assertStringNotContainsString('"><', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}
