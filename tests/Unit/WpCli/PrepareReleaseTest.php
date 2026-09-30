<?php

namespace Osec\Tests\Unit\WpCli;

use Osec\Tests\Utilities\TestBase;
use Osec\WpCli\PrepareRelease;

/**
 * @group wpcli
 *
 * Only the protected check methods are tested here - prepare_release()
 * itself is not, since it ends in WP_CLI::error(), which calls exit()
 * directly outside of WP_CLI::runcommand(). See the "Testability finding"
 * in the prepare-release-tooling plan for details.
 */
class PrepareReleaseTest extends TestBase
{
    private function invoke(PrepareRelease $instance, string $method, array $args = [])
    {
        return self::getPrivateMethod(PrepareRelease::class, $method)->invokeArgs($instance, $args);
    }

    private function fake_response(int $status_code, $body): object
    {
        return (object) [
            'status_code' => $status_code,
            'body'        => is_string($body) ? $body : wp_json_encode($body),
        ];
    }

    public function test_get_latest_wp_version_returns_current_offer()
    {
        $instance = new PrepareRelease(function () {
            return $this->fake_response(200, ['offers' => [['current' => '7.2']]]);
        });

        $this->assertEquals('7.2', $this->invoke($instance, 'get_latest_wp_version'));
    }

    public function test_get_latest_wp_version_throws_on_non_200()
    {
        $instance = new PrepareRelease(function () {
            return $this->fake_response(500, '');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wordpress.org version-check returned HTTP 500');

        $this->invoke($instance, 'get_latest_wp_version');
    }

    public function test_get_latest_wp_version_throws_on_missing_offers()
    {
        $instance = new PrepareRelease(function () {
            return $this->fake_response(200, ['offers' => []]);
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('could not parse the current WordPress version from wordpress.org response');

        $this->invoke($instance, 'get_latest_wp_version');
    }

    public function test_get_latest_wp_version_throws_on_unparseable_body()
    {
        $instance = new PrepareRelease(function () {
            return $this->fake_response(200, 'not json');
        });

        $this->expectException(\Exception::class);

        $this->invoke($instance, 'get_latest_wp_version');
    }

    public function test_assert_core_not_outdated_passes_when_current()
    {
        $instance = new PrepareRelease();
        $installed = wp_get_wp_version();

        // No exception expected.
        $this->invoke($instance, 'assert_core_not_outdated', [$installed]);
        $this->addToAssertionCount(1);
    }

    public function test_assert_core_not_outdated_throws_when_behind()
    {
        $instance = new PrepareRelease();
        $installed = wp_get_wp_version();
        $future    = ((int) explode('.', $installed)[0] + 1) . '.0';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/WordPress core is outdated/');

        $this->invoke($instance, 'assert_core_not_outdated', [$future]);
    }

    public function test_assert_tested_up_to_current_passes_when_matching()
    {
        $instance = new PrepareRelease();
        $tested_up_to = $this->invoke($instance, 'get_plugin_header_value', ['Tested up to']);

        // No exception expected.
        $this->invoke($instance, 'assert_tested_up_to_current', [$tested_up_to]);
        $this->addToAssertionCount(1);
    }

    public function test_assert_tested_up_to_current_throws_when_stale()
    {
        $instance = new PrepareRelease();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches("/'Tested up to'/");

        $this->invoke($instance, 'assert_tested_up_to_current', ['99.9']);
    }

    public function test_verify_version_consistency_passes_for_in_sync_repo()
    {
        $instance = new PrepareRelease();

        // No exception expected - repo is expected to be in sync.
        $this->invoke($instance, 'verify_version_consistency');
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider provide_major_minor
     */
    public function test_major_minor(string $version, string $expected)
    {
        $instance = new PrepareRelease();
        $this->assertEquals($expected, $this->invoke($instance, 'major_minor', [$version]));
    }

    public static function provide_major_minor(): array
    {
        return [
            ['7.1.2', '7.1'],
            ['7.1', '7.1'],
            ['6.7.0', '6.7'],
        ];
    }
}
