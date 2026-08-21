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
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use PHPUnit\Framework\Attributes\DataProvider;

class BookingCapacityTest extends ContaoTestCase
{
    public function testGetBookingCount(): void
    {
        $capacity = $this->capacity(5);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1]);

        $this->assertSame(5, $capacity->getBookingCount($event));
    }

    public function testGetBookingCountIncludingWaitingList(): void
    {
        // fetchOne returns the same value for both the booking and the waiting-list query,
        // so including the waiting list doubles the count.
        $capacity = $this->capacity(4);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1]);

        $this->assertSame(8, $capacity->getBookingCount($event, true));
    }

    public function testGetWaitingListCount(): void
    {
        $capacity = $this->capacity(3);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1]);

        $this->assertSame(3, $capacity->getWaitingListCount($event));
    }

    #[DataProvider('fullyBookedProvider')]
    public function testIsFullyBooked(int $maxBookings, int $bookingCount, bool $expected): void
    {
        $capacity = $this->capacity($bookingCount);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'maxBookings' => $maxBookings]);

        $this->assertSame($expected, $capacity->isFullyBooked($event));
    }

    public static function fullyBookedProvider(): iterable
    {
        yield 'unlimited (maxBookings < 1) is never full' => [0, 999, false];
        yield 'count below max' => [5, 2, false];
        yield 'count equals max' => [5, 5, true];
        yield 'count above max' => [5, 6, true];
    }

    #[DataProvider('canFulfillProvider')]
    public function testCanFulfillBookingRequest(int $maxBookings, int $bookingCount, int $requested, bool $expected): void
    {
        $capacity = $this->capacity($bookingCount);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'maxBookings' => $maxBookings]);

        $this->assertSame($expected, $capacity->canFulfillBookingRequest($event, $requested));
    }

    public static function canFulfillProvider(): iterable
    {
        yield 'fits exactly' => [5, 2, 3, true];
        yield 'exceeds max' => [5, 2, 4, false];
        yield 'empty event' => [5, 0, 5, true];
        yield 'unlimited (maxBookings < 1) always fits' => [0, 999, 10, true];
    }

    #[DataProvider('unlimitedCapacityProvider')]
    public function testHasUnlimitedCapacity(int $maxBookings, bool $expected): void
    {
        $capacity = $this->capacity(0);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'maxBookings' => $maxBookings]);

        $this->assertSame($expected, $capacity->hasUnlimitedCapacity($event));
    }

    public static function unlimitedCapacityProvider(): iterable
    {
        yield 'zero means no limit' => [0, true];
        yield 'a limit of one is a limit' => [1, false];
        yield 'a real limit' => [50, false];
    }

    public function testCanFulfillWaitingListReturnsFalseWhenDisabled(): void
    {
        $capacity = $this->capacity(0);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'enableWaitingList' => false]);

        $this->assertFalse($capacity->canFulfillBookingRequestWaitingList($event, 1));
    }

    public function testCanFulfillWaitingListReturnsTrueWhenUnlimited(): void
    {
        $capacity = $this->capacity(0);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'enableWaitingList' => true, 'maxWaitingList' => 0]);

        $this->assertTrue($capacity->canFulfillBookingRequestWaitingList($event, 5));
    }

    #[DataProvider('waitingListLimitProvider')]
    public function testCanFulfillWaitingListRespectsLimit(int $maxWaitingList, int $waitingCount, int $requested, bool $expected): void
    {
        $capacity = $this->capacity($waitingCount);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, [
            'id' => 1,
            'enableWaitingList' => true,
            'maxWaitingList' => $maxWaitingList,
        ]);

        $this->assertSame($expected, $capacity->canFulfillBookingRequestWaitingList($event, $requested));
    }

    public static function waitingListLimitProvider(): iterable
    {
        yield 'fits exactly' => [5, 2, 3, true];
        yield 'exceeds limit' => [5, 2, 4, false];
    }

    public function testIsWaitingListFullWhenDisabled(): void
    {
        $capacity = $this->capacity(0);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'enableWaitingList' => false]);

        // No waiting list available -> considered full.
        $this->assertTrue($capacity->isWaitingListFull($event));
    }

    #[DataProvider('freeSpotsProvider')]
    public function testGetFreeSpotsCount(int $maxBookings, int $bookingCount, int $expected): void
    {
        $capacity = $this->capacity($bookingCount);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 1, 'maxBookings' => $maxBookings]);

        $this->assertSame($expected, $capacity->getFreeSpotsCount($event));
    }

    public static function freeSpotsProvider(): iterable
    {
        yield 'some free' => [5, 2, 3];
        yield 'overbooked clamps to zero' => [2, 5, 0];
        yield 'all free' => [5, 0, 5];
        // Unlimited events report 0 here, callers must use hasUnlimitedCapacity().
        yield 'unlimited reports zero' => [0, 3, 0];
    }

    private function capacity(int $fetchOne): BookingCapacity
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturn($fetchOne)
        ;

        return new BookingCapacity($connection);
    }
}
