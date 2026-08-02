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

namespace Markocupic\CalendarEventBookingBundle\Event;

use Contao\CalendarEventsModel;
use Contao\Model\Collection;
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutHandlerInterface;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsOrderModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentEvent extends Event
{
    public function __construct(
        private readonly CalendarEventsModel $event,
        private readonly CalendarEventsMemberModel $booking,
        private readonly CalendarEventsOrderModel $order,
        private readonly Collection $payments,
        private readonly Request $request,
        private readonly CheckoutHandlerInterface|null $checkoutHandler = null,
    ) {
    }

    public function getRequest(): Request|null
    {
        return $this->request;
    }

    public function getCheckoutHandler(): CheckoutHandlerInterface|null
    {
        return $this->checkoutHandler;
    }

    public function getEvent(): CalendarEventsModel
    {
        return $this->event;
    }

    public function getBooking(): CalendarEventsMemberModel
    {
        return $this->booking;
    }

    public function getOrder(): CalendarEventsOrderModel
    {
        return $this->order;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }
}
