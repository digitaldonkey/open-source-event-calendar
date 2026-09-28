<?php

namespace Osec\Tests\Unit\Http\Request;

use Osec\Http\Request\ParamType;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\HostileInput;
use Osec\Tests\Utilities\TestBase;

/**
 * RequestParser::get_param() with a ParamType, and has_param().
 *
 * @group request
 * @group security
 */
class RequestParserGetParamTest extends TestBase
{
    use HostileInput;

    public function tear_down()
    {
        $_REQUEST = [];
        parent::tear_down();
    }

    /**
     * Sets a request value the way WordPress does: slashed.
     */
    private function request(mixed $value): void
    {
        $_REQUEST['p'] = wp_slash($value);
    }

    public function test_missing_returns_default_unchanged()
    {
        foreach (ParamType::cases() as $type) {
            $this->assertNull(RequestParser::get_param('p', null, $type), $type->name);
            $this->assertFalse(RequestParser::has_param('p'));
        }
    }

    public function test_sent_empty_is_not_the_default()
    {
        $this->request('');

        $this->assertTrue(RequestParser::has_param('p'));
        $this->assertSame('', RequestParser::get_param('p', null));
        $this->assertSame(0, RequestParser::get_param('p', null, ParamType::Int));
        $this->assertFalse(RequestParser::get_param('p', null, ParamType::Bool));
        $this->assertSame([], RequestParser::get_param('p', null, ParamType::IdList));
    }

    public function test_array_for_a_single_value_returns_default()
    {
        $this->request(['a', 'b']);

        foreach (ParamType::cases() as $type) {
            if (ParamType::IdList !== $type) {
                $this->assertSame('none', RequestParser::get_param('p', 'none', $type), $type->name);
            }
        }
    }

    public function test_default_type_is_text_like_before()
    {
        $this->request("O'Brien <b>Pub</b>\n");

        $this->assertSame("O'Brien Pub", RequestParser::get_param('p'));
    }

    public function test_unslashes_once()
    {
        $this->request('j \d\e F');

        $this->assertSame('j \d\e F', RequestParser::get_param('p'));
    }

    public function test_textarea_keeps_line_breaks()
    {
        $this->request("User-agent: *\nDisallow: /x");

        $this->assertSame("User-agent: *\nDisallow: /x", RequestParser::get_param('p', '', ParamType::Textarea));
    }

    /**
     * @dataProvider provide_scalars
     */
    public function test_scalar_types(ParamType $type, string $sent, mixed $expected)
    {
        $this->request($sent);

        $this->assertSame($expected, RequestParser::get_param('p', null, $type));
    }

    public function provide_scalars(): array
    {
        return [
            'key'               => [ParamType::Key, 'Osec_Delete-ICS!', 'osec_delete-ics'],
            'int'               => [ParamType::Int, '-1', -1],
            'int text'          => [ParamType::Int, '5 events', 5],
            'id'                => [ParamType::Id, '42', 42],
            'id negative'       => [ParamType::Id, '-42', 42],
            'float'             => [ParamType::Float, '52.52', 52.52],
            'bool 1'            => [ParamType::Bool, '1', true],
            'bool on'           => [ParamType::Bool, 'on', true],
            'bool true'         => [ParamType::Bool, 'true', true],
            'bool 0'            => [ParamType::Bool, '0', false],
            'bool false'        => [ParamType::Bool, 'false', false],
            'bool FALSE'        => [ParamType::Bool, 'FALSE', false],
            'bool off'          => [ParamType::Bool, 'off', false],
            'bool no'           => [ParamType::Bool, 'no', false],
            'email'             => [ParamType::Email, 'a&b@example.com', 'a&b@example.com'],
            'callback jquery'   => [ParamType::Callback, 'jQuery1124_1790589997', 'jQuery1124_1790589997'],
            'callback dotted'   => [ParamType::Callback, 'timely.cb', 'timely.cb'],
            'callback code'     => [ParamType::Callback, 'alert(document.domain)', null],
            'callback injected' => [ParamType::Callback, 'a;alert(1)', null],
            'callback newline'  => [ParamType::Callback, "cb\n", null],
        ];
    }

    public function test_urls_keep_percent_encoded_octets()
    {
        $url = 'https://example.com/buy?q=M%C3%BCnchen%20Tickets&ref=1';
        $this->request($url);

        $this->assertSame($url, RequestParser::get_param('p', '', ParamType::Url));
        $this->assertSame($url, RequestParser::get_param('p', '', ParamType::HttpUrl));
        $this->assertNotSame($url, RequestParser::get_param('p'), 'Text strips %xx.');
    }

    public function test_url_protocols()
    {
        $this->request('webcal://example.com/cal.ics');
        $this->assertSame('webcal://example.com/cal.ics', RequestParser::get_param('p', '', ParamType::Url));
        $this->assertSame('', RequestParser::get_param('p', '', ParamType::HttpUrl));

        $this->request('javascript:alert(1)');
        $this->assertSame('', RequestParser::get_param('p', '', ParamType::Url));
    }

    public function test_id_list_from_array_or_string()
    {
        $this->request(['3', '0', 'x', '-4', ['nested']]);
        $this->assertSame([3, 4], RequestParser::get_param('p', null, ParamType::IdList));

        $this->request('1, 2,,7');
        $this->assertSame([1, 2, 7], RequestParser::get_param('p', null, ParamType::IdList));
    }

    /**
     * @dataProvider provide_hostile
     */
    public function test_hostile_values_never_come_back_as_markup(string $value)
    {
        $this->request($value);

        foreach ([ParamType::Text, ParamType::Textarea, ParamType::Key, ParamType::Email] as $type) {
            self::assertNoTag(RequestParser::get_param('p', '', $type), $type->name);
        }
        foreach ([ParamType::Url, ParamType::HttpUrl] as $type) {
            self::assertSafeUrl(RequestParser::get_param('p', '', $type), $type->name);
        }
        $callback = RequestParser::get_param('p', '', ParamType::Callback);
        $this->assertMatchesRegularExpression('/^[\w$.]*$/', $callback);
    }

    public function provide_hostile(): array
    {
        return array_map(fn($value) => [$value], self::hostile_values());
    }
}
