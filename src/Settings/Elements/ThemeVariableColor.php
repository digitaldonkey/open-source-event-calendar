<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * This class represents a LESS variable of type color. It supports hex, rgb
 * and rgba formats.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Settings
 * @replaces Ai1ec_Less_Variable_Color
 */
class ThemeVariableColor extends SettingsAbstract
{

    /**
     * @var boolean
     */
    protected $readonly = false;

    public function render($html = '', $wrap = true) : string
    {
        $readonly = $this->readonly === true ? 'readonly' : '';

        $args = [
            'label'    => $this->_args[ 'description' ],
            'readonly' => $readonly,
            'id'       => $this->_args[ 'id' ],
            'value'    => $this->_args[ 'value' ],
            'format'   => $this->_get_format(),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('theme-options/color-picker.twig', $args, true)
                          ->get_content();
    }

    protected function _get_format()
    {
        $format = 'hex';
        if (str_starts_with($this->_args[ 'value' ], 'rgb')) {
            if (str_starts_with($this->_args[ 'value' ], 'rgba')) {
                $format = 'rgba';
            } else {
                $format = 'rgb';
            }
        }

        return $format;
    }
}
