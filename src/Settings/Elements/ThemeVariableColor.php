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
     * @var bool
     */
    protected $readonly = false;

    public function render($html = '', $wrap = true): string
    {
        $readonly = $this->readonly === true ? 'readonly' : '';

        $args = [
            'label'    => $this->args['description'],
            'readonly' => $readonly,
            'id'       => $this->args['id'],
            'value'    => $this->args['value'],
            'format'   => $this->getFormat(),
            'css_var'  => $this->args['css_var'] ?? '',
            'default'  => $this->args['default'] ?? '',
            'source'   => $this->args['source'] ?? '',
            'default_label'     => __('Default', 'open-source-event-calendar'),
            'use_default_label' => __('Use default', 'open-source-event-calendar'),
        ];

        // A css_var setting holds a colour or a var() reference; the bootstrap colour picker
        // would overwrite a reference with its own parse result, so it gets its own field.
        $template = empty($this->args['css_var'])
            ? 'theme-options/color-picker.twig'
            : 'theme-options/color-css-var.twig';

        return ThemeLoader::factory($this->app)
                          ->get_file($template, $args, true)
                          ->get_content();
    }

    protected function getFormat()
    {
        $format = 'hex';
        if (str_starts_with($this->args['value'], 'rgb')) {
            if (str_starts_with($this->args['value'], 'rgba')) {
                $format = 'rgba';
            } else {
                $format = 'rgb';
            }
        }

        return $format;
    }
}
