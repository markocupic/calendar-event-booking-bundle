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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

class OrderManager
{
    public function calcGrossAmountPerItem(CalendarEventsModel $event): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        return $this->calcNetAmountPerItem($event) + $this->calcVatAmountPerItem($event);
    }

    public function calcGrossTotalAmount(CalendarEventsModel $event, CalendarEventsMemberModel $booking): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        return $this->calcNetTotalAmount($event, $booking) + $this->calcVatTotalAmount($event, $booking);
    }

    public function calcNetAmountPerItem(CalendarEventsModel $event): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        return $event->netPrice;
    }

    public function calcNetTotalAmount(CalendarEventsModel $event, CalendarEventsMemberModel $booking): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        return $booking->ticketAmount * $this->calcNetAmountPerItem($event);
    }

    public function calcVatAmountPerItem(CalendarEventsModel $event): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        return $this->calcNetAmountPerItem($event) * $this->getTaxValue($event) * 0.01;
    }

    public function calcVatTotalAmount(CalendarEventsModel $event, CalendarEventsMemberModel $booking): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        return $this->calcNetTotalAmount($event, $booking) * $this->getTaxValue($event) * 0.01;
    }

    public function getCurrencyCode(CalendarEventsModel $event): string
    {
        return $event->currencyCode;
    }

    public function getTaxValue(CalendarEventsModel $event): float
    {
        return $event->taxValue;
    }
}
