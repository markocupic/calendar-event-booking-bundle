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
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Terminal42\NotificationCenterBundle\NotificationCenter;

/**
 * Functional test that runs findNextEligibleBooking() against a real in-memory
 * SQLite database to verify the promotion order (earliest addedOn wins) and the
 * eligibility filters. No Contao kernel is booted: the model re-fetch goes through
 * the injected framework adapter, so it can be stubbed.
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

        $processor = $this->createProcessor();

        $booking = $this->findNext($processor, $this->event(1, false), 5);

        $this->assertNotNull($booking);
        $this->assertSame(11, $booking->id);
    }

    public function testAdvancesInOrderAcrossConsecutiveCalls(): void
    {
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 300]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 100]);
        $this->seed(['id' => 12, 'pid' => 1, 'waitingList' => 1, 'addedOn' => 200]);

        $processor = $this->createProcessor();
        $event = $this->event(1, false);

        // processedIds is instance state, so consecutive calls must walk the queue in order.
        $this->assertSame(11, $this->findNext($processor, $event, 5)->id);
        $this->assertSame(12, $this->findNext($processor, $event, 5)->id);
        $this->assertSame(10, $this->findNext($processor, $event, 5)->id);
        $this->assertNull($this->findNext($processor, $event, 5));
    }

    public function testRespectsAvailableSlotsForTicketAmount(): void
    {
        // Earliest booking needs 2 tickets but only 1 slot is free -> must be skipped.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 2, 'addedOn' => 100]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'ticketAmount' => 1, 'addedOn' => 200]);

        $processor = $this->createProcessor();

        $booking = $this->findNext($processor, $this->event(1, false), 1);

        $this->assertSame(11, $booking->id);
    }

    public function testRequireOptInOnlyPromotesConfirmedBookings(): void
    {
        // Earliest booking is not confirmed; only the later confirmed one is eligible.
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 1, 'optIn' => 0, 'addedOn' => 100]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'optIn' => 1, 'addedOn' => 200]);

        $processor = $this->createProcessor();

        $booking = $this->findNext($processor, $this->event(1, true), 5);

        $this->assertSame(11, $booking->id);
    }

    public function testReturnsNullWhenNoEligibleBookingExists(): void
    {
        $this->seed(['id' => 10, 'pid' => 1, 'waitingList' => 0, 'addedOn' => 100]);
        $this->seed(['id' => 11, 'pid' => 1, 'waitingList' => 1, 'canceled' => 1, 'addedOn' => 200]);

        $processor = $this->createProcessor();

        $this->assertNull($this->findNext($processor, $this->event(1, false), 5));
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
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['requireOptIn' => $requireOptIn]);

        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => $id]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        return $event;
    }

    private function findNext(WaitingListPromotionProcessor $processor, CalendarEventsModel $event, int $availableSlots): CalendarEventsMemberModel|null
    {
        return (new \ReflectionMethod(WaitingListPromotionProcessor::class, 'findNextEligibleBooking'))
            ->invoke($processor, $event, $availableSlots)
        ;
    }

    private function createProcessor(): WaitingListPromotionProcessor
    {
        // The model re-fetch is stubbed via the framework adapter: findById($id)
        // returns a lightweight member model carrying that id, so the real SQLite
        // SELECT stays the single source of truth for which booking is chosen.
        $memberAdapter = $this->createAdapterMock(['findById']);
        $memberAdapter
            ->method('findById')
            ->willReturnCallback(fn (int $id): CalendarEventsMemberModel => $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => $id]))
        ;

        $framework = $this->createContaoFrameworkStub([CalendarEventsMemberModel::class => $memberAdapter]);

        return new WaitingListPromotionProcessor(
            $this->connection,
            $framework,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(BookingCapacity::class),
            $this->createMock(LockFactory::class),
            $this->createMock(NotificationCenter::class),
            $this->createMock(NotificationService::class),
            $this->createMock(RequestStack::class),
            true,
            $this->createMock(LoggerInterface::class),
        );
    }
}
