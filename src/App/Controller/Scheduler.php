<?php

namespace Osec\App\Controller;

use Osec\App\Model\SchedduleFrequencyHelper;
use Osec\App\Model\Settings;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;

/**
 * Events scheduling utility
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Scheduling_Utility
 * @author     Time.ly Network Inc.
 */
class Scheduler extends OsecBaseClass
{
    /**
     * @constant string Name of option
     */
    public const OPTION_NAME = 'osec_scheduler_hooks';

    /**
     * @var array Map of hooks currently registered
     */
    protected array $configuration = [];

    private bool $isUpdated = false;

    /**
     * Constructor
     *
     * Read configured hooks and frequencies from database
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $defaults = [
            'hooks'   => [],
            'freqs'   => [],
            'version' => '1.11',
        ];

        $this->configuration = $this->app->options->get(
            self::OPTION_NAME,
            $defaults
        );

        $this->configuration = array_merge($defaults, $this->configuration);
        $this->install_default_schedules();
        ShutdownController::factory($this->app)->register(
            $this->shutdown(...)
        );
        add_filter(
            'ai1ec_settings_initiated',
            $this->settings_initiated_hook(...)
        );
    }

    /**
     * Install default schedules
     *
     * @return Scheduler Instance of self for chaining
     */
    public function install_default_schedules()
    {
        $hook_list = $this->get_default_schedules();
        foreach ($hook_list as $hook => $freq) {
            $details = $this->get_details($hook);
            if (
                null === $details ||
                $this->shouldDefaultsBeOverwritten($hook, $details)
            ) {
                $this->schedule($hook, $freq);
            }
        }

        return $this;
    }

    /**
     * Get map of default schedules
     *
     * @return array Map of hooks and their default schedules
     */
    public function get_default_schedules()
    {
        return ['osec_purge_events_cache' => '3h'];
    }

    // TODO
    // Remove. Didn't find any implementations
    // **
    // * Run designated hook in background thread
    // *
    // * So far it is just re-scheduling the hook to be run at earliest
    // * time possible.
    // *
    // * @param string $hook Name of registered schedulable hook
    // *
    // * @return void Method does not return
    // */
    // public function background( $hook ) {
    // return $this->installScheduleEvents( $hook, time() );
    // }

    /**
     * Retrieve information about scheduled hook
     *
     * @param  string  $hook  Name of hook to extract
     *
     * @return array|null Hook schedule details, or NULL if none is installed
     */
    public function get_details($hook)
    {
        $existing = $this->getRegisteredHooks();
        if ( ! isset($existing[$hook])) {
            return null;
        }

        return $existing[$hook];
    }

    /**
     * Return a list of hooks already registered
     *
     * Convenient method to return a list of registered hooks
     *
     * @return array Map of hooks, mapped on hook name
     */
    protected function getRegisteredHooks()
    {
        return $this->configuration['hooks'];
    }

