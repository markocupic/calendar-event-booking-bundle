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

namespace Markocupic\CalendarEventBookingBundle\Tests\Functional\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Markocupic\CalendarEventBookingBundle\Cron\HandleExpirableBookingsCron;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Functional test for the SELECT that decides which temporarily reserved bookings
 * are eligible for auto-expiry, run against a real in-memory SQLite database.
 */
class HandleExpirableBookingsCronFunctionalTest extends ContaoTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement(
            'CREATE TABLE tl_calendar_events_member (
                id INTEGER PRIMARY KEY,
                temporaryReserved INTEGER NOT NULL DEFAULT 0,
                expired INTEGER NOT NULL DEFAULT 0,
                paid INTEGER NOT NULL DEFAULT 0,
                addedOn INTEGER NOT NULL DEFAULT 0
            )',
        );
    }

    public function testFetchExpirableBookingsOnlyReturnsReservedNotYetExpiredBeforeCutoff(): void
    {
        // Eligible: temporarily reserved, not yet expired, added before the cutoff.
        $this->seed(['id' => 1, 'temporaryReserved' => 1, 'expired' => 0, 'addedOn' => 100]);

        // Too recent: added at/after the cutoff -> still within the grace period.
        $this->seed(['id' => 2, 'temporaryReserved' => 1, 'expired' => 0, 'addedOn' => 2000]);

        // Confirmed booking (not a temporary reservation).
        $this->seed(['id' => 3, 'temporaryReserved' => 0, 'expired' => 0, 'addedOn' => 100]);

        // Already expired.
        $this->seed(['id' => 4, 'temporaryReserved' => 1, 'expired' => 1, 'addedOn' => 100]);

        // Paid: the seat has been bought. A payment provider may leave the booking
        // flagged as temporarily reserved for a while (delayed payment methods), so
        // without the paid guard the cron would expire a booking that is settled.
        $this->seed(['id' => 5, 'temporaryReserved' => 1, 'expired' => 0, 'paid' => 1, 'addedOn' => 100]);

        $ids = (new \ReflectionMethod(HandleExpirableBookingsCron::class, 'fetchExpirableBookings'))
            ->invoke($this->createCron(), 1000)
        ;

        $this->assertSame([1], array_map('intval', $ids));
    }

    /**
     * @param array<string, int> $row
     */
    private function seed(array $row): void
    {
        $row += ['temporaryReserved' => 0, 'expired' => 0, 'paid' => 0, 'addedOn' => 0];

        $this->connection->insert('tl_calendar_events_member', $row);
    }

    private function createCron(): HandleExpirableBookingsCron
    {
        return new HandleExpirableBookingsCron(
            $this->connection,
            $this->createMock(ContaoFramework::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(RequestStack::class),
            true,
            0,
            $this->createMock(LoggerInterface::class),
        );
    }
}
