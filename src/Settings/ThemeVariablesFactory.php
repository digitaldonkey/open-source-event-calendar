<?php

namespace Osec\Settings;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;
use Osec\Settings\Elements\SettingsAbstract;

/**
 * Abstract class for less variables.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Settings
 * @replaces Ai1ec_Less_Variable
 */
class ThemeVariablesFactory extends OsecBaseClass
{

    public const TYPE_MAP = [
        'color' => 'Osec\Settings\Elements\ThemeVariableColor',
        'font'  => 'Osec\Settings\Elements\ThemeVariableFont',
        'size'  => 'Osec\Settings\Elements\ThemeVariableSize',
    ];

    /**
     *
     *
     * @param  array  $args
     *
     * @return SettingsAbstract
     * @throws Exception
     */
    public function createRenderer(array $args) : SettingsAbstract
    {
        $this->assertReuiredParams($args);
        $class = self::TYPE_MAP[ $args[ 'type' ] ];

        return new $class($this->app, $args);
    }


    protected function assertReuiredParams($args) : void
    {
        if ( ! isset($args[ 'id' ])) {
            throw new Exception('Missing a required argument `id`');
        }
        if ( ! isset($args[ 'description' ])) {
            throw new Exception('Missing a required argument `description`');
        }
        if ( ! isset($args[ 'value' ])) {
            throw new Exception('Missing a required argument `value`');
        }
        if ( ! isset($args[ 'type' ])) {
            throw new Exception('Missing a required argument `type`');
        }
    }

}
