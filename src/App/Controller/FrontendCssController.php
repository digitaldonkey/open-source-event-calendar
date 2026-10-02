<?php

namespace Osec\App\Controller;

use Exception;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Bootstrap\App;
use Osec\Bootstrap\MemoryCheck;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\Cache;
use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheFile;
use Osec\Cache\CacheInterface;
use Osec\Cache\CacheNotSetException;
use Osec\Cache\CachePath;
use Osec\Cache\CacheWriteException;
use Osec\Exception\BootstrapException;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\ResponseHelper;
use WP_Post;

/**
 * The class which handles Frontend CSS.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Frontend
 * @replaces Ai1ec_Css_Frontend
 */
class FrontendCssController extends OsecBaseClass
{
    /**
     * If we request the "CSS file" from a non-File-cache,
     * this param will deliver CSS code.
     * e.g: <link rel="stylesheet" id="ai1ec_style-css"
     * href="//ddev-wordpress.ddev.site/?osec-css-cache=1725894108&amp;ver=2.3.1" media="all">
     */
    public const REQUEST_CSS_PARAM = 'osec-css-cache';

    /**
     * This is for testing purpose, set it to OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST value.
     */
    public const PARSE_LESS_FILES_AT_EVERY_REQUEST = OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST;

    /**
     * The compiled CSS state, an array (see update_persistence_layer()). Autoloaded: every calendar page reads it to
     * build the stylesheet link.
     */
    public const CSS_OPTION = 'osec_css';

    /**
     * 1.1.x option holding the CSS file URL or a timestamp, replaced by self::CSS_OPTION. Only removed on upgrade.
     */
    public const COMPILED_CSS_KEY = 'osec_compiled.css';

    /**
     * This option is set when... CSS needs recompile,
     */
    public const COMPILED_CSS_CACHE_KEY = 'osec_invalidate_css_cache';

    /**
     * Lock (ExecutionLimitController) held while one request compiles; others do not compile meanwhile.
     */
    public const COMPILE_LOCK = 'osec_css_compile';

    /**
     * Transient set after a failed compile: no automatic retry (flag, route) until it expires. Anything an admin does
     * (Theme Options save, theme switch, update, clearing the caches) ends it.
     */
    public const COMPILE_FAILED_TRANSIENT = 'osec_css_compile_failed';

    private const COMPILE_LOCK_TIMEOUT = 120;

    private const COMPILE_BACKOFF = 30 * MINUTE_IN_SECONDS;

    private const MAX_AGE = 31536000;

    /**
     * The calendar block (calendar_block/src/block.json).
     */
    public const CALENDAR_BLOCK = 'open-source-event-calendar/osec-calendar-classic';

    /**
     * Engine names stored in self::CSS_OPTION.
     */
    private const ENGINES = [
        CacheFile::class => 'file',
        CacheApcu::class => 'apcu',
        CacheDb::class   => 'db',
    ];

    /**
     * Engine chosen for writing, created on the first compile; page views do not need it.
     */
    private ?Cache $cache = null;

    /**
     * CSS compiled in this request.
     */
    private ?string $compiled = null;

    /**
     * Cache key of the compiled CSS in every engine, and the file name in the file cache.
     */
    public static function css_file_name(): string
    {
        return 'osec-compiled-' . get_current_blog_id() . '.css';
    }

