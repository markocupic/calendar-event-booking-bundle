<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

$GLOBALS['TL_LANG']['MSC']['bookings'] = 'bookings';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Maximum %s escorts per participant possible.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'We have already found a booking with the email address "%s". Booking process aborted.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Bitte geben Sie eine positive natürliche Zahl ein.';

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
