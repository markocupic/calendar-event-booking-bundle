<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

// Operations
$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'] = 'Show participants from event with ID %s';

// Legends
$GLOBALS['TL_LANG']['tl_calendar_events']['booking_options_legend'] = 'Booking settings';
$GLOBALS['TL_LANG']['tl_calendar_events']['notification_center_legend'] = 'Notification settings';
$GLOBALS['TL_LANG']['tl_calendar_events']['event_unsubscribe_legend'] = 'Event unsubscription settings';

// Fields
$GLOBALS['TL_LANG']['tl_calendar_events']['street'] = ['Street', 'Please add the street.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['postal'] = ['PLZ', 'Please add the postal code.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['city'] = ['Ort', 'Please add the city.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['addBookingForm'] = ['Activate the booking form', 'Activate the booking form and set the options.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['maxEscortsPerMember'] = ['Maximum escorts per participant', 'Please set the maximum number of escorts per participant.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['includeEscortsWhenCalculatingRegCount'] = ['Add accompanying persons to the total number of participants', 'Determine whether the accompanying persons should be added to the total number of participants.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingStartDate'] = ['Booking startdate', 'Set the booking starttime, please.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingEndDate'] = ['Booking enddate', 'Set the booking endtime, please.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['minMembers'] = ['Minimum participants', 'Please set the minimum participant number.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['maxMembers'] = ['Maximum participants', 'Please set the maximum participant number.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['enableNotificationCenter'] = ['Select message for booking confirmation', 'Select a message (Notification Center) for the booking confirmation.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationCenterIds'] = ['Choose one or more notifications', 'Choose one or more notifications.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationSender'] = ['Booking notification sender', 'Please choose the sender of the booking notification.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['enableDeregistration'] = ['Enable event unsubscription', 'Please choose if event unsubscription should be allowed.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['unsubscribeLimit'] = ['Unsubscription limit in days', 'Please set the number of days you allow users to unsubscribe from day of the event.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['unsubscribeLimitTstamp'] = ['Fixed unsubscription limit', 'Fixed time limit for unsubscriptions from this event (overrides subscription limit in days)'];
$GLOBALS['TL_LANG']['tl_calendar_events']['enableMultiBookingWithSameAddress'] = ['Enable mutiple bookings with same email address', 'Please choose if multiple bookings with same email address should be allowed.'];
