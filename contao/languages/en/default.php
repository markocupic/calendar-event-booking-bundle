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

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;

$GLOBALS['TL_LANG']['MSC']['bookings'] = 'bookings';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Maximum %s escorts per participant possible.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'We have already found a booking with the email address "%s". Booking process aborted.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Please enter a positive number.';
$GLOBALS['TL_LANG']['MSC']['maxMemberLimitExceeded'] = 'The total number of %s participants is exceeded. Please check the number of accompanying persons.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NOT_YET_POSSIBLE] = 'Booking is only possible from %s .';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NO_LONGER_POSSIBLE] = 'The registration period for this event has already expired. Unfortunately, registrations can no longer be accepted.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_EVENT_FULLY_BOOKED] = 'Sorry, but unfortunately this event is already fully booked.';

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
