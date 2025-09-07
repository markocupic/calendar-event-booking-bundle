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
        $amount = $this->calcNetAmountPerItem($event) + $this->calcVatAmountPerItem($event);

        return $this->formatPrice($amount);
    }

    public function calcGrossTotalAmount(CalendarEventsModel $event, CalendarEventsMemberModel $booking): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        $amount = $booking->ticketAmount * $this->calcGrossAmountPerItem($event);

        return $this->formatPrice($amount);
    }

    public function calcNetAmountPerItem(CalendarEventsModel $event): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        $amount = $event->netPrice;

        return $this->formatPrice($amount);
    }

    public function calcNetTotalAmount(CalendarEventsModel $event, CalendarEventsMemberModel $booking): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        $amount = $booking->ticketAmount * $this->calcNetAmountPerItem($event);

        return $this->formatPrice($amount);
    }

    public function calcVatAmountPerItem(CalendarEventsModel $event): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        $amount = $this->calcNetAmountPerItem($event) * $this->getTaxValue($event) * 0.01;

        return $this->formatPrice($amount);
    }

    public function calcVatTotalAmount(CalendarEventsModel $event, CalendarEventsMemberModel $booking): float
    {
        // @todo We could dispatch an event here to make it overwritable.
        $amount = $this->calcVatAmountPerItem($event) * $booking->ticketAmount;

        return $this->formatPrice($amount);
    }

    public function getCurrencyCode(CalendarEventsModel $event): string
    {
        return (string) $event->currencyCode;
    }

    public function getTaxValue(CalendarEventsModel $event): float
    {
        return (float) $event->taxValue;
    }

    protected function formatPrice(float $price): float
    {
        return round($price, 2);
    }
}
