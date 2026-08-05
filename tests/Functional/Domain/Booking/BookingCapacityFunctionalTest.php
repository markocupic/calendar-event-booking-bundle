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

namespace Markocupic\CalendarEventBookingBundle\Tests\Functional\Domain\Booking;

use Contao\CalendarEventsModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Functional test that runs BookingCapacity against a real in-memory SQLite
 * database. Unlike the unit test (which mocks fetchOne to a fixed value), this
 * verifies the actual SQL: the WHERE filters (pid, canceled, expired, waitingList)
 * and the SUM(ticketAmount) aggregation.
 */
class BookingCapacityFunctionalTest extends ContaoTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement(
            'CREATE TABLE tl_calendar_events_member (
                id INTEGER PRIMARY KEY,
                pid INTEGER NOT NULL DEFAULT 0,
                canceled INTEGER NOT NULL DEFAULT 0,
                expired INTEGER NOT NULL DEFAULT 0,
                waitingList INTEGER NOT NULL DEFAULT 0,
                ticketAmount INTEGER NOT NULL DEFAULT 1
            )',
        );
    }

    public function testGetBookingCountSumsOnlyActiveNonWaitingListTickets(): void
    {
        // Counted: active, non-waiting-list bookings for this event.
        $this->seed(['id' => 1, 'pid' => 1, 'ticketAmount' => 2]);
        $this->seed(['id' => 2, 'pid' => 1, 'ticketAmount' => 3]);

        // Ignored: canceled / expired / waiting list / different event.
        $this->seed(['id' => 3, 'pid' => 1, 'canceled' => 1, 'ticketAmount' => 4]);
        $this->seed(['id' => 4, 'pid' => 1, 'expired' => 1, 'ticketAmount' => 5]);
        $this->seed(['id' => 5, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 6]);
        $this->seed(['id' => 6, 'pid' => 2, 'ticketAmount' => 7]);

        $this->assertSame(5, $this->capacity()->getBookingCount($this->event(1)));
    }

    public function testGetBookingCountIsZeroWhenNoRowsMatch(): void
    {
        $this->seed(['id' => 1, 'pid' => 2, 'ticketAmount' => 3]); // other event only

        // SUM over an empty set is NULL -> cast to 0.
        $this->assertSame(0, $this->capacity()->getBookingCount($this->event(1)));
    }

    public function testGetWaitingListCountSumsOnlyWaitingListTickets(): void
    {
        $this->seed(['id' => 1, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 2]);
        $this->seed(['id' => 2, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 3]);

        // Ignored for the waiting-list count.
        $this->seed(['id' => 3, 'pid' => 1, 'waitingList' => 0, 'ticketAmount' => 4]);
        $this->seed(['id' => 4, 'pid' => 1, 'waitingList' => 1, 'canceled' => 1, 'ticketAmount' => 5]);
        $this->seed(['id' => 5, 'pid' => 1, 'waitingList' => 1, 'expired' => 1, 'ticketAmount' => 6]);
        $this->seed(['id' => 6, 'pid' => 2, 'waitingList' => 1, 'ticketAmount' => 7]);

        $this->assertSame(5, $this->capacity()->getWaitingListCount($this->event(1)));
    }

    public function testGetBookingCountIncludingWaitingListAddsBothSums(): void
    {
        $this->seed(['id' => 1, 'pid' => 1, 'waitingList' => 0, 'ticketAmount' => 5]); // regular
        $this->seed(['id' => 2, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 3]); // waiting list

        $capacity = $this->capacity();
        $event = $this->event(1);

        $this->assertSame(5, $capacity->getBookingCount($event));
        $this->assertSame(8, $capacity->getBookingCount($event, true));
    }

    public function testIsFullyBookedReflectsRealTicketSum(): void
    {
        $this->seed(['id' => 1, 'pid' => 1, 'ticketAmount' => 4]);

        $capacity = $this->capacity();

        $this->assertFalse($capacity->isFullyBooked($this->event(1, ['maxBookings' => 5])));
        $this->assertTrue($capacity->isFullyBooked($this->event(1, ['maxBookings' => 4])));
        // maxBookings < 1 means unlimited -> never fully booked.
        $this->assertFalse($capacity->isFullyBooked($this->event(1, ['maxBookings' => 0])));
    }

    public function testGetFreeSpotsCountReflectsRealTicketSum(): void
    {
        $this->seed(['id' => 1, 'pid' => 1, 'ticketAmount' => 4]);

        $capacity = $this->capacity();

        $this->assertSame(6, $capacity->getFreeSpotsCount($this->event(1, ['maxBookings' => 10])));
        // Overbooked clamps to zero.
        $this->assertSame(0, $capacity->getFreeSpotsCount($this->event(1, ['maxBookings' => 3])));
    }

    public function testCanFulfillBookingRequestAgainstRealSum(): void
    {
        $this->seed(['id' => 1, 'pid' => 1, 'ticketAmount' => 2]);

        $capacity = $this->capacity();
        $event = $this->event(1, ['maxBookings' => 5]);

        $this->assertTrue($capacity->canFulfillBookingRequest($event, 3)); // 2 + 3 = 5
        $this->assertFalse($capacity->canFulfillBookingRequest($event, 4)); // 2 + 4 = 6
    }

    public function testWaitingListCapacityAgainstRealSum(): void
    {
        $this->seed(['id' => 1, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 3]);

        $capacity = $this->capacity();

        $limited = $this->event(1, ['enableWaitingList' => true, 'maxWaitingList' => 5]);
        $this->assertTrue($capacity->canFulfillBookingRequestWaitingList($limited, 2)); // 3 + 2 = 5
        $this->assertFalse($capacity->canFulfillBookingRequestWaitingList($limited, 3)); // 3 + 3 = 6
        $this->assertFalse($capacity->isWaitingListFull($limited)); // one more (3+1=4) still fits

        $full = $this->event(1, ['enableWaitingList' => true, 'maxWaitingList' => 3]);
        $this->assertTrue($capacity->isWaitingListFull($full)); // 3 + 1 > 3

        $disabled = $this->event(1, ['enableWaitingList' => false]);
        $this->assertFalse($capacity->canFulfillBookingRequestWaitingList($disabled, 1));

        $unlimited = $this->event(1, ['enableWaitingList' => true, 'maxWaitingList' => 0]);
        $this->assertTrue($capacity->canFulfillBookingRequestWaitingList($unlimited, 999));
    }

    /**
     * @param array<string, int> $row
     */
    private function seed(array $row): void
    {
        $row += [
            'pid' => 1,
            'canceled' => 0,
            'expired' => 0,
            'waitingList' => 0,
            'ticketAmount' => 1,
        ];

        $this->connection->insert('tl_calendar_events_member', $row);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function event(int $id, array $overrides = []): CalendarEventsModel&MockObject
    {
        return $this->createClassWithPropertiesMock(CalendarEventsModel::class, array_merge(['id' => $id], $overrides));
    }

    private function capacity(): BookingCapacity
    {
        return new BookingCapacity($this->connection);
    }
}
