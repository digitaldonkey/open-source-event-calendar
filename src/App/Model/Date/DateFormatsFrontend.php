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
    public const FORMAT_SHORT_DEFAULT = 'd/m/y';
    public const FORMAT_NO_YEAR = 'osec_date_format_no_year';
    public const FORMAT_NO_YEAR_DEFAULT = 'd/m';

    private static array $default = [
        'osec_date_format_short'   => [
            'd/m/Y',
            'm/d/Y',
            'Y-m-d',
            'd.m.Y',
        ],
        'osec_date_format_no_year' => [
            'd/m',
            'M d',
            'm-d',
            'd.m.',
        ],
    ];

    public function initialize(): void
    {
        register_setting(
            'general',
            self::FORMAT_SHORT,
            [
                'default'           => self::FORMAT_SHORT_DEFAULT,
                'sanitize_callback' => [$this, 'sanitizeShort'],
            ]
        );

        register_setting(
            'general',
            self::FORMAT_NO_YEAR,
            [
                'default'           => self::FORMAT_NO_YEAR_DEFAULT,
                'sanitize_callback' => [$this, 'sanitizeNoYear'],
            ]
        );

        add_settings_section(
            self::SECTION_ID,
            __('Osec Frontend Date Formats', 'open-source-event-calendar'),
            function () {
                echo '<p>'
                . esc_html__(
                    'Osec calendar uses WordPress default "date_format" and "time_format" above and 
                        provides additional <strong>frontend date formats</strong>.',
                    'open-source-event-calendar'
                )
                . '<br />'
                . esc_html__(
                    'Above WordPress default "date_format" is considered as "long" format.',
                    'open-source-event-calendar'
                )
                . '<br />'
                . esc_html__(
                    'Backend Formats are in Osec Settings -> Adding/Editing Events.',
                    'open-source-event-calendar'
                )
                . '</p>';
            },
            'general' // Page to add to.
        );

        add_settings_field(
            self::FORMAT_SHORT,
            __('Date Format Short', 'open-source-event-calendar'),
            [$this, 'renderShortDate'],
            'general',
            self::SECTION_ID,
        );

        add_settings_field(
            self::FORMAT_NO_YEAR,
            __('Date Format Short no year', 'open-source-event-calendar'),
            [$this, 'renderShortNoYear'],
            'general',
            self::SECTION_ID,
        );
    }

    public function renderShortNoYear(): void
    {
        $defaults       = self::$default[self::FORMAT_NO_YEAR];
        $current_format = stripslashes(get_option(self::FORMAT_NO_YEAR, $defaults[0]));
        $is_custom      = ! in_array($current_format, $defaults, true);
        $args           = [
            'id'                         => self::FORMAT_NO_YEAR,
            'time_formats'               => $this->getFormat(self::FORMAT_NO_YEAR),
            'current_format'             => $current_format,
            'is_custom'                  => $is_custom,
            'custom_label'               => __('Custom:', 'open-source-event-calendar'),
            'custom_accessibility'       => __(
                'enter a custom time format in the following field:',
                'open-source-event-calendar'
            ),
            'custom_accessibility_label' => __('Custom time format:', 'open-source-event-calendar'),
            'preview_label'              => __('Preview:', 'open-source-event-calendar'),
            'current_format_example'     => date_i18n($current_format),
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('settings_date_format.twig', $args, true)
                   ->render();
    }

    /**
     * Get render-able defaults
     *
     * @param  string  $format
     *
     * @return array Render-able variables for requested settings
     * @throws Exception
     */
    public function getFormat(string $format): array
    {
        if (! isset(self::$default[$format])) {
            throw new Exception(
                esc_html(
                    'Unknown format. Got: ' . $format
                )
            );
        }
        $def = self::$default[$format];
        foreach ($def as $id => $fmt) {
            $def[$id] = [
                'raw'     => $fmt,
                'escaped' => esc_attr($fmt),
                'example' => date_i18n($fmt),
            ];
        }

        return $def;
    }

    public function renderShortDate(): void
    {
        $defaults       = self::$default[self::FORMAT_SHORT];
        $current_format = stripslashes(get_option(self::FORMAT_SHORT, $defaults[0]));
        $is_custom      = ! in_array($current_format, $defaults, true);
        $args           = [
            'id'                         => self::FORMAT_SHORT,
            'time_formats'               => $this->getFormat(self::FORMAT_SHORT),
            'current_format'             => $current_format,
            'is_custom'                  => $is_custom,
            'custom_label'               => __('Custom:', 'open-source-event-calendar'),
            'custom_accessibility'       => __(
                'enter a custom time format in the following field:',
                'open-source-event-calendar'
            ),
            'custom_accessibility_label' => __('Custom time format:', 'open-source-event-calendar'),
            'preview_label'              => __('Preview:', 'open-source-event-calendar'),
            'current_format_example'     => date_i18n($current_format),

        ];
        ThemeLoader::factory($this->app)
                   ->get_file('settings_date_format.twig', $args, true)
                   ->render();
    }

    public function sanitizeShort(string $value): string
    {
        return $this->sanitize($value, self::FORMAT_SHORT);
    }

    /**
     * @throws Exception
     */
    private function sanitize(string $value, string $format): string
    {
        $default = self::$default[$format];
        if (in_array($value, $default, true)) {
            return $value;
        }
        $key = $format . '_custom';
        // phpcs:ignore WordPress.Security.NonceVerification
        if ($value === 'custom' && isset($_POST[$key])) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $customVal = sanitize_key($_POST[$key]);
            // Check if it works.
            if ($customVal && (bool) strtotime(date_format(date_create(), $customVal))) {
                return $customVal;
            }
        }
        throw new Exception(esc_html(
            'Unknown format. Got: ' . $format
        ));
    }

    public function sanitizeNoYear(string $value): string
    {
        return $this->sanitize($value, self::FORMAT_NO_YEAR);
    }
}
