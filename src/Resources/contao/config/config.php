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

// Form HOOKS (f.ex. Kursanmeldung)
$GLOBALS['TL_HOOKS']['postUpload'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'postUpload');
$GLOBALS['TL_HOOKS']['compileFormFields'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'compileFormFields');
$GLOBALS['TL_HOOKS']['loadFormField'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'loadFormField');
$GLOBALS['TL_HOOKS']['validateFormField'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'validateFormField');
$GLOBALS['TL_HOOKS']['storeFormData'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'storeFormData');
$GLOBALS['TL_HOOKS']['prepareFormData'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'prepareFormData');
$GLOBALS['TL_HOOKS']['processFormData'][] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'processFormData');

