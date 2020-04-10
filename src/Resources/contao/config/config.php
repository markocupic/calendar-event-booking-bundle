<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

// Add child table tl_calendar_events_member to tl_calendar_events
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_events_member';

/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['events']['eventbooking'] = 'Markocupic\CalendarEventBookingBundle\ModuleEventBooking';
$GLOBALS['FE_MOD']['events']['unsubscribefromevent'] = 'Markocupic\CalendarEventBookingBundle\ModuleUnsubscribeFromEvent';
// Refactored modules
//$GLOBALS['FE_MOD']['events']['calendar_event_booking_member_list'] = 'Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingMemberListController';

if (TL_MODE == 'BE')
{
    // Add Backend CSS
    $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
}

// Form HOOKS (event booking)
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

// Register $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']
$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('Markocupic\CalendarEventBookingBundle\InitializeSystemHook', 'registerHooks');

// On update (keep running older settings)
$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('Markocupic\CalendarEventBookingBundle\InitializeSystemHook', 'onUpdate');

// Auto generate booking form
$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('Markocupic\CalendarEventBookingBundle\InitializeSystemHook', 'autoGenerateBookingForm');

// Notification center
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['calendar-event-booking-bundle'] = array
(
    // Type
    'booking-notification'           => array
    (
        // Field in tl_nc_language
        'email_sender_name'    => array('organizer_senderName'),
        'email_sender_address' => array('organizer_senderEmail'),
        'recipients'           => array('organizer_senderEmail', 'member_email'),
        'email_recipient_cc'   => array('organizer_senderEmail', 'member_email'),
        'email_recipient_bcc'  => array('organizer_senderEmail', 'member_email'),
        'email_replyTo'        => array('organizer_senderEmail'),
        'email_subject'        => array('event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'),
        'email_text'           => array('event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'),
        'email_html'           => array('event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'),
    ),
    // Type
    'event-unsubscribe-notification' => array
    (
        // Field in tl_nc_language
        'email_sender_name'    => array('organizer_senderName'),
        'email_sender_address' => array('organizer_senderEmail'),
        'recipients'           => array('organizer_senderEmail', 'member_email'),
        'email_recipient_cc'   => array('organizer_senderEmail', 'member_email'),
        'email_recipient_bcc'  => array('organizer_senderEmail', 'member_email'),
        'email_replyTo'        => array('organizer_senderEmail'),
        'email_subject'        => array('event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'),
        'email_text'           => array('event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'),
        'email_html'           => array('event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'),
    ),
);
