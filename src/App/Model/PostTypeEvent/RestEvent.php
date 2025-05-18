<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Model\TaxonomyAdapter;
use Osec\App\View\Calendar\ViewRuntimePropsTrait;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Event preprocessing for REST-based events.
 *
 * Goal: is to preoprcess Event into a dataset for Rest/React based BigCalendar.
 * Up to now data processing is done in src/App/View/Calendar/AbstractView and View classes.
 * But lot of this is HTML snips.
 * For Rest we want plain data for each event.
 * All Timezone handling is done in the frontend so wemust provide timezonless unix timestamps.
 *
 * @since      1.2
 * @author     digitaldonkey
 * @package PostTypeEvent
 */
class RestEvent extends OsecBaseClass
{
    use ViewRuntimePropsTrait;

    /**
     * @param  Event[]  $events
     *
     * @return array
     */
    public function processEvents(array $events): array
    {
        $post_ids = [];
        foreach ($events as $event) {
            $post_ids[] = (int)$event->get('post_id');
        }
        update_meta_cache('post', $post_ids);
        TaxonomyAdapter::factory($this->app)->update_meta($post_ids);
        return array_map([$this, 'processEvent'], $events);
    }

    public function processEvent(Event $event): object
    {
        self::addRuntimePropertiesStatic($this->app, $event);
        return (object) [
            'title' => $event->get_runtime('filtered_title'),
            'start' => $event->get('start')->format_to_gmt(),
            'end' => $event->get('end')->format_to_gmt(),
            'allDay' => $event->is_allday(),
            'resource' => 'any',
        ];
    }
}
