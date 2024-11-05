<?php

namespace Osec\Settings;

use Exception;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;

/**
 * Missing class setting-renderer description.
 *
 * @since      2.2
 * @author     Time.ly Network Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Renderer
 */
class SettingsRenderer extends OsecBaseClass
{

    /**
     * Renders single setting.
     *
     * @param  array  $setting  Setting structure.
     *
     * @return string Rendered content.
     *
     * @throws BootstrapException
     */
    public function render(array $setting)
    {
        $namespacedClass = $setting[ 'renderer' ][ 'class' ];
        if ( ! class_exists($namespacedClass)) {
            throw new Exception($namespacedClass.' does not exist');
        }
        $renderer = new $namespacedClass($this->app, $setting);

        return $renderer->render();
    }
}
