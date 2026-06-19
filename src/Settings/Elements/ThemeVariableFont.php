<?php

namespace Osec\Settings\Elements;

use Osec\Bootstrap\App;
use Osec\Theme\ThemeLoader;

/**
 * This class represent a LESS variable of type font.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Settings
 * @replaces Ai1ec_Less_Variable_Font
 */
class ThemeVariableFont extends SettingsAbstract
{
    /**
     * @var string Value saved when a custom font is used
     */
    public const CUSTOM_FONT = 'custom';

    /**
     * @var string suffix added to custom font fields
     */
    public const CUSTOM_FONT_ID_SUFFIX = '_custom';


    /**
     * @var string True if using a custom value
     */
    private bool $use_custom_value = false;

    /**
     * @var string The custom value.
     */
    private string $custom_value;

    /**
     *
     * @var array
     */
    private $fonts = [
        'Arial'               => 'Arial, Helvetica, sans-serif',
        'Arial Black'         => '"Arial Black", Gadget, sans-serif',
        'Comic Sans MS'       => '"Comic Sans MS", cursive',
        'Courier New'         => '"Courier New", monospace',
        'Georgia'             => 'Georgia, Georgia, serif',
        'Helvetica Neue'      => '"Helvetica Neue", Helvetica, Arial, sans-serif',
        'League Gothic'       => '"League Gothic", Impact, "Arial Black", Arial, sans-serif',
        'Impact'              => 'Impact, Charcoal, sans-serif',
        'Lucida Console'      => '"Lucida Console", Monaco, monospace',
        'Lucida Sans Unicode' => '"Lucida Sans Unicode", Lucida Grande, sans-serif',
        'MS Sans Serif'       => '"MS Sans Serif", Geneva, sans-serif',
        'MS Serif'            => '"MS Serif", "New York", serif',
        'Palatino'            => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
        'Tahoma'              => 'Tahoma, Geneva, sans-serif',
        'Times New Roman'     => '"Times New Roman", Times, serif',
        'Trebuchet Ms'        => '"Trebuchet MS", "Lucida Grande", sans-serif',
        'Verdana'             => 'Verdana, Geneva, sans-serif',
    ];

    /**
     * @param  App  $app
     * @param  array  $args
     */
    public function __construct(App $app, array $args)
    {
        parent::__construct($app, $args);

        /**
         * Allow extensions to add options to the font list.
         *
         * Alter default font list
         *
         * @since 1.0
         *
         * @param  array  $default_fonts
         */
        $this->fonts = apply_filters('osec_default_font_options_alter', $this->fonts);

        // Add Option for "Custom" to fontList
        if ( !in_array($args['value'], $this->fonts, true)) {
            $this->use_custom_value = true;
            $this->custom_value     = $args['value'];
            $this->args['value']   = self::CUSTOM_FONT;
        }
        $this->fonts[__('Custom...', 'open-source-event-calendar')] = self::CUSTOM_FONT;
    }

    public function render($html = '', $wrap = true): string
    {
        $args = [
            'label'  => $this->args['description'],
            'id'     => $this->args['id'],
            'input'  => [
                'id'    => $this->args['id'] . self::CUSTOM_FONT_ID_SUFFIX,
                'value' => '',
                'args'  => [
                    'placeholder' => __('Enter custom font(s)', 'open-source-event-calendar'),
                    'class'       => 'ai1ec-custom-font',
                ],
            ],
            'select' => [
                'id'      => $this->args['id'],
                'args'    => ['class' => 'ai1ec_font'],
                'options' => $this->getOptions(),
            ],
        ];

        if ( ! $this->use_custom_value) {
            $args['input']['args']['class'] = 'ai1ec-custom-font ai1ec-hide';
        } else {
            $args['input']['value'] = $this->custom_value;
        }

        return ThemeLoader::factory($this->app)
                          ->get_file('theme-options/font.twig', $args, true)
                          ->get_content();
    }

    public function getOptions()
    {
        $options = [];
        foreach ($this->fonts as $text => $key) {
            $option = [
                'text'  => $text,
                'value' => $key,
            ];
            if (
                $key === $this->args['value']
                || ($key === self::CUSTOM_FONT && $this->use_custom_value)
            ) {
                $option['args'] = ['selected' => 'selected'];
            }
            $options[] = $option;
        }

        return $options;
    }
}
