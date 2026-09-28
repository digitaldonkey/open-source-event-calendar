<?php

namespace Osec\Tests\Unit\Command;

use Osec\App\Model\PostTypeEvent\InvalidArgumentException;
use Osec\Command\CompileCoreCss;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;
use ReflectionMethod;

/**
 * CompileCoreCss resolves the theme name to its folder (`?theme=<name>`).
 *
 * Only the switch is tested here: a rebuild writes into the theme folder.
 *
 * @group request
 */
class CompileCoreCssTest extends TestBase
{
    public function tear_down()
    {
        $_REQUEST = [];
        parent::tear_down();
    }

    private function process(): string
    {
        global $osec_app;

        $method = new ReflectionMethod(CompileCoreCss::class, 'processFiles');

        return $method->invoke(new CompileCoreCss($osec_app, RequestParser::factory($osec_app)));
    }

    public function test_switch_stores_the_theme_array()
    {
        global $osec_app;

        $_REQUEST = ['theme' => 'plana', 'switch' => '1'];

        $this->assertSame('Theme switched to "plana".', $this->process());
        $theme = $osec_app->options->get('osec_current_theme');
        $this->assertSame('plana', $theme['stylesheet']);
        $this->assertSame(OSEC_PATH . 'public/' . OSEC_THEME_FOLDER . DIRECTORY_SEPARATOR . 'plana', $theme['theme_dir']);
    }

    public function test_unknown_theme_is_refused()
    {
        $_REQUEST = ['theme' => '../../uploads', 'switch' => '1'];

        $this->expectException(InvalidArgumentException::class);
        $this->process();
    }

    public function test_missing_theme()
    {
        $this->assertSame('Param theme is required', $this->process());
    }
}
