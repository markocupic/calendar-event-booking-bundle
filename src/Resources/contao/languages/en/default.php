<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;

// Misc
$GLOBALS['TL_LANG']['MSC']['bookings'] = 'bookings';

// Booking state references
$GLOBALS['TL_LANG']['MSC'][BookingState::STATE_UNDEFINED] = 'undefined';
$GLOBALS['TL_LANG']['MSC'][BookingState::STATE_NOT_CONFIRMED] = 'not confirmed';
$GLOBALS['TL_LANG']['MSC'][BookingState::STATE_CONFIRMED] = 'confirmed';
$GLOBALS['TL_LANG']['MSC'][BookingState::STATE_UNSUBSCRIBED] = 'unsubscribed';
$GLOBALS['TL_LANG']['MSC'][BookingState::STATE_REJECTED] = 'rejected';
$GLOBALS['TL_LANG']['MSC'][BookingState::STATE_WAITING_LIST] = 'waiting list';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Maximum %s escorts per participant possible.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'We have already found a booking with the email address "%s". Booking process aborted.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Please enter a positive number.';
$GLOBALS['TL_LANG']['MSC']['maxMemberLimitExceeded'] = 'The total number of %s participants is exceeded. Please check the number of accompanying persons.';

// On post booking messages
$GLOBALS['TL_LANG']['MSC']['post_booking_confirm_'.BookingState::STATE_NOT_CONFIRMED] = 'We have taken your booking for "%s" and will get in touch with you soon.';
$GLOBALS['TL_LANG']['MSC']['post_booking_confirm_'.BookingState::STATE_CONFIRMED] = 'We have successfully received your booking for "%s" and you have definitely been registered.';
$GLOBALS['TL_LANG']['MSC']['post_booking_confirm_'.BookingState::STATE_WAITING_LIST] = 'You have been successfully placed on the waiting list for "%s".';

// Form field
$GLOBALS['TL_LANG']['MSC']['addToWaitingList'] = 'Add to the waiting list.';

// Messages
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NOT_YET_POSSIBLE] = 'Booking is only possible from %s .';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NO_LONGER_POSSIBLE] = 'The registration period for this event has already expired. Unfortunately, registrations can no longer be accepted.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_EVENT_FULLY_BOOKED] = 'Sorry, but unfortunately this event is already fully booked.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_WAITING_LIST_POSSIBLE] = 'Unfortunately, the event is already fully booked. However, we may place you on the waiting list and we will notify you if you may move up as a result of cancellations.';

// Form validation backend
$GLOBALS['TL_LANG']['MSC']['adjustedBookingPeriodEndTime'] = 'The end date for the booking period has been adjusted.';

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
$GLOBALS['TL_LANG']['ERR']['alreadyUnsubscribed'] = 'You have already been unsubscribed from the event "%s".';
