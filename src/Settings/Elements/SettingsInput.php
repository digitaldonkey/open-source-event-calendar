<?php

namespace Osec\Settings\Elements;

use Osec\App\Model\Date\UIDateFormats;
use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page input option.
 *
 * @since  2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Input
 */
class SettingsInput extends SettingsAbstract
{


    public function render($html = '', $wrap = true) : string
    {
        $type = $this->_args[ 'renderer' ][ 'type' ];
        $date = $append = false;
        $class = '';
        $input_type = 'text';
        switch ($type) {
            case 'date':
                $date = true;
                break;

            case 'append':
                $append = true;
                break;

            case 'email':
                $input_type = 'email';
                break;

            case 'url':
                $input_type = 'url';
                break;
        }

        $input_args = ['class' => $class];
        $settings = $this->app->settings;
        if (true === $date) {
            $input_args += [
                'data-date-weekstart' => $settings->get('week_start_day'),
                'data-date-format'    => UIDateFormats::factory($this->app)->get_date_pattern_by_key(
                    $settings->get('input_date_format')
                ),
                'size'                => 12,
            ];
        }
        $args = [
            'id'         => $this->_args[ 'id' ],
            'label'      => $this->_args[ 'renderer' ][ 'label' ],
            'input_args' => $input_args,
            'input_type' => $input_type,
            'value'      => $this->_args[ 'value' ],
        ];
        if (isset($this->_args[ 'renderer' ][ 'status' ])) {
            $args[ 'licence_valid' ] = $settings->get($this->_args[ 'renderer' ][ 'status' ]) === 'valid';
        }
        if (true === $append) {
            $args[ 'append' ] = $this->_args[ 'renderer' ][ 'append' ];
        }
        if (isset($this->_args[ 'renderer' ][ 'help' ])) {
            $args[ 'help' ] = $this->_args[ 'renderer' ][ 'help' ];
        }
        if (isset($this->_args[ 'renderer' ][ 'group-class' ])) {
            $args[ 'group_class' ] = $this->_args[ 'renderer' ][ 'group-class' ];
        }
        $html = ThemeLoader::factory($this->app)
                           ->get_file('setting/input.twig', $args, true)
                           ->get_content();

        return $this->warp_in_form_group($html);
    }

}
