<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2021 Marko Cupic
 *
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2021
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

$GLOBALS['TL_LANG']['MSC']['bookings'] = 'bookings';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Maximum %s escorts per participant possible.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'We have already found a booking with the email address "%s". Booking process aborted.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Please enter a positive number.';
$GLOBALS['TL_LANG']['MSC']['bookingNotYetPossible'] = 'Booking is only possible from %s .';
$GLOBALS['TL_LANG']['MSC']['bookingNoLongerPossible'] = 'The registration period for this event has already expired. Unfortunately, registrations can no longer be accepted.';
$GLOBALS['TL_LANG']['MSC']['eventFullyBooked'] = 'Sorry, but unfortunately this event is already fully booked.';
$GLOBALS['TL_LANG']['MSC']['maxMemberLimitExceeded'] = 'The total number of %s participants is exceeded. Please check the number of accompanying persons.';
$GLOBALS['TL_LANG']['MSC']['loggedInAsBookingAdmin'] = 'You are logged in as the booking administrator. Bookings are possible at any time.';

// Form validation backend
$GLOBALS['TL_LANG']['MSC']['adjustedBookingPeriodEndtime'] = 'The end date for the booking period has been adjusted.';

// Unsubscribe from event

$GLOBALS['TL_LANG']['MSC']['unsubscribeInfo'] = 'You\'ve been successfully unsubscribed from event "%s".';
$GLOBALS['TL_LANG']['MSC']['unsubscribeConfirm'] = 'Dear <span class="event-member-name">%s %s</span><br>Are you sure you want to unsubscribe from event "%s"?';
$GLOBALS['TL_LANG']['BTN']['slabelUnsubscribeFromEvent'] = 'Unsubscribe from event';
$GLOBALS['TL_LANG']['BTN']['slabelCancel'] = 'Cancel';

// Errors
$GLOBALS['TL_LANG']['ERR']['unsubscriptionLimitExpired'] = 'The unsubscription limit for event "%s" has expired.';
$GLOBALS['TL_LANG']['ERR']['eventNotFound'] = 'Invalid booking token or could not find assigned event.';
$GLOBALS['TL_LANG']['ERR']['invalidBookingToken'] = 'Invalid booking token.';
$GLOBALS['TL_LANG']['ERR']['eventUnsubscriptionNotAllowed'] = 'You\'re not allowed to unsubscribe from event "%s".';
$GLOBALS['TL_LANG']['ERR']['invalidUnsubscriptionLimit'] = 'This unsubscription limit is too far in the future (see event start and end date and time).';
$GLOBALS['TL_LANG']['ERR']['conflictingUnsubscribeLimits'] = 'You cannot indicate both an unsubscription limit in days before the event and fixed limit at the same time. Please set unsubscription limit in days to 0 or delete the fixed limit.';
