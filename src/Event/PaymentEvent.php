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
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerInterface;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentEvent extends Event
{
    public function __construct(
        private readonly CalendarEventsModel $event,
        private readonly CalendarEventsMemberModel $booking,
        private readonly CalendarEventsPaymentModel $payment,
        private readonly CheckoutHandlerInterface $checkoutHandler,
        private readonly Request $request,
    ) {
    }

    public function getRequest(): Request|null
    {
        return $this->request;
    }

    public function getCheckoutHandler(): CheckoutHandlerInterface
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

    public function getPayment(): CalendarEventsPaymentModel
    {
        return $this->payment;
    }
}
