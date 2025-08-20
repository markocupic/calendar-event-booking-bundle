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

namespace Markocupic\CalendarEventBookingBundle\Parcel\Stamp;

use Terminal42\NotificationCenterBundle\Parcel\Stamp\StampInterface;

class CalendarEventBookingStamp implements StampInterface
{
    public function __construct(
        public string $booking_id,
        public string $notification_type,
    ) {
    }

    public function toArray(): array
    {
        return [
            'booking_id' => $this->booking_id,
            'notification_type' => $this->notification_type,
        ];
    }

    public static function fromArray(array $data): StampInterface
    {
        return new self((string) $data['booking_id'], $data['notification_type']);
    }
}
