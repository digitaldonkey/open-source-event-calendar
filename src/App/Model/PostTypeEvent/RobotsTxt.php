<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\OsecBaseClass;

/**
 * File robots.txt helper.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Robots_Helper
 */
class RobotsTxt extends OsecBaseClass
{
    /**
     * Install robotx.txt into current WordPress instance
     *
     * @return void
     */
    public function install(): void
    {
        // Update settings textarea
        $this->app->settings->set('edit_robots_txt', $this->rules('', false));
    }

    /**
     * Get default robots rules for the calendar
     *
     * @param  string  $is_public  Public flag
     * @param  string  $output  Current robots rules
     *
     * @return string
     */
    public function rules(string $output, bool $is_public): string
    {
        // Current rules
        $current_rules = array_map(
            'trim',
            explode(PHP_EOL, $output)
        );

        // Get calendar page URI
        $calendar_page_id = $this->app->settings->get('calendar_page_id');
        $page_base        = get_page_uri($calendar_page_id);

        // Custom rules
        $custom_rules = [];
        if ($page_base) {
            $custom_rules += [
                'User-agent: *',
                "Disallow: /$page_base/action~agenda/",
                "Disallow: /$page_base/action~oneday/",
                "Disallow: /$page_base/action~month/",
                "Disallow: /$page_base/action~week/",
            ];
        }

        $robots = array_merge($current_rules, $custom_rules);
        $robots = implode(
            PHP_EOL,
            array_filter(array_unique($robots))
        );

        return $robots;
    }
}
