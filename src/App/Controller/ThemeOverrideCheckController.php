<?php

namespace Osec\App\Controller;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;

/**
 * Tells admins about calendar theme templates written for the former category and tag lists.
 *
 * Up to 1.2.1 categories.twig and tags.twig printed one link per term (term.href) and a colour swatch
 * (term.color). Since the filters are selects (taxonomy-filter-select.twig), these values are only built with
 * OSEC_BUILD_LEGACY_TERM_ARGS, so a theme's own copy of the former templates shows empty links and no colours.
 *
 * The check runs on admin_init for users who manage the calendar, once per plugin version, active calendar
 * theme and setting of OSEC_BUILD_LEGACY_TERM_ARGS. It looks at the theme paths outside the bundled themes (a
 * custom theme, an extension's path). A finding is an admin notice through NotificationAdmin, dismissible;
 * dismissed, it stays away until one of these changes.
 */
class ThemeOverrideCheckController extends OsecBaseClass
{
    /**
     * Option holding the state of the last check: plugin version, theme and legacy setting it ran for.
     */
    public const OPTION = 'osec_theme_override_check';

    /**
     * Templates of the filters that used to print the former term lists.
     */
    public const TEMPLATES = ['categories.twig', 'tags.twig'];

    /**
     * Registers the check.
     *
     * @param  App  $app  The app.
     * @param  bool  $is_admin  Whether this is an admin request.
     *
     * @return void
     */
    public static function add_actions(App $app, bool $is_admin): void
    {
        if (! $is_admin) {
            return;
        }
        add_action('admin_init', fn() => self::factory($app)->maybe_check());
    }

    /**
     * Checks the theme paths once per plugin version, calendar theme and legacy setting; a notice per finding.
     *
     * @return void
     */
    public function maybe_check(): void
    {
        if (wp_doing_ajax() || ! current_user_can('manage_osec_options')) {
            return;
        }
        $state = [
            'version' => OSEC_VERSION,
            'theme'   => (string)($this->app->options->get('osec_current_theme')['stylesheet'] ?? ''),
            'legacy'  => $this->builds_legacy_term_args(),
        ];
        if ($this->app->options->get(self::OPTION) === $state) {
            return;
        }
        if (! $state['legacy']) {
            foreach ($this->outdated_templates() as $file) {
                $this->store_notice($file);
            }
        }
        $this->app->options->set(self::OPTION, $state);
    }

    /**
     * Theme templates outside the bundled themes that print term.href or term.color.
     *
     * @return string[] Absolute file paths.
     */
    public function outdated_templates(): array
    {
        $bundled = trailingslashit(wp_normalize_path((string)realpath(OSEC_DEFAULT_THEME_ROOT)));
        $found   = [];
        foreach (array_keys(ThemeLoader::factory($this->app)->get_paths()['theme'] ?? []) as $path) {
            $dir = trailingslashit(wp_normalize_path((string)realpath($path)));
            if ('/' === $dir || str_starts_with($dir, $bundled)) {
                continue;
            }
            foreach (self::TEMPLATES as $template) {
                $file = $dir . $template;
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
                if (is_readable($file) && preg_match('/\bterm\.(href|color)\b/', (string)file_get_contents($file))) {
                    $found[] = $file;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Whether the former term values are built anyway (OSEC_BUILD_LEGACY_TERM_ARGS); then nothing is broken.
     *
     * @return bool
     */
    protected function builds_legacy_term_args(): bool
    {
        return defined('OSEC_BUILD_LEGACY_TERM_ARGS') && OSEC_BUILD_LEGACY_TERM_ARGS;
    }

    /**
     * Stores the notice for one outdated template.
     *
     * @param  string  $file  Absolute path of the template.
     *
     * @return void
     */
    private function store_notice(string $file): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $message = '<p>' . esc_html(
            sprintf(
                /* translators: 1: template file name, 2: its directory. */
                __(
                    'Your calendar theme\'s own %1$s (in %2$s) still prints the former category and tag lists. The filters are searchable selects now, so this template shows empty links and no colours.',
                    'open-source-event-calendar'
                ),
                basename($file),
                dirname($file)
            )
        ) . '</p><p>' . esc_html(
            sprintf(
                /* translators: %s: constant name. */
                __(
                    'Update it from the bundled theme it is based on (vortex or plana) and redo your changes there. For a theme based on vortex, defining %s as true in wp-config.php brings the former links and colours back for now.',
                    'open-source-event-calendar'
                ),
                'OSEC_BUILD_LEGACY_TERM_ARGS'
            )
        ) . '</p>';
        // phpcs:enable

        NotificationAdmin::factory($this->app)->store($message, 'error', 1, [NotificationAdmin::RCPT_ADMIN], true);
    }
}
