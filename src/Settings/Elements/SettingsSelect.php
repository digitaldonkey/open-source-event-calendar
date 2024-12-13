<?php

namespace Osec\Settings\Elements;

use Osec\App\WpmlHelper;
use Osec\Exception\Exception;
use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page select option.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Select
 */
class SettingsSelect extends SettingsAbstract
{
    public function render($html = '', $wrap = true): string
    {
        $options = $this->args['renderer']['options'];

        if ( ! is_array($options)) {
            $callback = explode(':', (string)$options);
            if (str_starts_with($callback[0], 'Osec')) {
                if (strpos($options, '::') !== false) {
                    $options = $options();
                } else {
                    throw new Exception('Non static call not implemented yet.');
                }
            } elseif (method_exists($this, $options)) {
                $options = $this->{$options}();
            } else {
                throw new Exception('Callback must be namespaced within `Osec`.');
            }
        }

        /**
         * Alter Select options
         *
         * @since 1.0
         *
         * @param  string  $id  Select ID
         *
         * @param  array  $options  Select options
         */
        $options   = apply_filters('osec_settings_select_options', $options, $this->args['id']);
        $fieldsets = is_array($this->args['renderer']['fieldsets']) ? $this->args['renderer']['fieldsets'] : [];
        foreach ($options as $key => &$option) {
            // if the key is a string, it's an optgroup
            if (is_string($key)) {
                foreach ($option as &$opt) {
                    $opt = $this->_set_selected_value($opt);
                }
            } else {
                $option = $this->_set_selected_value($option);
                if (isset($option['settings'])) {
                    throw new Exception('This case must not exists anymore.');
                    // $fieldsets[] = $this->_render_fieldset(
                    // $option['settings'],
                    // $option['value'],
                    // $this->args['id'],
                    // isset($option['args']['selected'])
                    // );
                }
            }
        }
        $select_args = [];
        $args        = [
            'id'         => $this->args['id'],
            'label'      => $this->args['renderer']['label'],
            'attributes' => $select_args,
            'options'    => $options,
            'fieldsets'  => $fieldsets,
        ];
        $html .= ThemeLoader::factory($this->app)
                                  ->get_file('setting/select.twig', $args, true)
                                  ->get_content();

        return $this->warp_in_form_group($html);
    }

    /**
     * Toggle `selected` attribute according to current selection.
     *
     * @param  array  $option  Option being checked.
     *
     * @return array Optionally modified option entry.
     */
    protected function _set_selected_value(array $option)
    {
        if ($option['value'] === $this->args['value']) {
            $option['args'] = ['selected' => 'selected'];
        }

        return $option;
    }

    /**
     * Gets the options for the "Starting day of week" select.
     *
     * @return array
     */
    protected function get_weekdays_settings(): array
    {
        $options = [];
        for ($day_index = 0; $day_index <= 6; $day_index++) {
            $option    = [
                'text'  => WpmlHelper::factory($this->app)
                                     ->get_weekday($day_index),
                'value' => $day_index,
            ];
            $options[] = $option;
        }

        return $options;
    }
}
