<?php

/**
 * @copyright  Marko Cupic 2018
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */

// Add child table tl_calendar_events_member to tl_calendar_events
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_events_member';

/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['events']['eventbooking'] = 'Markocupic\CalendarEventBookingBundle\ModuleEventBooking';

if (TL_MODE == 'BE')
{
    // Add Backend CSS
    $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
}

// Form HOOKS (Kursanmeldung)
// Hooks will be registered in ModuleEventBooking::generate()
// Override these globals if you want to use custom form validation
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['postUpload'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'postUpload');
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['compileFormFields'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'compileFormFields');
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['loadFormField'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'loadFormField');
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['validateFormField'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'validateFormField');
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['storeFormData'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'storeFormData');
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['prepareFormData'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'prepareFormData');
// Send notification
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['processFormData'] = array('Markocupic\CalendarEventBookingBundle\ValidateForms', 'processFormData');


// Notification center
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['calendar-event-booking-bundle'] = array
(
    // Type
    'booking-notification' => array
    (
        // Field in tl_nc_language
        'email_sender_name'    => array('senderName', 'member_lastname', 'member_firstname'),
        'email_sender_address' => array('senderEmail', 'member_email'),
        'recipients'           => array('senderEmail', 'member_email'),
        'email_recipient_cc'   => array('senderEmail', 'member_email'),
        'email_recipient_bcc'  => array('senderEmail', 'member_email'),
        'email_replyTo'        => array('senderEmail'),
        'email_subject'        => array('event_*', 'member_*', 'senderName', 'senderEmail'),
        'email_text'           => array('event_*', 'member_*', 'senderName', 'senderEmail'),
        'email_html'           => array('event_*', 'member_*', 'senderName', 'senderEmail'),
    ),
);
