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

namespace Markocupic\CalendarEventBookingBundle\Tests\Functional\Controller\FrontendModule;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingMemberListController;
use Markocupic\CalendarEventBookingBundle\Request\EventUrlResolver;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;

/**
 * Functional test for EventBookingMemberListController::getBookings() against a real
 * in-memory SQLite database. Verifies the event (pid) scope, the optional booking
 * status filter (conditions OR-combined, the whole group AND-ed onto the event so the
 * event scope stays intact), the column whitelist and the ASC/DESC sanitisation.
 */
class EventBookingMemberListControllerFunctionalTest extends ContaoTestCase
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
                firstname TEXT NOT NULL DEFAULT "",
                confirmed INTEGER NOT NULL DEFAULT 0,
                canceled INTEGER NOT NULL DEFAULT 0,
                waitingList INTEGER NOT NULL DEFAULT 0,
                addedOn INTEGER NOT NULL DEFAULT 0
            )',
        );

        // Event 1
        $this->seed(['id' => 1, 'pid' => 1, 'firstname' => 'A', 'confirmed' => 1, 'addedOn' => 100]);
        $this->seed(['id' => 2, 'pid' => 1, 'firstname' => 'B', 'canceled' => 1, 'addedOn' => 200]);
        $this->seed(['id' => 3, 'pid' => 1, 'firstname' => 'C', 'waitingList' => 1, 'addedOn' => 300]);
        // Event 2 (must never leak into event 1 results)
        $this->seed(['id' => 4, 'pid' => 2, 'firstname' => 'D', 'confirmed' => 1, 'addedOn' => 400]);
    }

    public function testReturnsOnlyRowsOfTheEventOrderedByAddedOn(): void
    {
        $rows = $this->getBookings(1, [], ['addedOn::ASC']);

        $this->assertSame([1, 2, 3], $this->ids($rows));
    }

    public function testStatusFilterGroupIsAndedOntoEventScope(): void
    {
        // Single condition: only confirmed bookings of event 1 (event 2's confirmed row stays out).
        $this->assertSame([1], $this->ids($this->getBookings(1, ['confirmed::true'], ['id::ASC'])));

        // Two conditions are OR-combined, but the group stays AND-ed onto pid = 1.
        $this->assertSame(
            [1, 3],
            $this->ids($this->getBookings(1, ['confirmed::true', 'waitingList::true'], ['id::ASC'])),
        );
    }

    public function testUnknownFilterColumnIsIgnored(): void
    {
        // A bogus status column must be skipped, leaving only the event scope -> all rows.
        $this->assertSame([1, 2, 3], $this->ids($this->getBookings(1, ['doesNotExist::true'], ['id::ASC'])));
    }

    public function testOrderDirectionIsSanitisedAndColumnsWhitelisted(): void
    {
        // DESC is honoured.
        $this->assertSame([3, 2, 1], $this->ids($this->getBookings(1, [], ['addedOn::DESC'])));

        // Unknown order column is skipped; an invalid direction falls back to ASC.
        $this->assertSame([1, 2, 3], $this->ids($this->getBookings(1, [], ['nope::ASC', 'addedOn::sideways'])));
    }

    /**
     * @param array<string, int|string> $row
     */
    private function seed(array $row): void
    {
        $row += ['pid' => 1, 'firstname' => '', 'confirmed' => 0, 'canceled' => 0, 'waitingList' => 0, 'addedOn' => 0];

        $this->connection->insert('tl_calendar_events_member', $row);
    }

    /**
     * @param array<string> $arrWhere
     * @param array<string> $arrOrder
     *
     * @return array<int, array<string, mixed>>
     */
    private function getBookings(int $eventId, array $arrWhere, array $arrOrder): array
    {
        return (new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings'))
            ->invoke($this->createController(), $eventId, $arrWhere, $arrOrder)
        ;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, int>
     */
    private function ids(array $rows): array
    {
        return array_map('intval', array_column($rows, 'id'));
    }

    private function createController(): EventBookingMemberListController
    {
        return new EventBookingMemberListController(
            $this->connection,
            $this->createStub(FigureUtil::class),
            $this->createStub(EventUrlResolver::class),
            $this->createStub(ScopeMatcher::class),
        );
    }
}
