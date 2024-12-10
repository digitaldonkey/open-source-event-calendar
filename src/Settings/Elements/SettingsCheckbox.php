<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page checkbox option.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Settings_Checkbox
 */
class SettingsCheckbox extends SettingsAbstract
{
    public function render($html = '', $wrap = true): string
    {
        $attributes = ['class' => 'checkbox'];
        if (true === $this->args['value']) {
            $attributes['checked'] = 'checked';
        }
        $args               = $this->args;
        $args['attributes'] = $attributes;
        $file               = ThemeLoader::factory($this->app)->get_file(
            'setting/checkbox.twig',
            $args,
            true
        );

        return $this->warp_in_form_group($file->get_content());
    }
}
