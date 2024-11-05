<?php

namespace Osec\App\Model\Date;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;
use Osec\Theme\ThemeLoader;

/**
 * Wrap library calls to date subsystem.
 *
 * Meant to increase performance and work around known bugs in environment.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Date
 * @replaces Ai1ec_Date_System
 */
class DateFormatsFrontend extends OsecBaseClass
{
    private const SECTION_ID = 'osec_date_format_section';
    public const FORMAT_SHORT = 'osec_date_format_short';
    public const FORMAT_NO_YEAR = 'osec_date_format_no_year';

    private static array $default = [
        'osec_date_format_short' =>  [
            'd/m/Y',
            'm/d/Y',
            'Y-m-d',
            'j.m.Y',
        ],
        'osec_date_format_no_year' => [
            'd/m',
            'M d',
            'm-d',
            'j.m.',
        ],
    ];

    public function initialize() : void {
        register_setting(
            'general',
            self::FORMAT_SHORT,
            [
                'default' => 'Y-M-D',
                'sanitize_callback' => array( $this, 'sanitizeShort' ),
            ]
        );

        register_setting(
            'general',
            self::FORMAT_NO_YEAR,
            [
                'default' => 'M-D',
                'sanitize_callback' => array( $this, 'sanitizeNoYear' )
            ]
        );

        add_settings_section(
            self::SECTION_ID,
            __( 'Osec Frontend Date Formats', OSEC_TXT_DOM ),
            function () {
                echo '<p>'
                     . __( 'Osec calendar uses WordPress default "date_format" and "time_format" above and provides additional <strong>frontend date formates</strong>.', OSEC_TXT_DOM )
                     . '<br />'
                     . __( 'Above WordPress default "date_format" is considered as "long" format.', OSEC_TXT_DOM )
                     . '<br />'
                     . __( 'Backend Formats are in Osec Settings -> Adding/Editing Events.', OSEC_TXT_DOM )
                  . '</p>';
            },
            'general'
        );

        add_settings_field(
            self::FORMAT_SHORT,
            __( 'Date Format Short', OSEC_TXT_DOM),
            [$this, 'renderShortDate'],
            'general',
            self::SECTION_ID,
        );

        add_settings_field(
            self::FORMAT_NO_YEAR,
            __( 'Date Format Short no year', OSEC_TXT_DOM),
            [$this, 'renderShortNoYear'],
            'general',
            self::SECTION_ID,
        );
    }

    /**
     * Get render-able defaults
     *
     * @param  string  $format
     *
     * @return array Render-able variables for requested settings
     * @throws Exception
     */
    public function getFormat(string $format) : array {
        if (!isset(self::$default[$format])) {
            throw new Exception('Unknown format. Got: ' . $format);
        }
        $def = self::$default[$format];
        foreach ($def as $id => $fmt) {
            $def[$id] = [
                'raw' => $fmt,
                'escaped' => esc_attr($fmt),
                'example' => date_i18n($fmt),
            ];
        }
        return $def;
    }

    public function renderShortNoYear() : void {
        $defaults = self::$default[self::FORMAT_NO_YEAR];
        $current_format = stripslashes(get_option(self::FORMAT_NO_YEAR, $defaults[0]));
        $is_custom = !in_array($current_format, $defaults);
        $args = [
            'id' => self::FORMAT_NO_YEAR,
            'time_formats' => $this->getFormat(self::FORMAT_NO_YEAR),
            'current_format' => $current_format,
            'is_custom' => $is_custom,
            'custom_label' =>  __( 'Custom:' ),
            'custom_accessibility' =>  __( 'enter a custom time format in the following field:' ),
            'custom_accessibility_label' =>  __( 'Custom time format:' ),
            'preview_label' => __( 'Preview:' ),
            'current_format_example' => date_i18n($current_format),
        ];
        ThemeLoader::factory($this->app)
            ->get_file('settings-date-format.twig', $args, true)
            ->render();
    }

    public function renderShortDate() : void {
        $defaults = self::$default[self::FORMAT_SHORT];
        $current_format = stripslashes(get_option(self::FORMAT_SHORT, $defaults[0]));
        $is_custom = !in_array($current_format, $defaults);
        $args = [
            'id' => self::FORMAT_SHORT,
            'time_formats' => $this->getFormat(self::FORMAT_SHORT),
            'current_format' => $current_format,
            'is_custom' => $is_custom,
            'custom_label' =>  __( 'Custom:' ),
            'custom_accessibility' =>  __( 'enter a custom time format in the following field:' ),
            'custom_accessibility_label' =>  __( 'Custom time format:' ),
            'preview_label' => __( 'Preview:' ),
            'current_format_example' => date_i18n($current_format),

        ];
        ThemeLoader::factory($this->app)
          ->get_file('settings-date-format.twig', $args, true)
          ->render();
    }

    public function sanitizeShort(string $value) : string {
        return $this->sanitize($value, self::FORMAT_SHORT);
    }

    public function sanitizeNoYear(string $value) : string {
        return $this->sanitize($value, self::FORMAT_NO_YEAR);
    }

    /**
     * @throws Exception
     */
    private function sanitize(string $value, string $format) : string {
        $default = self::$default[$format];
        if (in_array($value, $default)){
            return $value;
        }
        if ($value === 'custom' && isset($_POST[$format . '_custom'])){
            return $_POST[$format . '_custom'];
        }
        throw new Exception('Unknown format. Got: ' . $format);
    }
}
