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

namespace Markocupic\CalendarEventBookingBundle\Tests\Checkout;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ModuleModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutResult;
use Markocupic\CalendarEventBookingBundle\Checkout\DefaultCheckoutHandler;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

class DefaultCheckoutHandlerTest extends ContaoTestCase
{
    public function testGetType(): void
    {
        $this->assertSame('default', DefaultCheckoutHandler::getType());
        $this->assertSame(DefaultCheckoutHandler::NAME, DefaultCheckoutHandler::getType());
    }

    public function testHandleRequestBuildsResult(): void
    {
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['id' => 3]);

        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 2]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $booking = $this->mockBooking($event, ['id' => 1]);

        $model = $this->createClassWithPropertiesMock(ModuleModel::class);

        $result = (new DefaultCheckoutHandler())->handleRequest($booking, $model, new Request());

        $this->assertInstanceOf(CheckoutResult::class, $result);
        $this->assertSame('default', $result->getCheckoutType());

        $data = $result->getData();
        $this->assertSame(['id' => 1], $data['booking']);
        $this->assertSame(['id' => 2], $data['event']);
        $this->assertSame(['id' => 3], $data['calendar']);
        $this->assertSame($model, $data['module']);
    }

    public function testHandleRequestThrowsWhenEventMissing(): void
    {
        $booking = $this->mockBooking(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event not found.');

        (new DefaultCheckoutHandler())->handleRequest($booking, $this->createClassWithPropertiesMock(ModuleModel::class), new Request());
    }

    public function testHandleRequestThrowsWhenCalendarMissing(): void
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $booking = $this->mockBooking($event);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Calendar not found.');

        (new DefaultCheckoutHandler())->handleRequest($booking, $this->createClassWithPropertiesMock(ModuleModel::class), new Request());
    }

    /**
     * @param array<string, mixed> $props
     */
    private function mockBooking(CalendarEventsModel|null $event, array $props = []): CalendarEventsMemberModel&MockObject
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, $props);
        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($event)
        ;

        return $booking;
    }
}
