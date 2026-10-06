<?php

namespace Osec\Tests\Unit\App\Model\Date;

use Osec\App\Model\Date\DateFormatsFrontend;
use Osec\Tests\Utilities\TestBase;

/**
 * Saving the frontend date formats on Settings -> General (options.php -> update_option()).
 */
class DateFormatsFrontendSaveTest extends TestBase
{
    private const OPTION = DateFormatsFrontend::FORMAT_SHORT;

    public function set_up()
    {
        global $osec_app, $wp_settings_errors;

        parent::set_up();
        require_once ABSPATH . 'wp-admin/includes/template.php';
        // The test framework resets hooks per test; the factory returns the instance registered before.
        DateFormatsFrontend::factory($osec_app)->initialize();
        $wp_settings_errors = [];
        unset($_REQUEST[self::OPTION . '_custom']);
    }

    public function tear_down()
    {
        unset($_REQUEST[self::OPTION . '_custom']);
        parent::tear_down();
    }

    private function save(string $value, ?string $custom = null): void
    {
        if (null !== $custom) {
            // Request values arrive slashed (wp_magic_quotes()).
            $_REQUEST[self::OPTION . '_custom'] = wp_slash($custom);
        }
        update_option(self::OPTION, $value);
    }

    public function test_preset()
    {
        $this->save('Y-m-d');

        $this->assertSame('Y-m-d', get_option(self::OPTION));
    }

    /**
     * The option does not exist yet: update_option() sanitizes, add_option() sanitizes the result again.
     */
    public function test_first_custom_format_without_stored_option()
    {
        delete_option(self::OPTION);
        $this->save('custom', 'Y/m/d');

        $this->assertSame('Y/m/d', get_option(self::OPTION));
    }

    public function test_custom_format_with_stored_option()
    {
        update_option(self::OPTION, 'd.m.Y');
        $this->save('custom', 'j. M \\K\\W W');

        $this->assertSame('j. M \\K\\W W', get_option(self::OPTION));
    }

    public function test_custom_format_is_sanitized()
    {
        delete_option(self::OPTION);
        $this->save('custom', 'j.<script>x</script> M');

        $this->assertSame('j. M', get_option(self::OPTION));
    }

    public function test_empty_custom_format_keeps_previous_and_reports()
    {
        update_option(self::OPTION, 'd.m.Y');
        $this->save('custom', '  ');

        $this->assertSame('d.m.Y', get_option(self::OPTION));
        $this->assertSame(self::OPTION . '_empty', get_settings_errors(self::OPTION)[0]['code'] ?? null);
    }

    public function test_stored_format_keeps_its_backslashes_in_the_field()
    {
        global $osec_app;

        update_option(self::OPTION, 'j. M \\K\\W W');
        ob_start();
        DateFormatsFrontend::factory($osec_app)->renderShortDate();
        $html = ob_get_clean();

        $this->assertStringContainsString('value="j. M \\K\\W W"', $html);
    }
}
