<?php

namespace Osec\Tests\Unit\Twig;

use Osec\App\Model\Date\DateFormatsFrontend;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;
use Twig\Error\RuntimeError;

/**
 * The |kses Twig filter, the admin notice template using it, and the backend list without
 * script or event handler attributes.
 *
 * @group escaping
 * @group security
 */
class TwigExtensionKsesTest extends TestBase
{
    private function render(string $template, array $args): string
    {
        global $osec_app;

        return ThemeLoader::factory($osec_app)
                          ->get_twig_instance()
                          ->createTemplate($template)
                          ->render($args);
    }

    public function test_basic_keeps_text_formatting_and_links()
    {
        $html = '<p><strong>Saved</strong> <em>now</em> <code>x</code><br><pre>y</pre>'
            . '<a class="button" href="https://example.com/" title="t">Edit</a></p>';

        $this->assertSame($html, $this->render('{{ m|kses }}', ['m' => $html]));
    }

    public function test_basic_removes_scripts_and_event_handlers()
    {
        $html = $this->render('{{ m|kses }}', [
            'm' => '<p onclick="a()">x</p><script>b()</script><img src="x" onerror="c()">'
                . '<a href="javascript:d()" onmouseover="e()">y</a><input onfocus="f()">',
        ]);

        $this->assertSame('<p>x</p>b()<a href="d()">y</a>', $html);
    }

    public function test_named_list()
    {
        $this->assertSame('<p>x</p>', $this->render("{{ m|kses('basic') }}", ['m' => '<p>x</p><div>']));
    }

    public function test_unknown_list_name_throws()
    {
        $this->expectException(RuntimeError::class);
        $this->render("{{ m|kses('nope') }}", ['m' => 'x']);
    }

    public function test_admin_notice_message_is_limited_to_basic()
    {
        global $osec_app;

        $html = ThemeLoader::factory($osec_app)->get_file(
            'notification/admin.twig',
            [
                'class'   => 'error',
                'label'   => 'Label',
                'message' => '<p><strong>ok</strong></p><script>alert(1)</script><img src=x onerror=alert(2)>',
            ],
            true
        )->get_content();

        $this->assertStringContainsString('<p><strong>ok</strong></p>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_backend_list_allows_no_script_or_event_handlers()
    {
        global $osec_app;

        $allowed = $osec_app->kses->allowed_html_backend();

        $this->assertArrayNotHasKey('script', $allowed);
        foreach ($allowed as $tag => $attributes) {
            foreach (array_keys($attributes) as $attribute) {
                $this->assertStringStartsNotWith('on', (string)$attribute, "$tag@$attribute");
            }
        }
    }

    public function test_date_format_settings_render_without_inline_script()
    {
        global $osec_app;

        ob_start();
        DateFormatsFrontend::factory($osec_app)->renderShortDate();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringContainsString('data-osec-format="d/m/Y"', $html);
        $this->assertStringContainsString('class="small-text osec-date-format-custom"', $html);
        $this->assertStringContainsString('class="osec-date-format-custom-radio"', $html);
    }
}
