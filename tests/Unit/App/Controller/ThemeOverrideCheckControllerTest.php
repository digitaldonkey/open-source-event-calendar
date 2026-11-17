<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\ThemeOverrideCheckController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;

/**
 * Admins hear about a calendar theme whose own categories.twig / tags.twig still print the former term lists
 * (term.href, term.color): since the filters became selects those values are only built with
 * OSEC_BUILD_LEGACY_TERM_ARGS, so such a template shows empty links and no colours.
 *
 * @group theme
 */
class ThemeOverrideCheckControllerTest extends TestBase
{
    private string $dir;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->options->delete(ThemeOverrideCheckController::OPTION);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->dir = get_temp_dir() . 'osec-theme-override-' . wp_rand() . '/';
        wp_mkdir_p($this->dir);
    }

    public function tear_down()
    {
        global $osec_app;

        foreach (glob($this->dir . '*') as $file) {
            wp_delete_file($file);
        }
        rmdir($this->dir);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        wp_set_current_user(0);
        parent::tear_down();
    }

    /**
     * Puts a template into a theme path of its own, in front of the bundled themes.
     */
    private function override(string $file, string $content): void
    {
        global $osec_app;

        file_put_contents($this->dir . $file, $content);
        $loader = new ThemeLoader($osec_app);
        $loader->add_path_theme($this->dir, 'https://example.org/custom-theme/');
        $osec_app->inject_object(ThemeLoader::class, $loader);
    }

    private function notices(): array
    {
        global $osec_app;

        return $osec_app->options->get(NotificationAdmin::OPTION_KEY, [])['_messages'] ?? [];
    }

    public function test_bundled_themes_give_no_notice()
    {
        global $osec_app;

        ThemeOverrideCheckController::factory($osec_app)->maybe_check();

        self::assertSame([], $this->notices());
    }

    public function test_former_term_list_override_gives_one_notice()
    {
        global $osec_app;

        $this->override('categories.twig', '{% for term in categories %}<a href="{{ term.href }}">{{ term.color|raw }}</a>{% endfor %}');
        $controller = ThemeOverrideCheckController::factory($osec_app);
        $controller->maybe_check();

        $notices = $this->notices();
        self::assertCount(1, $notices);
        $message = array_values($notices)[0]['message'];
        self::assertStringContainsString('categories.twig', $message);
        self::assertStringContainsString('OSEC_BUILD_LEGACY_TERM_ARGS', $message);
        self::assertStringContainsString(esc_html(untrailingslashit($this->dir)), $message);

        NotificationAdmin::factory($osec_app)->remove(array_key_first($notices));
        $controller->maybe_check();
        self::assertSame([], $this->notices(), 'A dismissed notice stays away for this version and theme.');
    }

    public function test_updated_override_gives_no_notice()
    {
        global $osec_app;

        $this->override('tags.twig', "{% include 'taxonomy-filter-select.twig' with {terms: tags} %}");
        ThemeOverrideCheckController::factory($osec_app)->maybe_check();

        self::assertSame([], $this->notices());
    }

    public function test_no_notice_with_legacy_term_args_enabled()
    {
        global $osec_app;

        $this->override('tags.twig', '{% for term in tags %}<a href="{{ term.href }}">{{ term.name }}</a>{% endfor %}');
        $controller = new class ($osec_app) extends ThemeOverrideCheckController {
            protected function builds_legacy_term_args(): bool
            {
                return true;
            }
        };
        $controller->maybe_check();

        self::assertSame([], $this->notices());

        // The constant removed later: the template is broken again, checked again.
        ThemeOverrideCheckController::factory($osec_app)->maybe_check();
        self::assertCount(1, $this->notices());
    }

    public function test_user_without_the_capability_gets_no_notice()
    {
        global $osec_app;

        $this->override('tags.twig', '{{ term.href }}');
        wp_set_current_user(self::factory()->user->create(['role' => 'osec_event_assistant']));
        ThemeOverrideCheckController::factory($osec_app)->maybe_check();

        self::assertSame([], $this->notices());
    }
}
