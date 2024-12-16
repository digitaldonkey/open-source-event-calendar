<?php

namespace Osec\App\Controller;

use Exception;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Bootstrap\App;
use Osec\Bootstrap\MemoryCheck;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\Cache;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheNotSetException;
use Osec\Cache\CacheWriteException;
use Osec\Exception\BootstrapException;
use Osec\Http\Response\ResponseHelper;

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
     * e.h: <link rel="stylesheet" id="ai1ec_style-css"
     * href="//ddev-wordpress.ddev.site/?osec-css-cache=1725894108&amp;ver=2.3.1" media="all">
     */
    public const REQUEST_CSS_PARAM = 'osec-css-cache';

    /**
     * This is for testing purpose, set it to OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST value.
     */
    public const PARSE_LESS_FILES_AT_EVERY_REQUEST = OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST;

    /**
     * Identifyer to CSS cache setting as wp-option.
     */
    public const COMPILED_CSS_KEY = 'osec_compiled.css';

    /**
     * This option is set when... CSS needs recompile,
     */
    public const COMPILED_CSS_CACHE_KEY = 'osec_invalidate_css_cache';

    /**
     * @var
     */
    private ?Cache $cache;

    /**
     * @param  App  $app
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->cache = CacheFactory::factory($this->app)->createCache('css');
    }

    // **
    // *
    // * Get if file cache is enabled
    // *
    // * @return boolean
    // */
    // public function is_file_cache_enabled()
    // {
    // return $this->cache->is_file_cache();
    // }

    /**
     * Renders the css for our frontend.
     *
     * Sets etags to avoid sending not needed data
     */
    public function render_css()
    {
        header('HTTP/1.1 200 OK');
        header('Content-Type: text/css', true, 200);
        // Aggressive caching to save future requests from the same client.
        $etag = '"' . md5(__FILE__ . $_GET[self::REQUEST_CSS_PARAM]) . '"';
        header('ETag: ' . $etag);
        $max_age = 31536000;
        header(
            'Expires: ' .
            gmdate(
                'D, d M Y H:i:s',
                UIDateFormats::factory($this->app)->current_time() + $max_age
            ) .
            ' GMT'
        );
        header('Cache-Control: public, max-age=' . $max_age);
        if (
            empty($_SERVER['HTTP_IF_NONE_MATCH']) ||
            $etag !== stripslashes((string)$_SERVER['HTTP_IF_NONE_MATCH'])
        ) {
            echo $this->get_compiled_css();
        } else {
            // Not modified!
            status_header(304);
        }
        // We're done!
        ResponseHelper::stop();
    }

    /**
     * Try to get the CSS from cache.
     * If it's not there re-generate it and save it to cache
     * If we are in preview mode, recompile the css using the theme present in
     * the url.
     */
    public function get_compiled_css()
    {
        try {
            // If we want to force a recompile, we throw an exception.
            if (self::PARSE_LESS_FILES_AT_EVERY_REQUEST) {
                throw new CacheNotSetException();
            } else {
                // This throws an exception if the key is not set
                $css = $this->cache->get(self::COMPILED_CSS_KEY);

                return $css;
            }
        } catch (CacheNotSetException $e) {
            $css = LessController::factory($this->app)->parse_less_files();
            try {
                $this->update_persistence_layer($css);

                return $css;
            } catch (CacheWriteException $e) {
                if ( ! self::PARSE_LESS_FILES_AT_EVERY_REQUEST) {
                    NotificationAdmin::factory($this->app)->store(
                        sprintf(
                            __(
                                'Your CSS is being compiled on every request, '
                                    . 'which causes your calendar to perform slowly. The following error occurred: %s',
                                'open-source-event-calendar'
                            ),
                            $e->getMessage()
                        ),
                        'error',
                        2,
                        [NotificationAdmin::RCPT_ADMIN],
                        true
                    );
                }

                // If something is really broken, still return the css.
                // This means we parse it every time. This should never happen.
                return $css;
            }
        }
    }

    /**
     * @param $css
     *
     * @return void
     */
    public function update_persistence_layer($css)
    {
        if ($this->cache->is_file_cache()) {
            $cacheData = $this->cache->engine->setWithFileInfo(self::COMPILED_CSS_KEY, $css);
            $this->store_css_cache($cacheData['url']);
        } else {
            $this->cache->set(self::COMPILED_CSS_KEY, $css);
            // At any other cache the self::COMPILED_CSS_KEY
            // Value will be integer time.
            // VALUSE is_numeric
            $this->store_css_cache(time());
        }
    }

    /**
     * Save the path to the CSS file or false to load standard CSS
     *
     * @param  mixed|false  $value
     */
    private function store_css_cache(mixed $value = false)
    {
        $this->app->options->set(
            self::COMPILED_CSS_KEY,
            $value,
            true
        );
        // Tell render cache to update.
        $this->app->options->set(self::COMPILED_CSS_CACHE_KEY, true, true);
    }

    /**
     * Create the link that will be added to the frontend
     */
    public function add_link_to_html_for_frontend(): void
    {
        $url = $this->get_css_url();
        if ('' !== $url && ! is_admin()) {
            wp_enqueue_style('ai1ec_style', $url, [], OSEC_VERSION);
        }
    }

    /**
     * Get the url to retrieve the css
     *
     * @return string
     */
    public function get_css_url()
    {
        // get what's saved. I t could be false, a int or a string.
        // if it's false or a int, use PHP to render CSS
        $saved_par = $this->app->options->get(self::COMPILED_CSS_KEY);
        // $saved_par = Number value required to display css in Header,

        // if it's empty it's a new install probably. Return static css.
        if (null === $saved_par) {
            $theme = $this->app->options->get('osec_current_theme');

            return ResponseHelper::remove_protocols(
            /**
             * Alter css file.
             *
             * @since 1.0
             *
             * @param  string  $parsed_css  Css file path
             */
                apply_filters('osec_frontend_standard_css_url', $theme['theme_url'] . '/css/ai1ec_parsed_css.css')
            );
        }
        // if it's numeric, just consider it a new install
        if (is_numeric($saved_par)) {
            // if (TRUE) {

            // "Link CSS in <head> section
            // when file cache is unavailable."
            if ($this->app->settings->get('render_css_as_link')) {
                $time = (int)$saved_par;

                return ResponseHelper::remove_protocols(
                    add_query_arg(
                        [self::REQUEST_CSS_PARAM => $time],
                        trailingslashit(get_site_url())
                    )
                );
            } else {
                // Write CSS into Style tag.
                add_action('wp_head', $this->echo_css(...));

                return '';
            }
        }

        // otherwise return the string
        return ResponseHelper::remove_protocols(
            $saved_par
        );
    }

    public function echo_css()
    {
        echo '<style id="aliec">';
        echo $this->get_compiled_css();
        echo '</style>';
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
                    '<p>' . __(
                        "Theme options were successfully reset to their default values. <a href='%s'>Visit site</a>",
                        'open-source-event-calendar'
                    ) . '</p>',
                    get_site_url()
                );
            } else {
                $message = sprintf(
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
        if ( ! $lessCtrl->is_compilation_needed($variables)) {
            $this->app->options->delete(self::COMPILED_CSS_KEY);

            return true;
        }

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
            $css = $lessCtrl->parse_less_files($variables);
            // Reset the parse time to force a browser reload of the CSS, whether we are
            // updating persistence or not. Do it here to be sure files compile ok.
            // TODO Verify that this is not necessary anymore.
            $this->store_css_cache(time());

            if ($update_persistence) {
                $this->update_persistence_layer($css);
            } else {
                $this->cache->delete(self::COMPILED_CSS_KEY);
            }
        } catch (CacheWriteException) {
            // This means successful during parsing but problems persisting the CSS.
            $message = '<p>' . __(
                'The LESS file compiled correctly but there was an error while saving the generated CSS to persistence.',
                'open-source-event-calendar'
            ) . '</p>';
            $notification->store($message, 'error');

            return false;
        } catch (Exception $e) {
            // An error from lessphp.
            $message = sprintf(
                __(
                    '<p><strong>There was an error while compiling CSS.</strong>
                        The message returned was: <em>%s</em></p>',
                    'open-source-event-calendar'
                ),
                $e->getMessage()
            );
            $notification->store($message, 'error', 1);

            return false;
        }

        return true;
    }

    /*
     * Remove any (temp) content created by this class.
     */

    public function uninstall(bool $purge = false)
    {
        $this->cache->clear_cache();
    }
}
