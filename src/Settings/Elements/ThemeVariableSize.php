<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * This class represent a LESS variable of type size.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Settings
 * @replaces Ai1ec_Less_Variable_Size
 */
class ThemeVariableSize extends SettingsAbstract
{

    public function render($html = '', $wrap = false) : string
    {
        $args = [
            'label' => $this->_args[ 'description' ],
            'id'    => $this->_args[ 'id' ],
            'value' => $this->_args[ 'value' ],
            'args'  => [
                'class'       => 'input-mini ai1ec-less-variable-size',
                'placeholder' => __('Length', OSEC_TXT_DOM),
            ],
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('theme-options/size.twig', $args, true)
                          ->get_content();
    }
}
