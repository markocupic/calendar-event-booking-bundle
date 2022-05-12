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

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Booking;

class BookingState
{
    public const STATE_UNDEFINED = 'undefined';
    public const STATE_NOT_CONFIRMED = 'not_confirmed';
    public const STATE_CONFIRMED = 'confirmed';
    public const STATE_WAITING_LIST = 'waiting_list';
    public const STATE_REJECTED = 'rejected';
    public const STATE_UNSUBSCRIBED = 'unsubscribed';
}
