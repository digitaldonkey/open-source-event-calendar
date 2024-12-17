<?php

namespace Osec\App\View\Calendar;

use Osec\App\I18n;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Generate translation entities for subscription buttons.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_View_Calendar_SubscribeButton
 */
class CalendarSubscribeButtonView extends OsecBaseClass
{
    /**
     * Get a list of texts for subscribtion buttons.
     *
     * @return array Map of labels.
     */
    public function get_labels()
    {
        return [
            'label' => [
                'timely'    => __('Add to Timely Calendar', 'open-source-event-calendar'),
                'google'    => __('Add to Google', 'open-source-event-calendar'),
                'outlook'   => __('Add to Outlook', 'open-source-event-calendar'),
                'apple'     => __('Add to Apple Calendar', 'open-source-event-calendar'),
                'plaintext' => __('Add to other calendar', 'open-source-event-calendar'),
            ],
            'title' => [
                'timely'    => I18n::__(
                    'Copy this URL for your own Timely calendar or click to add to your rich-text calendar'
                ),
                'google'    => __('Subscribe to this calendar in your Google Calendar', 'open-source-event-calendar'),
                'outlook'   => __('Subscribe to this calendar in MS Outlook', 'open-source-event-calendar'),
                'apple'     => __('Subscribe to this calendar in Apple Calendar/iCal', 'open-source-event-calendar'),
                'plaintext' => __(
                    'Subscribe to this calendar in another plain-text calendar',
                    'open-source-event-calendar'
                ),
            ],
        ];
    }
}
