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

namespace Markocupic\CalendarEventBookingBundle\Domain\Order;

use Contao\CalendarEventsModel;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateGrossAmountPerItemEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateGrossTotalAmountEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateNetAmountPerItemEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateNetTotalAmountEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateVatAmountPerItemEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateVatTotalAmountEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\GetTaxValueEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class PriceCalculator
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function calcGrossAmountPerItem(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $grossAmountPerItem = $this->calcNetAmountPerItem($calEvent, $booking) + $this->calcVatAmountPerItem($calEvent, $booking);

        $event = new CalculateGrossAmountPerItemEvent($calEvent, $booking, $grossAmountPerItem);
        $this->eventDispatcher->dispatch($event);

        return $this->formatPrice($event->getGrossAmountPerItem());
    }

    public function calcGrossTotalAmount(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $grossTotalAmount = $booking->ticketAmount * $this->calcGrossAmountPerItem($calEvent, $booking);

        $event = new CalculateGrossTotalAmountEvent($calEvent, $booking, $grossTotalAmount);
        $this->eventDispatcher->dispatch($event);

        return $this->formatPrice($event->getGrossTotalAmount());
    }

    public function calcNetAmountPerItem(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $netAmountPerItem = (float) $calEvent->netPrice;

        $event = new CalculateNetAmountPerItemEvent($calEvent, $booking, $netAmountPerItem);
        $this->eventDispatcher->dispatch($event);

        return $this->formatPrice($event->getNetAmountPerItem());
    }

    public function calcNetTotalAmount(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $netTotalAmount = $booking->ticketAmount * $this->calcNetAmountPerItem($calEvent, $booking);

        $event = new CalculateNetTotalAmountEvent($calEvent, $booking, $netTotalAmount);
        $this->eventDispatcher->dispatch($event);

        return $this->formatPrice($event->getNetTotalAmount());
    }

    public function calcVatAmountPerItem(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $vatAmountPerItem = $this->calcNetAmountPerItem($calEvent, $booking) * $this->getTaxValue($calEvent, $booking) * 0.01;

        $event = new CalculateVatAmountPerItemEvent($calEvent, $booking, $vatAmountPerItem);
        $this->eventDispatcher->dispatch($event);

        return $this->formatPrice($event->getVatAmountPerItem());
    }

    public function calcVatTotalAmount(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $vatTotalAmount = $this->calcVatAmountPerItem($calEvent, $booking) * $booking->ticketAmount;

        $event = new CalculateVatTotalAmountEvent($calEvent, $booking, $vatTotalAmount);
        $this->eventDispatcher->dispatch($event);

        return $this->formatPrice($event->getVatTotalAmount());
    }

    public function getCurrencyCode(CalendarEventsModel $calEvent): string
    {
        return (string) $calEvent->currencyCode;
    }

    public function getTaxValue(CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): float
    {
        $taxValue = (float) $calEvent->taxValue;

        $event = new GetTaxValueEvent($calEvent, $booking, $taxValue);
        $this->eventDispatcher->dispatch($event);

        return $event->getTaxValue();
    }

    protected function formatPrice(float $price): float
    {
        return round($price, 2);
    }
}
