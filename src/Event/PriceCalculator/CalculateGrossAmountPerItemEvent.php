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

namespace Markocupic\CalendarEventBookingBundle\Event\PriceCalculator;

use Contao\CalendarEventsModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\EventDispatcher\Event;

class CalculateGrossAmountPerItemEvent extends Event
{
    public function __construct(
        private readonly CalendarEventsModel $calEvent,
        private readonly CalendarEventsMemberModel $booking,
        private float $grossAmountPerItem,
    ) {
    }

    public function getCalendarEvent(): CalendarEventsModel
    {
        return $this->calEvent;
    }

    public function getBooking(): CalendarEventsMemberModel
    {
        return $this->booking;
    }

    public function getGrossAmountPerItem(): float
    {
        return $this->grossAmountPerItem;
    }

    public function setGrossAmountPerItem(float $grossAmountPerItem): void
    {
        $this->grossAmountPerItem = $grossAmountPerItem;
    }
}
