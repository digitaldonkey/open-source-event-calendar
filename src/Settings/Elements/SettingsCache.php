<?php

namespace Osec\Settings\Elements;

use Osec\App\I18n;
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
        $cachePath        = (new CachePath())->getCacheData();
        $current_cache    = CacheFactory::factory($this->app)->createCache('test')->get_active_cache();
        $available_caches = [
            'CacheApcu' => [
                'name'            => 'CacheApcu',
                'is_available'    => $this->niceBoolean(CacheApcu::is_available()),
                'is_curren_cache' => $this->niceBoolean($current_cache === 'CacheApcu'),
                'notes'           => '@see: <a target="_blank" href="https://www.php.net/manual/en/book.apcu.php">'
                                     . 'php.net/manual/en/book.apcu.php</a>',
                'constant'        => 'OSEC_ENABLE_CACHE_APCU',
            ],
            'CacheFile' => [
                'name'            => 'CacheFile',
                'is_available'    => $this->niceBoolean(CacheFile::is_available()),
                'is_curren_cache' => $this->niceBoolean($current_cache === 'CacheFile'),
                'notes'           => $cachePath ? '<div style="max-width: 100%; overflow-x: scroll;">'
                                      . $cachePath['path'] . '<br />'
                                      . $cachePath['url'] . '</div>' : __('Not Available', 'open-source-event-calendar'),
                'constant'        => 'OSEC_ENABLE_CACHE_FILE',
            ],
            'CacheDb'   => [
                'name'            => 'CacheDb',
                'is_available'    => $this->niceBoolean(CacheDb::is_available()),
                'is_curren_cache' => $this->niceBoolean($current_cache === 'CacheDb'),
                'notes'           => '',
                'constant'        => 'Can\'t be disabled',
            ],
        ];

        $args = [
            'current_cache'        => $current_cache,
            'available_caches'     => $available_caches,
            'twig_cache_available' => (
                CacheFile::OSEC_FILE_CACHE_UNAVAILABLE !== $this->args['value']
                && ! empty($this->args['value'])
            ),
            'twig_path'            => $this->args['value'],
            'id'                   => $this->args['id'],
            'label'                => $this->args['renderer']['label'],
            'text'                 => [
                'refresh' => I18n::__('Check again'),
                'nocache' => I18n::__('Templates cache is not writable'),
                'okcache' => I18n::__('Twig cache is writable (FileCache("twig"))'),
                'rescan'  => I18n::__('Checking...'),
                'title'   => I18n::__('Performance Report'),
            ],
        ];
        return $args;
    }

    protected function niceBoolean($bool): string
    {
        return (string)(bool)$bool;
    }
}
