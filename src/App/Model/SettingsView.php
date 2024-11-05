<?php

namespace Osec\App\Model;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\SettingsException;


/**
 * Settings extension for managing view-related settings.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Settings_View
 */
class SettingsView extends OsecBaseClass
{

    /**
     * @var string Name of settings option to use for views map.
     */
    public const SETTING_VIEWS_MAP = 'enabled_views';

    /**
     * Add a view if not set.
     */
    public function add(array $view)
    {
        $enabled_views = $this->_get();
        if (isset($enabled_views[ $view[ 'name' ] ])) {
            if ($enabled_views[ $view[ 'name' ] ][ 'longname' ] === $view[ 'longname' ]) {
                return;
            }
            $enabled_views[ $view[ 'name' ] ][ 'longname' ] = $view[ 'longname' ];
        } else {
            // Copy relevant settings to local view array; account for possible missing
            // mobile settings during upgrade (assign defaults).
            $enabled_views[ $view[ 'name' ] ] = [
                'enabled'        => $view[ 'enabled' ],
                'default'        => $view[ 'default' ],
                'enabled_mobile' => $view[ 'enabled_mobile' ] ?? $view[ 'enabled' ],
                'default_mobile' => $view[ 'default_mobile' ] ?? $view[ 'default' ],
                'longname'       => $view[ 'longname' ]
            ];
        }
        $this->setEnabledViews($enabled_views);
    }

    /**
     * Retrieve views maps from storage.
     *
     * @return array Current views map.
     */
    protected function getViewsEnabledViews()
    {
        return (array) $this->app->settings->get(self::SETTING_VIEWS_MAP, []);
    }

    /**
     * Update views map.
     *
     * @param  array  $enabled_views  Map of enabled views.
     *
     * @return Settings Success.
     */
    private function setEnabledViews(array $enabled_views)
    {
        return $this->app->settings->set(self::SETTING_VIEWS_MAP, $enabled_views);
    }

    /**
     * Remove a view.
     *
     * @param  string  $view
     */
    public function remove($view)
    {
        $enabled_views = $this->getViewsEnabledViews();
        if (isset($enabled_views[ $view ])) {
            unset($enabled_views[ $view ]);
            $this->setEnabledViews($enabled_views);
        }
    }

    /**
     * Retrieve all configured views.
     *
     * @return array Map of configured view aliases and their details.
     */
    public function get_all()
    {
        return $this->getViewsEnabledViews();
    }

    /**
     * Get name of view to be rendered for requested alias.
     *
     * @param  string  $view  Name of view requested.
     *
     * @return string Name of view to be rendered.
     *
     * @throws SettingsException If no views are configured.
     */
    public function get_configured($view)
    {
        $enabled_views = $this->getViewsEnabledViews();
        if (empty($enabled_views)) {
            throw new SettingsException('No view is enabled');
        }
        if (
            isset($enabled_views[ $view ]) &&
            $enabled_views[ $view ][ 'enabled' ]
        ) {
            return $view;
        }

        return $this->get_default();
    }

    /**
     * Get default view to render.
     *
     *
     * @return int|string|null
     */
    public function get_default()
    {
        $enabled_views = $this->getViewsEnabledViews();
        $default = null;
        // Check mobile settings first, if in mobile mode. May not exist in cli/phpunit
        if (function_exists('wp_is_mobile') && wp_is_mobile()) {
            foreach ($enabled_views as $view => $details) {
                if (
                    isset($details[ 'default_mobile' ]) &&
                    $details[ 'default_mobile' ] &&
                    $details[ 'enabled_mobile' ]
                ) {
                    $default = $view;
                    break;
                }
            }
        }
        // Either not in mobile mode or no mobile settings available; look up
        // desktop settings.
        if (null === $default) {
            foreach ($enabled_views as $view => $details) {
                if ($details[ 'default' ] && $details[ 'enabled' ]) {
                    $default = $view;
                    break;
                }
            }
        }
        // No enabled view found, but we need to pick one, so pick the first view.
        if (null === $default) {
            $default = (string) current(array_keys($enabled_views));
        }

        return $default;
    }

}
