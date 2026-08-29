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

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Booking;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\EventStatusResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventStatusResolverTest extends ContaoTestCase
{
    private BookingCapacity&MockObject $bookingCapacity;

    private EventStatusResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingCapacity = $this->createMock(BookingCapacity::class);
        $this->resolver = new EventStatusResolver(
            $this->bookingCapacity,
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    public function testReturnsDraftWhenNotPublished(): void
    {
        $this->assertStatus(EventStatusResolver::DRAFT, ['published' => false]);
    }

    public function testReturnsDraftWhenStartInFuture(): void
    {
        $this->assertStatus(EventStatusResolver::DRAFT, ['published' => true, 'start' => time() + 3600]);
    }

    public function testReturnsDraftWhenEndInPast(): void
    {
        $this->assertStatus(EventStatusResolver::DRAFT, ['published' => true, 'start' => time() - 7200, 'end' => time() - 3600]);
    }

    public function testReturnsNotBookableWhenBookingFormDisabled(): void
    {
        $this->assertStatus(EventStatusResolver::NOT_BOOKABLE, [
            'published' => true, 'start' => time() - 7200, 'end' => time() + 3600, 'enableBookingForm' => false,
        ]);
    }

    public function testReturnsNotYetBookableWhenBookingStartInFuture(): void
    {
        $this->assertStatus(EventStatusResolver::NOT_YET_BOOKABLE, [
            'published' => true, 'start' => time() - 7200, 'end' => time() + 3600,
            'enableBookingForm' => true, 'bookingStartDate' => time() + 3600,
        ]);
    }

    public function testReturnsBookingClosedWhenBookingEndInPast(): void
    {
        $this->assertStatus(EventStatusResolver::BOOKING_CLOSED, [
            'published' => true, 'start' => time() - 7200, 'end' => time() + 3600,
            'enableBookingForm' => true, 'bookingStartDate' => time() - 3600, 'bookingEndDate' => time() - 1800,
        ]);
    }

    public function testReturnsFullyBookedWhenFullAndWaitingListUnavailable(): void
    {
        $this->bookingCapacity
            ->method('isFullyBooked')
            ->willReturn(true)
        ;

        $this->bookingCapacity
            ->method('canFulfillBookingRequestWaitingList')
            ->willReturn(false)
        ;

        $this->assertStatus(EventStatusResolver::FULLY_BOOKED, $this->bookableEventProps());
    }

    public function testReturnsWaitingListOpenWhenFullButWaitingListAvailable(): void
    {
        $this->bookingCapacity
            ->method('isFullyBooked')
            ->willReturn(true)
        ;

        $this->bookingCapacity
            ->method('canFulfillBookingRequestWaitingList')
            ->willReturn(true)
        ;

        $this->assertStatus(EventStatusResolver::WAITING_LIST_OPEN, $this->bookableEventProps());
    }

    public function testReturnsBookingOpenWhenNotFull(): void
    {
        $this->bookingCapacity
            ->method('isFullyBooked')
            ->willReturn(false)
        ;

        $this->assertStatus(EventStatusResolver::BOOKING_OPEN, $this->bookableEventProps());
    }

    public function testThrowsWhenCalendarIsNotFound(): void
    {
        // The related calendar is required up front; without it the status cannot be
        // determined and an exception is raised instead of guessing a status.
        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => 42]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $method = new \ReflectionMethod(EventStatusResolver::class, 'determineEventStatus');

        $this->expectException(\Exception::class);
        $method->invoke($this->resolver, $event);
    }

    public function testReturnsNotBookableWhenCalendarDisallowsEventBooking(): void
    {
        // Even a fully bookable event counts as not bookable when its calendar forbids booking.
        $this->assertStatus(EventStatusResolver::NOT_BOOKABLE, $this->bookableEventProps(), ['allowEventBooking' => false]);
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $calendarProps
     */
    private function assertStatus(string $expected, array $props, array $calendarProps = ['allowEventBooking' => true]): void
    {
        $calendar = $this->createClassWithPropertiesStub(CalendarModel::class, $calendarProps);

        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, $props);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $method = new \ReflectionMethod(EventStatusResolver::class, 'determineEventStatus');

        $this->assertSame($expected, $method->invoke($this->resolver, $event));
    }

    /**
     * @return array<string, mixed>
     */
    private function bookableEventProps(): array
    {
        return [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() + 3600,
            'enableBookingForm' => true,
            'bookingStartDate' => time() - 3600,
            'bookingEndDate' => time() + 3600,
        ];
    }
}
