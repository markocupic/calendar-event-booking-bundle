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

use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Markocupic\CalendarEventBookingBundle\Cron\DeleteExpiredBookingsCron;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Functional test for the auto-delete cron run against a real in-memory SQLite
 * database: only rows flagged expired = 1 must be deleted, everything else stays.
 */
class DeleteExpiredBookingsCronFunctionalTest extends ContaoTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement(
            'CREATE TABLE tl_calendar_events_member (
                id INTEGER PRIMARY KEY,
                expired INTEGER NOT NULL DEFAULT 0
            )',
        );
    }

    public function testProcessAutoDeleteRemovesOnlyExpiredBookings(): void
    {
        $this->connection->insert('tl_calendar_events_member', ['id' => 1, 'expired' => 1]);
        $this->connection->insert('tl_calendar_events_member', ['id' => 2, 'expired' => 0]);
        $this->connection->insert('tl_calendar_events_member', ['id' => 3, 'expired' => 1]);

        $this->createCron()->processAutoDelete();

        $remaining = array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM tl_calendar_events_member ORDER BY id'));

        $this->assertSame([2], $remaining);
    }

    public function testProcessAutoDeleteDoesNothingWhenDisabled(): void
    {
        $this->connection->insert('tl_calendar_events_member', ['id' => 1, 'expired' => 1]);

        $this->createCron(enabled: false)->processAutoDelete();

        $this->assertSame([1], array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM tl_calendar_events_member')));
    }

    private function createCron(bool $enabled = true): DeleteExpiredBookingsCron
    {
        // findById returns a lightweight non-null model so the cron proceeds to delete;
        // the AutoDeleteExpiredBookingEvent keeps its default shouldDelete = true.
        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->method('findById')
            ->willReturnCallback(fn (int $id): CalendarEventsMemberModel => $this->mockClassWithProperties(CalendarEventsMemberModel::class, ['id' => $id]))
        ;

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->method('dispatch')
            ->willReturnArgument(0)
        ;

        return new DeleteExpiredBookingsCron(
            $this->connection,
            $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]),
            $dispatcher,
            $this->createMock(RequestStack::class),
            $enabled,
            $this->createMock(LoggerInterface::class),
        );
    }
}
