<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page Enabled views selection snippet.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Element_Enabled_Views
 */
class SettingsEnabledViews extends SettingsAbstract
{
    public function render($html = '', $wrap = true): string
    {
        $this->convertValues();
        $args = [
            'views'        => $this->args['value'],
            'label'        => $this->args['renderer']['label'],
            'text_enabled' => __('Enabled', 'open-source-event-calendar'),
            'text_default' => __('Default', 'open-source-event-calendar'),
            'text_desktop' => __('Desktop', 'open-source-event-calendar'),
            'text_mobile'  => __('Mobile', 'open-source-event-calendar'),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('setting/enabled-views.twig', $args, true)
                          ->get_content();
    }

    /**
     * Convert values to bo used in rendering
     */
    protected function convertValues()
    {
        foreach ($this->args['value'] as &$view) {
            $view['enabled'] = $view['enabled'] ?
                'checked="checked"' :
                '';
            $view['default'] = $view['default'] ?
                'checked="checked"' :
                '';
            // Use mobile settings if available, else fall back to desktop settings.
            $view['enabled_mobile'] = isset($view['enabled_mobile']) ?
                ($view['enabled_mobile'] ?
                    'checked="checked"' :
                    '') :
                $view['enabled'];
            $view['default_mobile'] = isset($view['default_mobile']) ?
                ($view['default_mobile'] ?
                    'checked="checked"' :
                    '') :
                $view['default'];
            $view['longname']       = translate_nooped_plural(
                $view['longname'],
                1
            );
        }
    }
}
