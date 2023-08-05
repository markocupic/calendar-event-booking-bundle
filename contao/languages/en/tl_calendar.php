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

// Legends
$GLOBALS['TL_LANG']['tl_calendar']['event_unsubscribe_legend'] = 'Event unsubscription settings (the highlighted fields can be used to override the settings in the child events)';
$GLOBALS['TL_LANG']['tl_calendar']['booking_options_legend'] = 'Booking settings (the highlighted fields can be used to override the settings in the child events)';

// Fields
$GLOBALS['TL_LANG']['tl_calendar']['eventUnsubscribePage'] = ['Page containing the event unsubscription module', 'Please choose the page to which users are redirected when clicking the unsubscription link'];
$GLOBALS['TL_LANG']['tl_calendar']['calculateTotalFrom'] = ['Calculate total from', 'Specify which booking status should be used to calculate the total number of participants. Is required to calculate whether the event is fully booked.'];
$GLOBALS['TL_LANG']['tl_calendar']['addEscortsToTotal'] = ['Add accompanying persons to the total number of participants', 'Determine whether the accompanying persons should be added to the total number of participants.'];
$GLOBALS['TL_LANG']['tl_calendar']['waitingListLimit'] = ['Waiting list limit', 'Set the waiting list limit. Choose 0 to allow unlimited subscriptions.'];
$GLOBALS['TL_LANG']['tl_calendar']['eventBookingNotification'] = ['Choose one or more notifications', 'Choose one or more notifications.'];
$GLOBALS['TL_LANG']['tl_calendar']['eventBookingNotificationSender'] = ['Booking notification sender', 'Please choose the sender of the booking notification.'];
$GLOBALS['TL_LANG']['tl_calendar']['activateUnsubscribeNotification'] = ['Unsubscribe notification', 'Enable unsubscribe notifications.'];
$GLOBALS['TL_LANG']['tl_calendar']['eventUnsubscribeNotification'] = ['Choose one or more notifications', 'Choose one or more notifications.'];
$GLOBALS['TL_LANG']['tl_calendar']['eventUnsubscribeNotificationSender'] = ['Unsubscribe notification sender', 'Please choose the sender of the unsubscribe notification.'];
$GLOBALS['TL_LANG']['tl_calendar']['allowDuplicateEmail'] = ['Enable multiple bookings with same email address', 'Please choose if multiple bookings with same email address should be allowed.'];
$GLOBALS['TL_LANG']['tl_calendar']['bookingState'] = ['Booking state', 'Select the state that is automatically assigned to a new booking.'];
