<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Booking;

class BookingState
{
    public const STATE_UNDEFINED = 'cebb_booking_state_undefined';
    public const STATE_NOT_CONFIRMED = 'cebb_booking_state_not_confirmed';
    public const STATE_CONFIRMED = 'cebb_booking_state_confirmed';
    public const STATE_WAITING_LIST = 'cebb_booking_state_on_waiting_list';
    public const STATE_REJECTED = 'cebb_booking_state_rejected';
    public const STATE_UNSUBSCRIBED = 'cebb_booking_state_unsubscribed';
}
