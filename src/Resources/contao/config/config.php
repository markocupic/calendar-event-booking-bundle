<?php

// Add child table tl_calendar_events_member to tl_calendar_events
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_events_member';

/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['events']['eventbooking'] = 'Markocupic\CalendarEventBookingBundle\ModuleEventBooking';

if (TL_MODE == 'BE') {
    // Add Backend CSS
    $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
}
