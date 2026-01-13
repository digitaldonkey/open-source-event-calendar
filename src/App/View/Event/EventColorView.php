<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\TaxonomyAdapter;
use Osec\Bootstrap\OsecBaseClass;

/**
 * This class renders the html for the event colors.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Color
 */
class EventColorView extends OsecBaseClass
{
    public function get_faded_color(Event $event)
    {
        return $this->getColor($event, 'faded');
    }

    /**
     * Faded version of event category color
     */
    protected function getColor(Event $event, $type)
    {
        static $categories_cache = [
            'hex'  => [],
            'rgba'  => [],
            'faded' => [],
        ];
        $methods    = [
            'hex' => 'get_event_category_hex_color',
            'rgba'  => 'get_event_category_rgba_color',
            'faded' => 'get_event_category_faded_color',
        ];
        $categories = TaxonomyAdapter::factory($this->app)
                                     ->get_post_categories($event->get('post_id'));

        if ( ! empty($categories)) {
            if ( ! isset($categories_cache[$type][$categories[0]->term_id])) {
                $method = $methods[$type];
                $categories_cache[$type][$categories[0]->term_id] = $this->$method($categories[0]->term_id);
            }

            return $categories_cache[$type][$categories[0]->term_id];
        }

        return '';
    }

    /**
     * rgba() format of faded category color.
     *
     * @return  string
     */
    public function get_rgba_color(Event $event)
    {
        return $this->getColor($event, 'rgba');
    }

    /**
     * Returns a faded version of the event's category color in hex format.
     *
     * @param  int  $term_id  The Event Category's term ID
     *
     * @return string
     */
    public function get_event_category_faded_color($term_id)
    {
        $taxonomy = TaxonomyAdapter::factory($this->app);
        $color    = $taxonomy->get_category_color($term_id);
        if ( ! is_null($color) && ! empty($color)) {
            $color1 = substr((string)$color, 1);
            $color2 = 'ffffff';

            $c1_p1 = hexdec(substr($color1, 0, 2));
            $c1_p2 = hexdec(substr($color1, 2, 2));
            $c1_p3 = hexdec(substr($color1, 4, 2));

            $c2_p1 = hexdec(substr($color2, 0, 2));
            $c2_p2 = hexdec(substr($color2, 2, 2));
            $c2_p3 = hexdec(substr($color2, 4, 2));

            $m_p1 = dechex(round($c1_p1 * 0.5 + $c2_p1 * 0.5));
            $m_p2 = dechex(round($c1_p2 * 0.5 + $c2_p2 * 0.5));
            $m_p3 = dechex(round($c1_p3 * 0.5 + $c2_p3 * 0.5));

            return '#' . $m_p1 . $m_p2 . $m_p3;
        }

        return '';
    }

    /**
     * Returns the rgba() format of the event's category color, with '%s' in place
     * of the opacity (to be substituted by sprintf).
     *
     * @param  int  $term_id  The Event Category's term ID
     *
     * @return string
     */
    public function get_event_category_rgba_color($term_id)
    {
        $taxonomy = TaxonomyAdapter::factory($this->app);
        $color    = $taxonomy->get_category_color($term_id);
        if ( ! is_null($color) && ! empty($color)) {
            $p1 = hexdec(substr((string)$color, 1, 2));
            $p2 = hexdec(substr((string)$color, 3, 2));
            $p3 = hexdec(substr((string)$color, 5, 2));

            return "rgba($p1, $p2, $p3, %s)";
        }

        return '';
    }

    /**
     * Returns the rgba() format of the event's category color, with '%s' in place
     * of the opacity (to be substituted by sprintf).
     *
     * @param  int  $term_id  The Event Category's term ID
     *
     * @return string
     */
    public function get_event_category_hex_color($term_id)
    {
        $taxonomy = TaxonomyAdapter::factory($this->app);
        $color    = $taxonomy->get_category_color($term_id);
        if ( ! is_null($color) && ! empty($color)) {
            return $color;
        }
        return '';
    }
}
