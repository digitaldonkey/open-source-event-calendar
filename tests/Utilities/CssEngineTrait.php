<?php

namespace Osec\Tests\Utilities;

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
        $osec_app->inject_object(FrontendCssController::class, new FrontendCssController($osec_app));

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
