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

namespace Markocupic\CalendarEventBookingBundle\Tests\Event;

use Contao\CalendarEventsModel;
use Contao\Model\Collection;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutHandlerInterface;
use Markocupic\CalendarEventBookingBundle\Event\PaymentEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsOrderModel;
use Symfony\Component\HttpFoundation\Request;

class PaymentEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $calEvent = $this->mockClassWithProperties(CalendarEventsModel::class, ['id' => 2]);
        $booking = $this->mockClassWithProperties(CalendarEventsMemberModel::class, ['id' => 1]);
        $order = $this->mockClassWithProperties(CalendarEventsOrderModel::class, ['id' => 3]);
        $payments = $this->createMock(Collection::class);
        $request = new Request();
        $handler = $this->createMock(CheckoutHandlerInterface::class);

        $event = new PaymentEvent($calEvent, $booking, $order, $payments, $request, $handler);

        $this->assertSame($calEvent, $event->getEvent());
        $this->assertSame($booking, $event->getBooking());
        $this->assertSame($order, $event->getOrder());
        $this->assertSame($payments, $event->getPayments());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($handler, $event->getCheckoutHandler());
    }

    public function testCheckoutHandlerIsNullWhenNotProvided(): void
    {
        $event = new PaymentEvent(
            $this->mockClassWithProperties(CalendarEventsModel::class),
            $this->mockClassWithProperties(CalendarEventsMemberModel::class),
            $this->mockClassWithProperties(CalendarEventsOrderModel::class),
            $this->createMock(Collection::class),
            new Request(),
        );

        $this->assertNull($event->getCheckoutHandler());
    }
}
