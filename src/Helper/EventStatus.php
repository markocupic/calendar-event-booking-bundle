<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

class EventStatus
{
    public const DRAFT = 'draft'; // Event is being created but not yet visible or bookable.

    public const BOOKING_OPEN = 'booking_open'; // Users can register or buy tickets.

    public const FULLY_BOOKED = 'fully_booked'; // All spots are taken; booking no more allowed.

    public const WAITING_LIST_OPEN = 'waiting_list_open'; // Event is full, but users can join a waiting list.

    public const NOT_BOOKABLE = 'not_bookable'; // Event is not bookable.

    public const NOT_YET_BOOKABLE = 'not_yet_bookable'; // Event is not yet bookable. Waiting for the booking period to start.

    public const BOOKING_CLOSED = 'booking_closed'; // Booking period has ended (manually or automatically).
}
