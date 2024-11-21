<?php

namespace Osec\Settings\Elements;

use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page textarea option.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Textarea
 */
class SettingsTextarea extends SettingsAbstract
{
    public const DEFAULT_ROWS = 6;


    public function render($html = '', $wrap = true): string
    {
        // $type  = $this->args['renderer']['type'];
        // $date  = $append = false;

        // Set attributes
        $input_args = [];

        // Set textarea rows
        if ( ! empty($this->args['renderer']['rows'])) {
            $input_args['rows'] = $this->args['renderer']['rows'];
        }

        // Set textarea disabled
        if ( ! empty($this->args['renderer']['disabled'])) {
            $input_args['disabled'] = $this->args['renderer']['disabled'];
        }

        // Set textarea readonly
        if ( ! empty($this->args['renderer']['readonly'])) {
            $input_args['readonly'] = $this->args['renderer']['readonly'];
        }

        $args = [
            'id'         => $this->args['id'],
            'label'      => $this->args['renderer']['label'],
            'input_args' => $input_args,
            'value'      => $this->args['value'],
        ];
        // if ( true === $append ) {
        // $args['append'] = $this->args['renderer']['append'];
        // }
        if (isset($this->args['renderer']['help'])) {
            $args['help'] = $this->args['renderer']['help'];
        }
        $html .= ThemeLoader::factory($this->app)
                           ->get_file('setting/textarea.twig', $args, true)
                           ->get_content();

        return $this->warp_in_form_group($html);
    }
}
