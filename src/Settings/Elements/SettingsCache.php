<?php

namespace Osec\Settings\Elements;

use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheFile;
use Osec\Cache\CachePath;
use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page html.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Cache
 */
class SettingsCache extends SettingsAbstract
{
    public function render($html = '', $wrap = true): string
    {
        $args = $this->get_twig_cache_args();
        $file = ThemeLoader::factory($this->app)
                           ->get_file('setting/cache_info.twig', $args, true);

        return $this->warp_in_form_group($file->get_content());
    }

    /**
     * Returns data for Twig template.
     *
     * @return array Data for template
     */
    public function get_twig_cache_args()
    {
        $cachePath = (new CachePath())->getCacheData();
        if ($cachePath) {
            $cachePathTxt = '<div style="max-width: 100%; overflow-x: scroll;">'
                                . $cachePath['path']
                                . '<br />'
                                . $cachePath['url']
                            . '</div>';
        } else {
            $cachePathTxt = __('Not Available', 'open-source-event-calendar');
        }

        $current_cache    = CacheFactory::factory($this->app)->createCache('test')->get_active_cache();
        $available_caches = [
            'CacheApcu' => [
                'name'            => 'CacheApcu',
                'is_available'    => $this->niceBoolean(CacheApcu::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheApcu'),
                'notes'           => '@see: <a target="_blank" href="https://www.php.net/manual/en/book.apcu.php">'
                                     . 'php.net/manual/en/book.apcu.php</a>',
                'constant'        => 'OSEC_ENABLE_CACHE_APCU',
            ],
            'CacheFile' => [
                'name'            => 'CacheFile',
                'is_available'    => $this->niceBoolean(CacheFile::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheFile'),
                'notes'           => $cachePathTxt,
                'constant'        => 'OSEC_ENABLE_CACHE_FILE',
            ],
            'CacheDb'   => [
                'name'            => 'CacheDb',
                'is_available'    => $this->niceBoolean(CacheDb::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheDb'),
                'notes'           => '',
                'constant'        => 'Can\'t be disabled',
            ],
        ];

        $twigCache = CacheFile::createFileCacheInstance($this->app, 'twig');

        $args = [
            'current_cache'        => $current_cache,
            'available_caches'     => $available_caches,
            'twig_cache_available' => (bool) $twigCache,
            'twig_path'            => $twigCache ? $twigCache->getCachePath() : CacheFile::OSEC_FILE_CACHE_UNAVAILABLE,
            'id'                   => $this->args['id'],
            'label'                => $this->args['renderer']['label'],
            'text'                 => [
                'refresh' => __('Check again', 'open-source-event-calendar'),
                'nocache' => __('Templates cache is not writable', 'open-source-event-calendar'),
                'okcache' => __('Twig cache is writable (FileCache("twig"))', 'open-source-event-calendar'),
                'rescan'  => __('Checking...', 'open-source-event-calendar'),
                'title'   => __('Performance Report', 'open-source-event-calendar'),
            ],
        ];

        return $args;
    }

    protected function niceBoolean($boolVar): string
    {
        return (string)(bool)$boolVar;
    }
}
