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
use Contao\CalendarModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\WaitingListPromotionProcessor;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Terminal42\NotificationCenterBundle\NotificationCenter;

/**
 * Functional test that runs findNextEligibleBookingId() against a real in-memory
 * SQLite database to verify the promotion order (earliest addedOn wins) and the
 * eligibility filters.
 *
 * The method returns a raw id and takes the list of already handled bookings as an
 * argument - that list lives in processWaitingListForEvent() and is threaded
 * through here the same way, so these tests mirror one real run of the loop.
 */
class WaitingListPromotionProcessorFunctionalTest extends ContaoTestCase
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
                waitingList INTEGER NOT NULL DEFAULT 0,
                temporaryReserved INTEGER NOT NULL DEFAULT 0,
                canceled INTEGER NOT NULL DEFAULT 0,
                expired INTEGER NOT NULL DEFAULT 0,
                optIn INTEGER NOT NULL DEFAULT 0,
                ticketAmount INTEGER NOT NULL DEFAULT 1,
                addedOn INTEGER NOT NULL DEFAULT 0
            )',
        );
    }

    public function testPicksEarliestAddedOnAmongEligibleBookings(): void
    {
        // Eligible waiting-list bookings, deliberately inserted out of order.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 300]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 100]); // earliest
        $this->seed(['id' => 12, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 200]);

        // Ineligible rows that must be ignored.
        $this->seed(['id' => 20, 'pid' => 1, 'waitingList' => 0, 'addedOn' => 1]); // not on waiting list
        $this->seed(['id' => 21, 'pid' => 1, 'waitingList' => 1, 'canceled' => 1, 'addedOn' => 1]);
        $this->seed(['id' => 22, 'pid' => 1, 'waitingList' => 1, 'expired' => 1, 'addedOn' => 1]);
        $this->seed(['id' => 23, 'pid' => 1, 'waitingList' => 1, 'temporaryReserved' => 1, 'addedOn' => 1]);
        $this->seed(['id' => 24, 'pid' => 2, 'waitingList' => 1, 'addedOn' => 1]); // other event

        $bookingId = $this->findNext($this->createProcessor(), $this->event(1, false), 5);

        $this->assertSame(11, $bookingId);
    }

    public function testAdvancesInOrderAcrossConsecutiveCalls(): void
    {
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 300]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 100]);
        $this->seed(['id' => 12, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 200]);

        $processor = $this->createProcessor();
        $event = $this->event(1, false);

        // What processWaitingListForEvent() does: carry the ids already handled
        // forward, so consecutive calls walk the queue in order instead of
        // returning the same booking over and over.
        $processedIds = [0];
        $picked = [];

        while (null !== $bookingId = $this->findNext($processor, $event, 5, $processedIds)) {
            $picked[] = $bookingId;
            $processedIds[] = $bookingId;
        }

        $this->assertSame([11, 12, 10], $picked);
    }

    public function testRespectsAvailableSlotsForTicketAmount(): void
    {
        // Earliest booking needs 2 tickets but only 1 slot is free -> must be skipped.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 2, 'addedOn' => 100]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 1, 'addedOn' => 200]);

        $bookingId = $this->findNext($this->createProcessor(), $this->event(1, false), 1);

        $this->assertSame(11, $bookingId);
    }

    /**
     * An event without a booking limit passes PHP_INT_MAX as the number of
     * available slots (see getAvailableSlots()), which must not overflow the
     * ticketAmount comparison.
     */
    public function testUnlimitedSlotsPromoteEvenLargeTicketAmounts(): void
    {
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 999, 'addedOn' => 100]);

        $bookingId = $this->findNext($this->createProcessor(), $this->event(1, false), PHP_INT_MAX);

        $this->assertSame(10, $bookingId);
    }

    public function testRequireOptInOnlyPromotesConfirmedBookings(): void
    {
        // Earliest booking is not confirmed; only the later confirmed one is eligible.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'optIn' => 0, 'addedOn' => 100]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'optIn' => 1, 'addedOn' => 200]);

        $bookingId = $this->findNext($this->createProcessor(), $this->event(1, true), 5);

        $this->assertSame(11, $bookingId);
    }

    public function testReturnsNullWhenNoEligibleBookingExists(): void
    {
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 0, 'addedOn' => 100]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'canceled' => 1, 'addedOn' => 200]);

        $this->assertNull($this->findNext($this->createProcessor(), $this->event(1, false), 5));
    }

    public function testReturnsNullWhenCalendarDisallowsEventBooking(): void
    {
        // A perfectly eligible booking exists, but the calendar no longer allows
        // event booking -> the whole event is skipped before any SELECT runs.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 100]);

        $this->assertNull($this->findNext($this->createProcessor(), $this->eventWithFlags(1, allowEventBooking: false, enableBookingForm: true), 5));
    }

    public function testReturnsNullWhenEventBookingFormIsDisabled(): void
    {
        // Same, but this time the event itself has its booking form disabled.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 100]);

        $this->assertNull($this->findNext($this->createProcessor(), $this->eventWithFlags(1, allowEventBooking: true, enableBookingForm: false), 5));
    }

    /**
     * @param array<string, int> $row
     */
    private function seed(array $row): void
    {
        $row += [
            'pid' => 1,
            'waitingList' => 0,
            'temporaryReserved' => 0,
            'canceled' => 0,
            'expired' => 0,
            'optIn' => 0,
            'ticketAmount' => 1,
            'addedOn' => 0,
        ];

        $this->connection->insert('tl_calendar_events_member', $row);
    }

    private function event(int $id, bool $requireOptIn): CalendarEventsModel
    {
        $calendar = $this->createClassWithPropertiesStub(CalendarModel::class, ['allowEventBooking' => true, 'requireOptIn' => $requireOptIn]);

        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => $id, 'enableBookingForm' => true]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        return $event;
    }

    private function eventWithFlags(int $id, bool $allowEventBooking, bool $enableBookingForm): CalendarEventsModel
    {
        $calendar = $this->createClassWithPropertiesStub(CalendarModel::class, ['allowEventBooking' => $allowEventBooking, 'requireOptIn' => false]);

        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => $id, 'enableBookingForm' => $enableBookingForm]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        return $event;
    }

    /**
     * @param array<int> $processedIds
     */
    private function findNext(WaitingListPromotionProcessor $processor, CalendarEventsModel $event, int $availableSlots, array $processedIds = [0]): int|null
    {
        return (new \ReflectionMethod(WaitingListPromotionProcessor::class, 'findNextEligibleBookingId'))
            ->invoke($processor, $event, $availableSlots, $processedIds)
        ;
    }

    private function createProcessor(): WaitingListPromotionProcessor
    {
        // findNextEligibleBookingId() only returns an id - the model re-fetch happens
        // in processWaitingListForEvent(), so no framework adapter is needed here and
        // the real SQLite SELECT stays the single source of truth.
        return new WaitingListPromotionProcessor(
            $this->connection,
            $this->createContaoFrameworkStub([]),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(BookingCapacity::class),
            $this->createStub(LockFactory::class),
            $this->createStub(NotificationCenter::class),
            $this->createStub(NotificationService::class),
            $this->createStub(RequestStack::class),
            true,
            $this->createStub(LoggerInterface::class),
        );
    }
}
