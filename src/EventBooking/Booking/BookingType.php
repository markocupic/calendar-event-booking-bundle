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

class BookingType
{
    public const TYPE_GUEST = 'booking_type_guest';
    public const TYPE_MEMBER = 'booking_type_member';
    public const TYPE_MANUALLY = 'booking_type_manually';
}
