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
                'timely'    => I18n::__('Add to Timely Calendar'),
                'google'    => I18n::__('Add to Google'),
                'outlook'   => I18n::__('Add to Outlook'),
                'apple'     => I18n::__('Add to Apple Calendar'),
                'plaintext' => I18n::__('Add to other calendar'),
            ],
            'title' => [
                'timely'    => I18n::__(
                    'Copy this URL for your own Timely calendar or click to add to your rich-text calendar'
                ),
                'google'    => I18n::__('Subscribe to this calendar in your Google Calendar'),
                'outlook'   => I18n::__('Subscribe to this calendar in MS Outlook'),
                'apple'     => I18n::__('Subscribe to this calendar in Apple Calendar/iCal'),
                'plaintext' => I18n::__('Subscribe to this calendar in another plain-text calendar'),
            ],
        ];
    }
}
