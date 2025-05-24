<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;

class BigCalendarBlockController extends OsecBaseClass
{
    private array $blockFile;
    private string $assetPath;

    private string $assetUrl;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->assetPath = 'blocks/build/react-big-calendar/';
        $this->assetUrl = trailingslashit(OSEC_URL)  . $this->assetPath;
        $this->blockFile = json_decode(
            file_get_contents(OSEC_PATH . $this->assetPath . 'block.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function registerCalendarBlock()
    {
        // TODO
        //   Currently at least the Editor Script is a duplication
        //   of classic block.
        //
        // Backend (Block Edit)
        wp_register_script(
            'osec-calendar-block-react-big-calendar-backend',
            $this->assetUrl . 'index.js',
            [
                // Dependencies
                'wp-blocks',
                'wp-i18n',
                'wp-block-editor',
                'wp-data',
                'wp-core-data',
            ],
            OSEC_VERSION,
            true
        );
        wp_register_style(
            'osec-editor-style-react-big-calendar',
            $this->assetUrl . 'index.css',
            [],
            OSEC_VERSION
        );
        register_block_style(
            'open-source-event-calendar/react-big-calendar',
            [
                'name' => 'osec-editor-style-react-big-calendar',
                'label' => __('osec-editor-style-react-big-calendar', 'open-source-event-calendar'),
                'style_handle' => 'osec-editor-style-react-big-calendar',
            ]
        );

        // Frontend
        // Automatically includes 'import' dependencies.
        $script_asset_path = OSEC_PATH . $this->assetPath . 'index.asset.php';
        if (! file_exists($script_asset_path)) {
            throw new Exception(
                esc_html__(
                    'You need to run `npm start` or `npm run build` for the "my-namespace/my-block" block first.',
                    'open-source-event-calendar'
                )
            );
        }
        /** @var TYPE_NAME $script_asset */
        $script_asset = require($script_asset_path);

        wp_register_script(
            'osec-calendar-block-react-big-calendar-frontend',
            $this->assetUrl . 'view.js',
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        // TODO Currently not in use.
        //   Evaluate cleaner ways for dayjs locale loading.

        wp_localize_script(
            'osec-calendar-block-react-big-calendar-frontend',
            'osecSettings',
            [
                'dayjsLocaleUri' => OSEC_URL . '/blocks/build/react-big-calendar/dayjs-locales/'
            ]
        );


        //   CSS - react-big-calendar/lib/css/react-big-calendar.css
        //   is imported in view.scss.
        wp_register_style(
            'osec-calendar-block-react-big-calendar-css',
            $this->assetUrl . 'style-index.css',
            [],
            filemtime(OSEC_PATH . $this->assetPath . 'style-index.css')
        );


        register_block_type(
            $this->blockFile['name'],
            array_merge_recursive(
                $this->blockFile,
                [
                    'editor_script' => 'osec-calendar-block-react-big-calendar-backend',
                    'view_script' => 'osec-calendar-block-react-big-calendar-frontend',
                    'style' => 'osec-calendar-block-react-big-calendar-css',
                    'render_callback' => function (array $attributes, string $content): string {
                        $id = 'osec-react-big-calendar-' . substr(md5(json_encode($attributes)), 0, 12);
                        $attributes = array_merge($attributes, [
                            'id' => $id,
                        ]);
                        $attributes_json = htmlspecialchars(json_encode($attributes), ENT_QUOTES, 'UTF-8');
                        $content .= '<div '
                                        . get_block_wrapper_attributes([
                                            'class' => 'osec-react-big-calendar'
                                        ])
                                        . 'id="' . $id . '"'
                                        . 'data-props="' . $attributes_json . '"'
                                    . '>'
                                    . '</div>';

                        return $content;
                    },

                ]
            )
        );
    }
}
