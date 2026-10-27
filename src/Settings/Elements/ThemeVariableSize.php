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
    public function render($html = '', $wrap = false): string
    {
        $args = [
            'label' => $this->args['description'],
            'id'    => $this->args['id'],
            'value' => $this->args['value'],
            'args'  => [
                'class'       => '',
                // A size variable may name its own hint, e.g. a width (user_variables.php 'placeholder').
                'placeholder' => $this->args['placeholder'] ?? __('CSS font size', 'open-source-event-calendar'),
            ],
        ];

        // A css_var setting (plana) may hold var(--…, fallback): its own field, without the size
        // pattern check of the plain field.
        $template = 'theme-options/size.twig';
        if ( ! empty($this->args['css_var'])) {
            $template        = 'theme-options/size-css-var.twig';
            $args['css_var'] = $this->args['css_var'];
            $args['default'] = $this->args['default'] ?? '';
            $args['source']  = $this->args['source'] ?? '';
            $args['default_label']     = __('Default', 'open-source-event-calendar');
            $args['use_default_label'] = __('Use default', 'open-source-event-calendar');
        }

        return ThemeLoader::factory($this->app)
                          ->get_file($template, $args, true)
                          ->get_content();
    }
}
