<?php

namespace Markocupic\CalendarEventBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;

/**
 * @Callback(target="config.onload", table="tl_calendar_events_member")
 */
class ContentOnLoadCallbackListener
{
    public function __invoke(): void
    {
        // Add Backend CSS
        $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
    }
}
