<?php

namespace Osec\Settings\Elements;

use Osec\App\Model\DatabaseCharset;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;

/**
 * The "Database" box on the settings page: tables that are not utf8mb4 and the button converting them.
 * Shown only while a table needs it (AdminPageSettings::add_meta_box()).
 */
class SettingsDatabase extends OsecBaseClass
{
    public function render()
    {
        $tables = DatabaseCharset::factory($this->app)->tables_to_convert();
        ThemeLoader::factory($this->app)
            ->get_file(
                'setting/database_charset.twig',
                [
                    'tables'  => $tables,
                    'convert' => [
                        'url'   => rest_url('osec/v1/database/convert'),
                        'nonce' => wp_create_nonce('wp_rest'),
                    ],
                    'text'    => [
                        'info'    => implode(
                            ' ',
                            [
                                __(
                                    'These tables cannot store emoji and other 4-byte characters yet:',
                                    'open-source-event-calendar'
                                ),
                                __(
                                    'an event or feed containing one fails to save.',
                                    'open-source-event-calendar'
                                ),
                                __('Converting them changes no stored text.', 'open-source-event-calendar'),
                                __('It takes about a second per 100,000 events.', 'open-source-event-calendar'),
                                __('Back up the database first.', 'open-source-event-calendar'),
                            ]
                        ),
                        'table'     => __('Table', 'open-source-event-calendar'),
                        'collation' => __('Collation', 'open-source-event-calendar'),
                        'other'     => __('not utf8, will not be converted', 'open-source-event-calendar'),
                        'button'    => __('Convert database tables', 'open-source-event-calendar'),
                        'busy'      => __('Converting...', 'open-source-event-calendar'),
                        'failed'    => __('The tables could not be converted.', 'open-source-event-calendar'),
                    ],
                ],
                true
            )
            ->render();
    }
}
