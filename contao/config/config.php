<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsBookingNotificationModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;

// Add child tables to the calendar module
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = CalendarEventsMemberModel::getTable();
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = CalendarEventsPaymentModel::getTable();
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = CalendarEventsBookingNotificationModel::getTable();

// Register custom models
$GLOBALS['TL_MODELS']['tl_calendar_events_member'] = CalendarEventsMemberModel::class;
$GLOBALS['TL_MODELS']['tl_calendar_events_payment'] = CalendarEventsPaymentModel::class;
$GLOBALS['TL_MODELS']['tl_calendar_events_booking_notification'] = CalendarEventsBookingNotificationModel::class;
