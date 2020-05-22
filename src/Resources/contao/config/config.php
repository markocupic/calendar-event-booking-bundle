<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

// Add child table tl_calendar_events_member to tl_calendar_events
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_events_member';

if (TL_MODE === 'BE')
{
    // Add Backend CSS
    $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
}

// Register custom models
$GLOBALS['TL_MODELS']['tl_calendar_events_member'] = CalendarEventsMemberModel::class;

/**
 * Form HOOKS (event booking)
 * Hooks will be registered on the fly in the initializeSystem listener in
 * Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\InitializeSystem::registerCalendarEventBookingHooks().
 *
 * !!!!Override these globals, if you want to use custom form validation!!!
 */
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['compileFormFields'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\CompileFormFields', 'compileFormFields'];
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['loadFormField'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\LoadFormField', 'loadFormField'];
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['validateFormField'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateFormField', 'validateFormField'];
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['prepareFormData'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PrepareFormData', 'prepareFormData'];
$GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']['processFormData'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ProcessFormData', 'processFormData'];

// These hooks are registered via Resources/config/listener.yml
//$GLOBALS['TL_HOOKS']['initializeSystem'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\InitializeSystem', 'registerCalendarEventBookingHooks'];
//$GLOBALS['TL_HOOKS']['exportTable'] = ['Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ExportTable', 'exportBookingList'];


/**
 * Notification center
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['calendar-event-booking-bundle'] = [
    // Type
    'booking-notification'           => [
        // Field in tl_nc_language
        'email_sender_name'    => ['organizer_senderName'],
        'email_sender_address' => ['organizer_senderEmail'],
        'recipients'           => ['organizer_senderEmail', 'member_email'],
        'email_recipient_cc'   => ['organizer_senderEmail', 'member_email'],
        'email_recipient_bcc'  => ['organizer_senderEmail', 'member_email'],
        'email_replyTo'        => ['organizer_senderEmail'],
        'email_subject'        => ['event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'],
        'email_text'           => ['event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'],
        'email_html'           => ['event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'],
    ],
    // Type
    'event-unsubscribe-notification' => [
        // Field in tl_nc_language
        'email_sender_name'    => ['organizer_senderName'],
        'email_sender_address' => ['organizer_senderEmail'],
        'recipients'           => ['organizer_senderEmail', 'member_email'],
        'email_recipient_cc'   => ['organizer_senderEmail', 'member_email'],
        'email_recipient_bcc'  => ['organizer_senderEmail', 'member_email'],
        'email_replyTo'        => ['organizer_senderEmail'],
        'email_subject'        => ['event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'],
        'email_text'           => ['event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'],
        'email_html'           => ['event_*', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirthFormated', 'member_salutation', 'organizer_*', 'organizer_senderName', 'organizer_senderEmail'],
    ],
];
