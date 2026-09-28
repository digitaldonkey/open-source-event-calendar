<?php

namespace Osec\Tests\Unit\Command;

use Osec\App\View\Admin\AdminPageSettings;
use Osec\Command\SaveSettings;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * Settings are saved as submitted: unslashed once, textareas keep line breaks.
 *
 * @group settings
 * @group request
 */
class SaveSettingsTest extends TestBase
{
    /**
     * Setting values before the test. A save without view checkboxes disables
     * every view, and the Settings object outlives the DB rollback.
     */
    private array $saved = [];

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved = array_map(fn($option) => $option['value'] ?? null, $osec_app->settings->get_options());
    }

    public function tear_down()
    {
        global $osec_app;

        $_REQUEST = [];
        foreach ($this->saved as $name => $value) {
            $osec_app->settings->set($name, $value);
        }
        parent::tear_down();
    }

    public function test_backslashes_and_line_breaks_survive()
    {
        global $osec_app;

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $_REQUEST = wp_slash([
            AdminPageSettings::$NONCE['nonce_name'] => wp_create_nonce(key([AdminPageSettings::$NONCE['action'] => 1])),
            'calendar_css_selector'                 => '#main\:content',
            'edit_robots_txt'                       => "User-agent: *\nDisallow: /x",
        ]);

        SaveSettings::factory(
            $osec_app,
            RequestParser::factory($osec_app),
            AdminPageSettings::$NONCE
        )->do_execute();

        $this->assertSame('#main\:content', $osec_app->settings->get('calendar_css_selector'));
        $this->assertSame("User-agent: *\nDisallow: /x", $osec_app->settings->get('edit_robots_txt'));
    }
}
