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

namespace Markocupic\CalendarEventStatusBundle\Tests\Helper;

use Contao\CalendarEventsModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Helper\EventStatus;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventStatusTest extends ContaoTestCase
{
    private EventStatus $eventStatus;

    private Connection $connectionMock;

    private EventDispatcherInterface $eventDispatcherMock;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $this->eventStatus = new EventStatus(
            $this->connectionMock,
            $this->eventDispatcherMock,
        );
    }

    public function testDetermineEventStatusReturnsDraftIfEventIsNotPublished(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => false,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::DRAFT, $result);
    }

    public function testDetermineEventStatusReturnsDraftIfEventStartIsInFuture(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() + 3600,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::DRAFT, $result);
    }

    public function testDetermineEventStatusReturnsDraftIfEventEndIsInPast(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() - 3600,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::DRAFT, $result);
    }

    public function testDetermineEventStatusReturnsNotBookableIfBookingFormIsDisabled(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() + 3600,
            'enableBookingForm' => false,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::NOT_BOOKABLE, $result);
    }

    public function testDetermineEventStatusReturnsNotYetBookableIfBookingStartDateIsInFuture(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() + 3600,
            'enableBookingForm' => true,
            'bookingStartDate' => time() + 3600,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::NOT_YET_BOOKABLE, $result);
    }

    public function testDetermineEventStatusReturnsBookingClosedIfBookingEndDateIsInPast(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() + 3600,
            'enableBookingForm' => true,
            'bookingStartDate' => time() - 3600,
            'bookingEndDate' => time() - 1800,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::BOOKING_CLOSED, $result);
    }

    public function testDetermineEventStatusReturnsFullyBookedIfEventFullyBooked(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() + 3600,
            'enableBookingForm' => true,
            'bookingStartDate' => time() - 3600,
            'bookingEndDate' => time() + 3600,
        ]);

        $this->eventStatus = $this->getMockBuilder(EventStatus::class)
            ->setConstructorArgs([
                $this->connectionMock,
                $this->eventDispatcherMock,
            ])
            ->onlyMethods(['isFullyBooked'])
            ->getMock()
        ;

        $this->eventStatus
            ->method('isFullyBooked')
            ->willReturn(true)
        ;

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::FULLY_BOOKED, $result);
    }

    public function testDetermineEventStatusReturnsBookingOpenIfConditionsAllow(): void
    {
        $calendarEvent = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'published' => true,
            'start' => time() - 7200,
            'end' => time() + 3600,
            'enableBookingForm' => true,
            'bookingStartDate' => time() - 3600,
            'bookingEndDate' => time() + 3600,
        ]);

        $method = new \ReflectionMethod(EventStatus::class, 'determineEventStatus');
        $result = $method->invokeArgs($this->eventStatus, [$calendarEvent]);

        $this->assertSame(EventStatus::BOOKING_OPEN, $result);
    }
}
