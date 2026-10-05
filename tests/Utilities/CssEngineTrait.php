<?php

namespace Osec\Tests\Utilities;

use Osec\App\Controller\BootstrapController;
use Osec\App\Controller\FrontendCssController;
use Osec\Cache\Cache;
use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheFile;
use Osec\Cache\CacheInterface;
use Osec\Cache\CacheNotSetException;
use Osec\Cache\CachePath;

/**
 * Makes the CSS compile use one cache engine, through the injected CacheFactory, and gives a fresh
 * FrontendCssController (the controller is a singleton, H13).
 */
trait CssEngineTrait
{
    protected ?Cache $css_cache = null;

    /**
     * @param  string  $engine  'apcu', 'file' or 'db', or any name with $engine_object.
     */
    protected function use_css_engine(string $engine, ?CacheInterface $engine_object = null): Cache
    {
        global $osec_app;

        $this->css_cache = new Cache(
            'css',
            $engine_object ?? match ($engine) {
                'apcu' => new CacheApcu($osec_app),
                'file' => CacheFile::createFileCacheInstance($osec_app, 'css'),
                'db'   => CacheDb::factory($osec_app),
            }
        );
        $osec_app->inject_object(
            CacheFactory::class,
            new class ($osec_app, $this->css_cache) extends CacheFactory {
                public function __construct($app, private Cache $cache)
                {
                    parent::__construct($app);
                }

                public function createCache(string $cache_id): Cache
                {
                    return $this->cache;
                }
            }
        );
        $osec_app->inject_object(FrontendCssController::class, $this->web_request_controller());

        return $this->css_cache;
    }

    /**
     * Removes the CSS of the engine in use and the CSS state, then restores the real factory and controller.
     */
    protected function reset_css_engine(): void
    {
        global $osec_app;

        if ($this->css_cache) {
            $this->css_cache->delete(FrontendCssController::css_file_name());
            $this->css_cache = null;
        }
        $osec_app->options->delete(FrontendCssController::CSS_OPTION);
        $osec_app->inject_object(CachePath::class, new CachePath($osec_app));
        $osec_app->inject_object(CacheFactory::class, new CacheFactory($osec_app));
        $osec_app->inject_object(FrontendCssController::class, new FrontendCssController($osec_app));
    }

    /**
     * A controller that sees a web request (PHPUnit always runs in the CLI), or the CLI when $cli is true.
     */
    protected function web_request_controller(bool $cli = false): FrontendCssController
    {
        global $osec_app;

        return new class ($osec_app, $cli) extends FrontendCssController {
            public function __construct($app, private bool $cli)
            {
                parent::__construct($app);
            }

            protected function is_cli(): bool
            {
                return $this->cli;
            }
        };
    }

    /**
     * The next request's init: BootstrapController::verifyCache() as registered on 'init'.
     */
    protected function css_next_request(): void
    {
        global $wp_filter;

        foreach ($wp_filter['init']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $fn = $callback['function'];
                if (is_array($fn) && $fn[0] instanceof BootstrapController && 'verifyCache' === $fn[1]) {
                    $fn();

                    return;
                }
            }
        }
        $this->fail('BootstrapController::verifyCache() is not registered on init.');
    }

    /**
     * The compile lock commits the test transaction, so whatever was written before it survives the rollback.
     * Call after parent::tear_down().
     */
    protected function commit_css_cleanup(): void
    {
        global $osec_app, $wpdb;

        // Through Options, which also keeps the values in memory.
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        $osec_app->options->delete(FrontendCssController::CSS_OPTION);
        delete_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT);
        delete_option('osec_xlock_' . FrontendCssController::COMPILE_LOCK);
        $wpdb->query('COMMIT');
    }

    /**
     * The CSS the engine in use holds, or null.
     */
    protected function stored_css(): ?string
    {
        try {
            return $this->css_cache->get(FrontendCssController::css_file_name());
        } catch (CacheNotSetException) {
            return null;
        }
    }
}
