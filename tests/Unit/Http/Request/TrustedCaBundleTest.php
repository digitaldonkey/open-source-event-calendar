<?php

namespace Osec\Tests\Unit\Http\Request;

use Osec\Http\Request\TrustedCaBundle;
use Osec\Tests\Unit\Cache\CacheFileTestBase;

/**
 * The CA list feeds are verified against when "Trust this server's CA certificates for feeds" is on: WordPress' own
 * list plus the server's. Verification stays on.
 *
 * @group feeds
 */
class TrustedCaBundleTest extends CacheFileTestBase
{
    private string $system_bundle;

    public function set_up()
    {
        parent::set_up();
        $this->system_bundle = get_temp_dir() . 'osec-test-system-ca-' . wp_generate_password(6, false) . '.pem';
        file_put_contents($this->system_bundle, "-----BEGIN CERTIFICATE-----\nTESTLOCALCA\n-----END CERTIFICATE-----\n");
    }

    public function tear_down()
    {
        global $osec_app;

        wp_delete_file($this->system_bundle);
        $dir = $this->bundle($this->system_bundle)->get_dir();
        if ($dir && is_dir($dir)) {
            array_map('wp_delete_file', glob($dir . '*') ?: []);
        }
        $osec_app->inject_object(TrustedCaBundle::class, new TrustedCaBundle($osec_app));
        parent::tear_down();
    }

    public function test_combines_the_wordpress_and_the_server_list()
    {
        $path = $this->bundle($this->system_bundle)->path();

        $this->assertIsString($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString(
            file_get_contents(ABSPATH . WPINC . '/certificates/ca-bundle.crt'),
            $content,
            "WordPress' own list stays"
        );
        $this->assertStringContainsString('TESTLOCALCA', $content);
    }

    public function test_rebuilt_when_the_server_list_changes()
    {
        $first = $this->bundle($this->system_bundle)->path();
        file_put_contents($this->system_bundle, "-----BEGIN CERTIFICATE-----\nANOTHERCA\n-----END CERTIFICATE-----\n");
        touch($this->system_bundle, time() + 10);
        clearstatcache();

        $second = $this->bundle($this->system_bundle)->path();

        $this->assertNotSame($first, $second);
        $this->assertStringContainsString('ANOTHERCA', file_get_contents($second));
        $this->assertFileDoesNotExist($first, 'the outdated list is removed');
    }

    public function test_no_bundle_without_a_server_list()
    {
        $this->assertNull($this->bundle(null)->path());
    }

    public function test_finds_the_server_list_of_this_system()
    {
        global $osec_app;

        // The DDEV container (Debian) and CI images have one.
        $found = (new TrustedCaBundle($osec_app))->system_bundle();

        $this->assertIsString($found);
        $this->assertFileIsReadable($found);
        $this->assertNotSame(realpath(ABSPATH . WPINC . '/certificates/ca-bundle.crt'), realpath($found));
    }

    private function bundle(?string $system): TrustedCaBundle
    {
        global $osec_app;

        return new class ($osec_app, $system) extends TrustedCaBundle {
            public function __construct($app, private ?string $system)
            {
                parent::__construct($app);
            }

            public function system_bundle(): ?string
            {
                return $this->system;
            }
        };
    }
}
