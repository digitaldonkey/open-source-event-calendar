<?php

namespace Osec\WpCli;

/**
 * Implements prepareRelease command.
 */
class PrepareRelease
{
    private const PLUGIN_DIR = __DIR__ . '/../..';

    private const PLUGIN_FILE = self::PLUGIN_DIR . '/open-source-event-calendar.php';

    private const VERSION_CHECK_URL = 'https://api.wordpress.org/core/version-check/1.7/';

    /** @var callable */
    private $http_fetcher;

    public function __construct(?callable $http_fetcher = null)
    {
        $this->http_fetcher = $http_fetcher ?? static function (string $url) {
            return \WP_CLI\Utils\http_request('GET', $url);
        };
    }

    /**
     * Checks release metadata before a release. Read-only: reports
     * drift and inconsistencies, never edits any file itself.
     *
     * Verifies the local WordPress core install is current, checks the
     * plugin header's "Tested up to" against the latest WordPress
     * version, and verifies OSEC_VERSION (constants.php) agrees with the
     * plugin header's Version and Stable Tag fields. Every check is
     * blocking - a WP_CLI::error() on any failure.
     **
     * ## EXAMPLES
     *
     *     wp osec prepare_release
     *
     * @when before_wp_load
     */
    public function prepare_release()
    {
        try {
            require_once self::PLUGIN_DIR . '/constants.php';
            osec_initiate_constants(
                self::PLUGIN_DIR,
                'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/'
            );

            $latest_wp_version = $this->get_latest_wp_version();

            $this->assert_core_not_outdated($latest_wp_version);
            $this->assert_tested_up_to_current($latest_wp_version);
            $this->verify_version_consistency();

            \WP_CLI::success('Release metadata looks good.');
        } catch (\Exception $e) {
            \WP_CLI::error('Could not prepare release: ' . $e->getMessage());
        }
    }

    /**
     * Fetch the current stable WordPress version from wordpress.org.
     * Throws if the version can't be determined - we should never
     * silently skip a check that's supposed to be guaranteed (fail
     * closed, not open).
     */
    protected function get_latest_wp_version(): string
    {
        $response = ($this->http_fetcher)(self::VERSION_CHECK_URL);
        if (200 !== $response->status_code) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \Exception("wordpress.org version-check returned HTTP {$response->status_code}");
        }

        $data    = json_decode($response->body, true);
        $current = $data['offers'][0]['current'] ?? null;

        if (empty($current)) {
            throw new \Exception('could not parse the current WordPress version from wordpress.org response');
        }

        return $current;
    }

    /**
     * Throws if the live WordPress install is behind latest.
     */
    protected function assert_core_not_outdated(string $latest_wp_version): void
    {
        $installed = wp_get_wp_version();
        if (version_compare($installed, $latest_wp_version, '<')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped, Generic.Files.LineLength.TooLong
            throw new \Exception("WordPress core is outdated (installed {$installed}, latest {$latest_wp_version}) - run `wp core update`.");
        }
    }

    /**
     * Throws if the plugin header's "Tested up to" is behind the latest
     * WordPress version. Reporting only - never edits the file itself.
     */
    protected function assert_tested_up_to_current(string $latest_wp_version): void
    {
        $target  = $this->major_minor($latest_wp_version);
        $current = $this->get_plugin_header_value('Tested up to');

        if ($current !== $target) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped, Generic.Files.LineLength.TooLong
            throw new \Exception("'Tested up to' ({$current}) is behind the latest WordPress version ({$target}) - update it in open-source-event-calendar.php.");
        }
    }

    /**
     * Verify OSEC_VERSION (constants.php) agrees with the plugin header's
     * Version and Stable Tag fields. Throws on mismatch.
     */
    protected function verify_version_consistency(): void
    {
        $osec_version      = OSEC_VERSION;
        $header_version    = $this->get_plugin_header_value('Version');
        $header_stable_tag = $this->get_plugin_header_value('Stable Tag');

        if ($osec_version === $header_version && $osec_version === $header_stable_tag) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped, Generic.Files.LineLength.TooLong
        throw new \Exception("version mismatch - OSEC_VERSION: {$osec_version}, header Version: {$header_version}, header Stable Tag: {$header_stable_tag}. These must all agree before a release.");
    }

    protected function get_plugin_header_value(string $key): string
    {
        [$value] = get_file_data(self::PLUGIN_FILE, [$key], 'plugin');
        if (empty($value)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \Exception("Unable to source value [$key]");
        }
        return $value;
    }

    protected function major_minor(string $version): string
    {
        $parts = explode('.', $version);
        return $parts[0] . '.' . $parts[1];
    }
}
