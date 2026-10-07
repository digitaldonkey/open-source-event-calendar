<?php

namespace Osec\Settings\Elements;

use Osec\App\Controller\FrontendCssController;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CacheFactory;
use Osec\Cache\CacheFile;
use Osec\Cache\CachePath;
use Osec\Cache\CacheTransient;
use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page html.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Cache
 */
class SettingsCache extends OsecBaseClass
{
    public function render($html = '', $wrap = true)
    {
        $args = $this->get_twig_cache_args();
        ThemeLoader::factory($this->app)
           ->get_file('setting/cache_info.twig', $args, true)
           ->render();
    }

    /**
     * Returns data for Twig template.
     *
     * @return array Data for template
     */
    public function get_twig_cache_args()
    {
        $cachePath = CachePath::factory($this->app)->getCacheData('css');
        if ($cachePath) {
            $cachePathTxt = '<div style="max-width: 100%; overflow: auto;">'
                                . esc_html($cachePath['path'])
                                . '<br />'
                                . esc_html((string) $cachePath['url'])
                            . '</div>';
        } else {
            $cachePathTxt = __('Not Available', 'open-source-event-calendar');
        }

        $current_cache    = CacheFactory::factory($this->app)->createCache('css')->get_active_cache();
        $available_caches = [
            'CacheFile' => [
                'name'            => 'CacheFile',
                'is_available'    => $this->niceBoolean(CacheFile::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheFile'),
                'notes'           => $cachePathTxt,
                'constant'        => 'OSEC_ENABLE_CACHE_FILE',
            ],
            'CacheTransient' => [
                'name'             => 'CacheTransient',
                'is_available'     => $this->niceBoolean(CacheTransient::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheTransient'),
                'notes'            => esc_html__(
                    'Only with a persistent object cache (Redis, Memcached).',
                    'open-source-event-calendar'
                ),
                'constant'         => 'OSEC_ENABLE_CACHE_TRANSIENT',
            ],
            'CacheApcu' => [
                'name'            => 'CacheApcu',
                'is_available'    => $this->niceBoolean(CacheApcu::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheApcu'),
                'notes'           => '@see: <a target="_blank" href="https://www.php.net/manual/en/book.apcu.php">'
                                     . 'php.net/manual/en/book.apcu.php</a>',
                'constant'        => 'OSEC_ENABLE_CACHE_APCU',
            ],
            'CacheDb'   => [
                'name'            => 'CacheDb',
                'is_available'    => $this->niceBoolean(CacheDb::is_available()),
                'is_current_cache' => $this->niceBoolean($current_cache === 'CacheDb'),
                'notes'           => '',
                'constant'        => 'Can\'t be disabled',
            ],
        ];

        $twigCache = ThemeLoader::factory($this->app)->get_cache_dir();

        $args = [
            'current_cache'        => $current_cache,
            'available_caches'     => $available_caches,
            'css_location'         => $this->css_location(),
            'twig_cache_available' => (bool) $twigCache,
            'twig_path'            => $twigCache ?? CacheFile::OSEC_FILE_CACHE_UNAVAILABLE,
            'id'                   => 'twig_cache',
            'info'                 => __('Caches used in given order.', 'open-source-event-calendar'),
            'rescan_nonce'         => wp_create_nonce(ThemeLoader::RESCAN_NONCE),
            'clear'                => [
                'url'   => rest_url('osec/v1/cache/clear'),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
            'text'                 => [
                'refresh'       => __('Check again', 'open-source-event-calendar'),
                'nocache'       => __('Templates cache is not writable', 'open-source-event-calendar'),
                'okcache'       => __('Twig cache is writable', 'open-source-event-calendar'),
                'rescan'        => __('Checking...', 'open-source-event-calendar'),
                'title'         => __('Performance Report', 'open-source-event-calendar'),
                'name'          => __('Name', 'open-source-event-calendar'),
                'available'     => __('Available', 'open-source-event-calendar'),
                'current'       => __('CSS', 'open-source-event-calendar'),
                'constant'      => __('Constant', 'open-source-event-calendar'),
                'notes'         => __('Notes', 'open-source-event-calendar'),
                'css_title'     => __('Compiled CSS', 'open-source-event-calendar'),
                'twig_title'    => __('Twig Cache', 'open-source-event-calendar'),
                'twig_info'     => __('Compiled templates are stored as PHP files.', 'open-source-event-calendar') . ' '
                    . __('Without a writable folder, they compile on every request.', 'open-source-event-calendar'),
                'clear_title'   => __('Clear all caches', 'open-source-event-calendar'),
                'clear_button'  => __('Clear all caches', 'open-source-event-calendar'),
                'clear_busy'    => __('Clearing...', 'open-source-event-calendar'),
                'clear_failed'  => __('The caches could not be cleared.', 'open-source-event-calendar'),
                'clear_info'    => implode(
                    ' ',
                    [
                        __(
                            'Deletes the compiled calendar CSS from every cache (file, APCu, database),',
                            'open-source-event-calendar'
                        ),
                        __(
                            'the compiled Twig templates of this site and what older versions left behind.',
                            'open-source-event-calendar'
                        ),
                        __('The CSS is compiled first and rebuilt right away.', 'open-source-event-calendar'),
                        __('If it does not compile, the current CSS is kept.', 'open-source-event-calendar'),
                        __('Templates are rebuilt on the next page view.', 'open-source-event-calendar'),
                        __('Settings and theme options are not changed.', 'open-source-event-calendar'),
                    ]
                ),
            ],
        ];

        return $args;
    }

    /**
     * Where the compiled CSS is now, from the state of the last compile.
     *
     * @return array<int, array{label: string, value: string, code: bool}> One row per line; code: a path or URL.
     */
    private function css_location(): array
    {
        $ctrl  = FrontendCssController::factory($this->app);
        $state = $ctrl->get_state();
        if ( ! $state) {
            return [
                [
                    'label' => __('Status', 'open-source-event-calendar'),
                    'value' => __(
                        'Not compiled yet: it is compiled on the next page view with a calendar.',
                        'open-source-event-calendar'
                    ),
                    'code'  => false,
                ],
            ];
        }
        $stored = [
            'file' => __('Static file, sent by the web server', 'open-source-event-calendar'),
            'transient' => __('Object cache (site transient), sent by PHP', 'open-source-event-calendar'),
            'apcu' => __('APCu, sent by PHP', 'open-source-event-calendar'),
            'db'   => __('Database, sent by PHP', 'open-source-event-calendar'),
        ];
        $rows   = [
            [
                'label' => __('Stored as', 'open-source-event-calendar'),
                'value' => $stored[$state['engine']],
                'code'  => false,
            ],
        ];
        if ('file' === $state['engine']) {
            $rows[] = [
                'label' => __('File', 'open-source-event-calendar'),
                'value' => CachePath::factory($this->app)->root_dir($state['root'], 'css') . $state['file'],
                'code'  => true,
            ];
        }
        $rows[] = [
            'label' => __('URL', 'open-source-event-calendar'),
            'value' => $ctrl->get_css_url(),
            'code'  => true,
        ];
        $rows[] = [
            'label' => __('Version', 'open-source-event-calendar'),
            'value' => $state['ver'],
            'code'  => true,
        ];

        return $rows;
    }

    protected function niceBoolean($boolVar): string
    {
        return (string)(bool)$boolVar;
    }
}