    /**
     * Renders the css for our frontend (the `?osec-css-cache=` route).
     *
     * Sets etags to avoid sending not needed data
     */
    public function render_css()
    {
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_IF_NONE_MATCH'])) : '';
        if ($this->is_not_modified($if_none_match)) {
            status_header(304);
            header('ETag: ' . $this->etag());
            ResponseHelper::stop();
        }
        $response = $this->css_response();
        status_header($response['status']);
        foreach ($response['headers'] as $name => $value) {
            header($name . ': ' . $value);
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $response['body'];
        ResponseHelper::stop();
    }

    /**
     * The route's response: the CSS, cached for a year (the URL changes with the CSS), or, while it cannot be had
     * (another request compiles, a compile failed), a stylesheet with only a comment that is not cached, so the
     * next page view asks again.
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public function css_response(): array
    {
        $css = $this->get_compiled_css();
        if (null === $css) {
            return [
                'status'  => 200,
                'headers' => [
                    'Content-Type'  => 'text/css',
                    'Cache-Control' => 'no-store',
                ],
                'body'    => '/* The calendar stylesheet is not available yet. */',
            ];
        }

        return [
            'status'  => 200,
            'headers' => [
                'Content-Type'  => 'text/css',
                'ETag'          => $this->etag(),
                'Expires'       => gmdate('D, d M Y H:i:s', time() + self::MAX_AGE) . ' GMT',
                'Cache-Control' => 'public, max-age=' . self::MAX_AGE,
            ],
            'body'    => $css,
        ];
    }

    /**
     * Whether the browser's copy is current. A server compressing the response marks the ETag weak (W/"..."),
     * and browsers send it back that way.
     *
     * @param  string  $if_none_match  The If-None-Match request header.
     */
    public function is_not_modified(string $if_none_match): bool
    {
        foreach (explode(',', $if_none_match) as $tag) {
            $tag = trim($tag);
            if ($this->etag() === (str_starts_with($tag, 'W/') ? substr($tag, 2) : $tag)) {
                return true;
            }
        }

        return false;
    }

    private function etag(): string
    {
        return '"' . md5(__FILE__ . RequestParser::get_param(self::REQUEST_CSS_PARAM)) . '"';
    }

    /**
     * The CSS: compiled in this request, stored, or compiled now and stored.
     *
     * A compile runs under the lock and not during the backoff after a failed one.
     *
     * @return string|null Null while another request compiles or after a failed compile.
     */
    public function get_compiled_css(): ?string
    {
        if (null !== $this->compiled) {
            return $this->compiled;
        }
        if (self::PARSE_LESS_FILES_AT_EVERY_REQUEST) {
            // Debug mode: compile errors are meant to show.
            $this->compiled = LessController::factory($this->app)->parse_less_files(null, false);

            return $this->compiled;
        }
        $stored = $this->get_stored_css();
        if (null !== $stored) {
            return $stored;
        }
        if (get_transient(self::COMPILE_FAILED_TRANSIENT)) {
            return null;
        }
        $lock = ExecutionLimitController::factory($this->app);
        if ( ! $lock->acquire(self::COMPILE_LOCK, self::COMPILE_LOCK_TIMEOUT)) {
            return null;
        }
        try {
            $this->compiled = LessController::factory($this->app)->parse_less_files(null, false);
        } catch (Exception $e) {
            $this->notify_compile_error($e);
            set_transient(self::COMPILE_FAILED_TRANSIENT, 1, self::COMPILE_BACKOFF);
            $lock->release(self::COMPILE_LOCK);

            return null;
        }
        try {
            $this->update_persistence_layer($this->compiled);
        } catch (CacheWriteException $e) {
            NotificationAdmin::factory($this->app)->store(
                sprintf(
                    /* translators: Compile error */
                    __(
                        'Your CSS is being compiled on every request,
                            which causes your calendar to perform slowly. The following error occurred: %s',
                        'open-source-event-calendar'
                    ),
                    $e->getMessage()
                ),
                'error',
                2,
                [NotificationAdmin::RCPT_ADMIN],
                true
            );
        } finally {
            $lock->release(self::COMPILE_LOCK);
        }

        // If the CSS cannot be stored, still return it.
        return $this->compiled;
    }

    /**
     * Compiles the CSS if a plugin update, activation or theme switch asked for it. Runs on init.
     *
     * Leaves the flag for a later request while another one compiles, during the backoff after a failed compile
     * (S2), and in WP-CLI or cron when the CSS would go to APCu, which the web server does not share (D7).
     */
    public function compile_flagged(): void
    {
        if (
            ! $this->app->options->get(self::COMPILED_CSS_CACHE_KEY)
            || get_transient(self::COMPILE_FAILED_TRANSIENT)
            || ($this->is_cli() && $this->get_cache()->engine instanceof CacheApcu)
        ) {
            return;
        }
        $lock = ExecutionLimitController::factory($this->app);
        if ( ! $lock->acquire(self::COMPILE_LOCK, self::COMPILE_LOCK_TIMEOUT)) {
            return;
        }
        try {
            if ( ! $this->invalidate_cache(null, true)) {
                set_transient(self::COMPILE_FAILED_TRANSIENT, 1, self::COMPILE_BACKOFF);
            }
        } finally {
            $lock->release(self::COMPILE_LOCK);
        }
    }

    /**
     * Compile on the next request (theme switch, activation, update): ends the backoff of an earlier failure, so the
     * change takes effect at once.
     */
    public function request_compile(): void
    {
        $this->app->options->set(self::COMPILED_CSS_CACHE_KEY, true, true);
        delete_transient(self::COMPILE_FAILED_TRANSIENT);
    }

    protected function is_cli(): bool
    {
        return 'cli' === PHP_SAPI;
    }

    /**
     * Stores the CSS in the first available engine, then the state pointing to it.
     *
     * State: engine ('file', 'apcu', 'db'), ver (first 7 characters of the CSS md5, changes with the CSS only), and
     * for the file engine root (CachePath::ROOT_*) and file. Written only after the CSS, so a failed write keeps the
     * previous state.
     *
     * @param  string  $css
     *
     * @return void
     * @throws CacheWriteException
     */
    public function update_persistence_layer($css)
    {
        $cache = $this->get_cache();
        if ( ! $cache->engine->set(self::css_file_name(), $css)) {
            throw new CacheWriteException(esc_html(self::css_file_name()));
        }
        $state = ['engine' => self::ENGINES[get_class($cache->engine)] ?? 'unknown'];
        if ($cache->engine instanceof CacheFile) {
            $state['root'] = $cache->engine->get_root();
            $state['file'] = self::css_file_name();
        }
        $state['ver'] = substr(md5($css), 0, 7);
        $this->app->options->set(self::CSS_OPTION, $state, true);
        // Whatever asked for a compile is answered (D-CSS2: setting it here compiled everything twice).
        $this->app->options->delete(self::COMPILED_CSS_CACHE_KEY);
        delete_transient(self::COMPILE_FAILED_TRANSIENT);
    }

    /**
     * @return array|null The state written by update_persistence_layer(), null if none or invalid.
     */
    public function get_state(): ?array
    {
        $state = $this->app->options->get(self::CSS_OPTION);
        if (
            ! is_array($state)
            || ! in_array($state['engine'] ?? null, self::ENGINES, true)
            || ! is_string($state['ver'] ?? null)
        ) {
            return null;
        }
        if ('file' === $state['engine'] && ! (is_string($state['root'] ?? null) && is_string($state['file'] ?? null))) {
            return null;
        }

        return $state;
    }

    /**
     * @wp_hook wp_enqueue_scripts Only on the frontend.
     */
    public static function add_actions(App $app, bool $is_admin): void
    {
        if ($is_admin) {
            return;
        }
        add_action(
            'wp_enqueue_scripts',
            function () use ($app) {
                $ctrl = self::factory($app);
                if ($ctrl->page_shows_calendar()) {
                    $ctrl->add_link_to_html_for_frontend();
                }
            }
        );
    }

    /**
     * Whether the requested page will show a calendar, known before wp_head (D4): the calendar page, a single event,
     * or singular content with the shortcode or the calendar block.
     *
     * Not detected (the CSS then follows with the footer styles): calendars in widgets, template parts, patterns,
     * reusable blocks or page builders (H9).
     */
    public function page_shows_calendar(): bool
    {
        if ( ! is_singular()) {
            return false;
        }
        $post = get_queried_object();
        if ( ! $post instanceof WP_Post) {
            return false;
        }

        return OSEC_POST_TYPE === $post->post_type
            || (int) $this->app->settings->get('calendar_page_id') === $post->ID
            || has_shortcode($post->post_content, OSEC_SHORTCODE)
            || has_block(self::CALENDAR_BLOCK, $post);
    }

    /**
     * Adds the compiled CSS to the page: a stylesheet link, or the CSS inline.
     *
     * Called early for pages known to show a calendar, and again by every calendar while it renders. Before wp_head
     * it lands in <head>; after it, WordPress prints it with the footer styles. Adding it twice prints it once.
     */
    public function add_link_to_html_for_frontend(): void
    {
        if (is_admin()) {
            return;
        }
        $url = $this->get_css_url();
        if ('' === $url) {
            $this->echo_css();

            return;
        }
        wp_enqueue_style('ai1ec_style', $url, [], $this->get_state()['ver'] ?? OSEC_VERSION);
    }

    /**
     * Get the url to retrieve the css
     *
     * The static file when it exists and has a URL, otherwise the route compiling or reading the CSS.
     *
     * @return string Empty for the inline variant (debug mode, or no file and render_css_as_link off).
     */
    public function get_css_url(): string
    {
        if (OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST) {
            return '';
        }

        $state = $this->get_state();
        if ($state && 'file' === $state['engine']) {
            $dir = CachePath::factory($this->app)->root_dir($state['root'], 'css');
            $url = $dir && file_exists($dir . $state['file'])
                ? CachePath::factory($this->app)->path_to_url($dir . $state['file']) : null;
            if ($url) {
                return ResponseHelper::remove_protocols($url);
            }
        }

        // "Link CSS in <head> section when file cache is unavailable."
        if ($this->app->settings->get('render_css_as_link')) {
            return ResponseHelper::remove_protocols(
                add_query_arg(
                    [self::REQUEST_CSS_PARAM => $state['ver'] ?? 0],
                    trailingslashit(get_site_url())
                )
            );
        }

        return '';
    }

    /**
     * Adds the CSS as an inline style (handle osec-frontend-css), once per request.
     */
    public function echo_css()
    {
        $handle = 'osec-frontend-css';
        if (wp_style_is($handle)) {
            return;
        }
        wp_register_style($handle, false, [], OSEC_VERSION);
        $compiled = $this->get_compiled_css();
        if (null === $compiled) {
            return;
        }
        if ($compiled !== wp_strip_all_tags($compiled)) {
            throw new Exception(esc_html__('Unexpected CSS content', 'open-source-event-calendar'));
        }
        wp_add_inline_style($handle, wp_strip_all_tags($compiled));
        wp_enqueue_style($handle);
    }

    /**
     * Update the less variables on the DB and recompile the CSS
     *
     * @param  bool  $resetting  are we resetting or updating variables?
     */
    public function update_variables_and_compile_css(array $variables, $resetting)
    {
        $no_parse_errors = $this->invalidate_cache($variables, true);

        if ($no_parse_errors) {
            $this->app->options->set(
                LessController::DB_KEY_FOR_LESS_VARIABLES,
                $variables
            );

            if (true === $resetting) {
                $message = sprintf(
                    /* translators: Url */
                    '<p>' . __(
                        "Theme options were successfully reset to their default values. <a href='%s'>Visit site</a>",
                        'open-source-event-calendar'
                    ) . '</p>',
                    get_site_url()
                );
            } else {
                $message = sprintf(
                    /* translators: Site url */
                    '<p>' . __(
                        "Theme options were updated successfully. <a href='%s'>Visit site</a>",
                        'open-source-event-calendar'
                    ) . '</p>',
                    get_site_url()
                );
            }
            NotificationAdmin::factory($this->app)->store($message);
        }
    }

    /**
     * Invalidate the persistence layer only after a successful compile of the
     * LESS files.
     *
     * @param  array|null  $variables  LESS variable array to use
     * @param  bool  $update_persistence  Whether the persist successful compile
     *
     * @return bool                     Whether successful
     * @throws BootstrapException
     */
    public function invalidate_cache(?array $variables = null, bool $update_persistence = true): bool
    {
        $lessCtrl = LessController::factory($this->app);
        $notification = NotificationAdmin::factory($this->app);
        if ( ! MemoryCheck::check_available_memory(OSEC_LESS_MIN_AVAIL_MEMORY)) {
            $message = sprintf(
                /* translators: Minimum PHP memory required */
                __(
                    'CSS compilation failed because you do not have enough free memory
                      (a minimum of %s is needed). Your calendar will not render or function
                      properly without CSS. Increase your PHP memory limit.',
                    'open-source-event-calendar'
                ),
                OSEC_LESS_MIN_AVAIL_MEMORY
            );
            $notification->store(
                $message,
                'error',
                1,
                [NotificationAdmin::RCPT_ADMIN],
                true
            );

            return false;
        }
        try {
            // Try to parse the css
            $css = $lessCtrl->parse_less_files($variables, false);
            if ($update_persistence) {
                $this->update_persistence_layer($css);
            }
        } catch (CacheWriteException) {
            // This means successful during parsing but problems persisting the CSS.
            $message = '<p>' . __(
                'The LESS file compiled correctly but there was an error
                    while saving the generated CSS to persistence.',
                'open-source-event-calendar'
            ) . '</p>';
            $notification->store($message, 'error');

            return false;
        } catch (Exception $e) {
            $this->notify_compile_error($e);

            return false;
        }

        return true;
    }

    /**
     * Removes the compiled CSS from every engine, its state, and what 1.1.x left behind.
     *
     * Only this site's own entries: other applications share APCu, other sites share the database.
     */
    public function clear_cache(): void
    {
        $name  = self::css_file_name();
        $path  = CachePath::factory($this->app);
        foreach ([CachePath::ROOT_OVERRIDE, CachePath::ROOT_UPLOADS] as $root) {
            $dir = $path->root_dir($root, 'css');
            if ($dir && is_dir($dir)) {
                CacheFile::for_dir($this->app, $dir, $root)->delete($name);
            }
        }
        if (CacheApcu::is_available()) {
            $apcu = new CacheApcu($this->app);
            $apcu->delete($name);
            $apcu->delete(self::COMPILED_CSS_KEY);
        }
        $db = CacheDb::factory($this->app);
        $db->delete($name);
        $db->delete(self::COMPILED_CSS_KEY);
        $this->app->options->delete(self::CSS_OPTION);
        $this->delete_legacy_state();

        // 1.1.x files: <site prefix>_osec_compiled.css, without the prefix in debug mode. Kept on upgrade, because
        // cached pages may still link them.
        $uploads = $path->root_dir(CachePath::ROOT_UPLOADS, 'css');
        foreach ($uploads ? glob($uploads . '*osec_compiled.css') ?: [] : [] as $file) {
            wp_delete_file($file);
        }
    }

    /**
     * Removes the 1.1.x option rows: the CSS URL or timestamp, the file cache index and the database copy.
     */
    public function delete_legacy_state(): void
    {
        $this->app->options->delete(self::COMPILED_CSS_KEY);
        CacheDb::factory($this->app)->delete(self::COMPILED_CSS_KEY);
        global $wpdb;
        foreach (
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $wpdb->esc_like(CacheFile::LEGACY_OPTION_PREFIX) . '%'
                )
            ) as $option
        ) {
            $this->app->options->delete($option);
        }
    }

    /*
     * Remove any (temp) content created by this class.
     */
    public function uninstall(bool $purge = false)
    {
        $this->clear_cache();
    }

    /**
     * An error from lessphp.
     */
    private function notify_compile_error(Exception $e): void
    {
        $message = '<p>' . sprintf(
            /* translators: Error message */
            __(
                '<strong>There was an error while compiling CSS.</strong>
                    The message returned was: <em>%s</em>',
                'open-source-event-calendar'
            ),
            esc_html($e->getMessage())
        ) . '</p>';
        NotificationAdmin::factory($this->app)->store($message, 'error', 1);
    }

    /**
     * The CSS from the engine the state names, null if there is none.
     */
    private function get_stored_css(): ?string
    {
        $state  = $this->get_state();
        $engine = $state ? $this->stored_engine($state) : null;
        try {
            return $engine?->get(self::css_file_name());
        } catch (CacheNotSetException) {
            return null;
        }
    }

    /**
     * Engine for writing, chosen when the CSS is compiled.
     */
    private function get_cache(): Cache
    {
        return $this->cache ??= CacheFactory::factory($this->app)->createCache('css');
    }

    /**
     * The engine the state names, or null if it is gone.
     */
    private function stored_engine(array $state): ?CacheInterface
    {
        switch ($state['engine']) {
            case 'file':
                $dir = CachePath::factory($this->app)->root_dir($state['root'], 'css');

                return $dir ? CacheFile::for_dir($this->app, $dir, $state['root']) : null;
            case 'apcu':
                return CacheApcu::is_available() ? new CacheApcu($this->app) : null;
            default:
                return CacheDb::factory($this->app);
        }
    }
}