    /**
     * In some cases we need to override existing values
     *
     * TODO I guess this is always true
     *
     * @param  string  $hook  Name of hook being checked
     * @param  array  $current  Hook details
     *
     * @return bool True if hook needs to be re-installed
     */
    protected function shouldDefaultsBeOverwritten($hook, array $current): bool
    {
        if (
            'osec_purge_events_cache' === $hook &&
            '5m' === $current['freq'] &&
            version_compare('1.11', $this->configuration['version']) >= 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Schedule hook run times
     *
     * @param  string  $hook  Name of hook to execute
     * @param  string  $freq  Frequency of runs
     * @param  int  $first  UNIX timestamp of first execution
     * @param  string  $version  Arbitrary cron version identifier [optional=0]
     *
     * @return bool Success
     */
    public function schedule($hook, $freq, $first = 0, $version = '0'): bool
    {
        $first = (int)$first;
        if (0 === $first) {
            $first = time();
        }

        return $this->installScheduleEvents($hook, $first, $freq, $version);
    }

    /**
     * Actually install/update hook
     *
     * @param  string  $hook  Name of hook to execute
     * @param  int  $timestamp  Time of first run
     * @param  string  $freq  User defined recurrence pattern [optional=NULL]
     * @param  string  $version  Arbitrary cron version identifier [optional=0]
     *
     * @return bool Success
     */
    protected function installScheduleEvents(
        $hook,
        $timestamp,
        $freq = null,
        $version = '0'
    ) {
        $installable = compact('hook', 'timestamp', 'version');
        if (null !== $freq) {
            $parsed_freq               = $this->get_valid_freq_details(
                $hook,
                $freq
            );
            $installable['recurrence'] = $this->get_named_frequency(
                $parsed_freq,
                $freq
            );
            $installable['freq']       = $parsed_freq->to_string();
            unset($parsed_freq);
        }
        if ( ! $this->mergeHook($hook, $installable)) {
            return false;
        }
        wp_clear_scheduled_hook($installable['hook']);

        return wp_schedule_event(
            $installable['timestamp'],
            $installable['recurrence'],
            $installable['hook']
        );
    }

    /**
     * Parse frequency to a details map
     *
     * @param  string  $hook  Name of hook to be installed
     * @param  string  $input  User supplied frequency
     *
     * @return SchedduleFrequencyHelper Valid parsed frequency object
     */
    public function get_valid_freq_details($hook, $input): SchedduleFrequencyHelper
    {
        $freq = $this->parseFreq($input);
        if (0 === $freq->to_seconds()) { // input was empty/parseable to empty
            $defaults = $this->get_default_schedules();
            if (isset($defaults[$hook])) {
                $freq = $this->parseFreq($defaults[$hook]);
            }
        }

        return $freq;
    }

    /**
     * Parse arbitrary frequency representation to one accepted by WP scheduler
     *
     * First check is made against available schedules map, to check whereas
     * frequency given matches some defined name.
     * If that fails - treats input as human readable offset between consequent
     * event runs. It might be either number of seconds, or a digit followed by
     * an abbreviation, one of: `s` for seconds (equal to no abbr. passed), `m`
     *  for minutes, `h` for hours, `d` fordays, `w` for weeks. I.e. '20m' will
     * be parsed to `1200` seconds.
     *
     * @param  string  $freq  Parseable frequency identifier
     *
     * @return SchedduleFrequencyHelper Parsed frequency object
     */
    protected function parseFreq($freq): SchedduleFrequencyHelper
    {
        $parsed = new SchedduleFrequencyHelper();
        if (false === $parsed->parse($freq)) {
            $parsed->parse('0');
        }

        return $parsed;
    }

    /**
     * Get named scheduler frequency
     *
     * As `wp_schedule_event` accepts only named frequencies this method ensures
     * that our custom frequencies are installed and available, generating alias
     * to be used for event scheduling.
     *
     * @param  SchedduleFrequencyHelper  $seconds  Number of seconds between
     *                                        sequential events
     * @param  string  $name  A schedule name used
     *                                                          by {@see wp_get_schedules}
     *
     * @return string Name to use when adding event to scheduler
     */
    public function get_named_frequency(
        SchedduleFrequencyHelper $seconds,
        $name = null
    ) {
        if (null !== $name) {
            $wpschedules = wp_get_schedules();
            if (isset($wpschedules[$name])) {
                return $name;
            }
            unset($wpschedules);
        }
        $seconds = $seconds->to_seconds();
        $current = $this->frequencyList();
        if ( ! isset($current[$seconds])) {
            $current[$seconds] = [
                'hash'    => 'every_' . $seconds,
                'name'    => $name,
                'seconds' => $seconds,
            ];
            $this->setFrequencyList($current);
        }

        return $current[$seconds]['hash'];
    }

    /**
     * Return a list of frequencies already registered
     *
     * Convenient method to return a list of registered frequencies
     *
     * @return array Map of frequencies, mapped on offset seconds
     */
    protected function frequencyList()
    {
        return $this->configuration['freqs'];
    }

    /**
     * Update a list of frequencies registered
     *
     * Update in-memory list of frequencies and mark status for writing to
     * database
     *
     * @param  array  $freqs
     *
     * @return bool Success
     */
    protected function setFrequencyList(array $freqs)
    {
        $this->configuration['freqs'] = $freqs;
        $this->isUpdated              = true;

        return true;
    }

    /**
     * Convenient method to perform hook description update
     *
     * @param  string  $hook  Name of hook to update
     * @param  array  $installable  Object to merge into memory
     *
     * @return bool Success
     */
    protected function mergeHook($hook, array $installable)
    {
        $existing = $this->getRegisteredHooks();
        if (isset($existing[$hook])) {
            $installable = array_merge($existing[$hook], $installable);
        }
        $existing[$hook] = $installable;

        return $this->setHooks($existing);
    }

    /**
     * Update a list of hooks registered
     *
     * Update in-memory list of hooks and mark status for writing to database
     *
     * @param  array  $hooks  Map of hooks mapped on hook name
     *
     * @return bool Success
     */
    protected function setHooks(array $hooks)
    {
        $this->configuration['hooks'] = $hooks;
        $this->isUpdated              = true;

        return true;
    }

    /**
     * Change hook scheduling
     *
     * Only make changes, if given schedule is not installed or frequency
     * defined differs from given in argument. For more details on action
     * {@see self::schedule()} which is called if conditions are met.
     *
     * @param  string  $hook  Name of hook to reschedule
     * @param  string  $freq  Frequency of runs
     * @param  string  $version  Arbitrary cron version identifier [optional=0]
     *
     * @return bool Success
     */
    public function reschedule($hook, $freq, $version = '0')
    {
        $freq       = trim($freq);
        $existing   = $this->get_details($hook);
        $reschedule = false;
        if (null === $existing) {
            $reschedule = true;
        } else {
            // unify frequencies to avoid unnecessary rescheduling
            $curr_freq = $this->parseFreq($existing['freq'])->to_string();
            $new_freq  = $this->parseFreq($freq)->to_string();
            if (
                0 !== strcmp($curr_freq, $new_freq) ||
                ! isset($existing['version']) ||
                (string)$existing['version'] !== (string)$version
            ) {
                $reschedule = true;
            }
            unset($curr_freq, $new_freq);
        }
        if ($reschedule) {
            return $this->schedule($hook, $freq, 0, $version);
        }

        return true;
    }

    /**
     * Update CRON schedules map with our custom timings
     *
     * Callback to `cron_schedules` action
     *
     * @param  array  $wp_map  Currently installed schedules map
     *
     * @return array Modified schedules map
     */
    public function cron_schedules(array $wp_map)
    {
        $freqs = $this->frequencyList();
        foreach ($freqs as $entry) {
            $wp_map[$entry['hash']] = [
                'interval' => $entry['seconds'],
                'display'  => $entry['name'],
            ];
        }

        return $wp_map;
    }

    /**
     * Shutdown sequence
     *
     * Write settings to database on destruct if changes were introduced
     *
     * @return void No returns are processed in shutdown sequence
     */
    public function shutdown()
    {
        if ($this->isUpdated) {
            $this->compactFrequencies();
            $this->configuration['version'] = OSEC_VERSION;
            update_option(self::OPTION_NAME, $this->configuration);
        }
    }

    /**
     * Remove frequencies, that are no longer associated to any of the hooks
     */
    protected function compactFrequencies()
    {
        $hook_list = $this->getRegisteredHooks();
        $this->setFrequencyList([]);
        foreach ($hook_list as $hook) {
            $this->get_named_frequency(
                $this->parseFreq($hook['freq'])
            );
        }

        return $this;
    }

    /**
     * Clear previously set schedules and delete options entry
     *
     * This is a callback method, to be executed upon un-install to ensure
     * that previously scheduled hooks are deleted and option storing list
     * is removed from options table.
     *
     * @param  bool  $purge
     *
     * @return void Success
     */
    public function uninstall(bool $purge = false): void
    {
        $cron_list = $this->getRegisteredHooks();
        foreach ($cron_list as $cron) {
            wp_clear_scheduled_hook($cron['hook']);
        }
    }

    /**
     * Delete hook from execution queue
     *
     * @param  string  $hook  Name of hook to delete
     *
     * @return bool Success
     */
    public function delete($hook)
    {
        $existing = $this->getRegisteredHooks();
        $success  = wp_clear_scheduled_hook($hook);
        if (isset($existing[$hook])) {
            unset($existing[$hook]);
            $this->setHooks($existing);
        }

        return $success;
    }

    /**
     * Modify values in settings object from hooks details
     *
     * @param  Settings  $settings
     *
     * @return Settings
     *   Modified settings model reference
     */
    public function settings_initiated_hook(Settings $settings): Settings
    {
        if (property_exists($settings, 'view_cache_refresh_interval')) {
            $cache_schedule                        = $this->get_details('osec_purge_events_cache');
            $settings->view_cache_refresh_interval = $cache_schedule['freq'];
        }

        return $settings;
    }
}
