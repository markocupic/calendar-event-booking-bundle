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

namespace Markocupic\CalendarEventBookingBundle\Migration\Version600;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class TicketAmountMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $doMigration = false;

        $schemaManager = $this->connection->createSchemaManager();

        // If the database table itself does not exist, we should do nothing
        if (!$schemaManager->tablesExist(['tl_calendar_events_member']) || !$schemaManager->tablesExist(['tl_calendar_events'])) {
            return false;
        }

        $columnsParent = $schemaManager->listTableColumns('tl_calendar_events');
        $columnsChild = $schemaManager->listTableColumns('tl_calendar_events_member');

        if (isset($columnsParent['id'], $columnsParent[strtolower('includeEscortsWhenCalculatingRegCount')], $columnsChild['id'], $columnsChild['pid'], $columnsChild['escorts'], $columnsChild[strtolower('ticketAmount')])) {
            $count = $this->connection->fetchOne(
                '
                    SELECT
                        COUNT(m.id)
                    FROM
                        tl_calendar_events_member m
                    JOIN
                        tl_calendar_events e
                    ON
                        m.pid = e.id
                    WHERE
                        m.ticketAmount = 1
                    AND
                        m.escorts > 0
                    AND
                        e.includeEscortsWhenCalculatingRegCount = 1
                      ',
            );

            if ($count > 0) {
                $doMigration = true;
            }
        }

        return $doMigration;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        $bookings = $this->connection->fetchAllAssociative(
            '
            SELECT
                m.id, m.ticketAmount, m.escorts
            FROM
                tl_calendar_events_member m
            JOIN
                tl_calendar_events e
            ON
                m.pid = e.id
            WHERE
                m.ticketAmount = 1
            AND
                m.escorts > 0
            AND
                e.includeEscortsWhenCalculatingRegCount = 1
              ',
        );

        if (!empty($bookings)) {
            foreach ($bookings as $booking) {
                $set = [
                    'ticketAmount' => 1 + (int) $booking['escorts'],
                    'escorts' => 0,
                ];
                $this->connection->update('tl_calendar_events_member', $set, ['id' => $booking['id']]);
            }
        }

        return new MigrationResult(
            true,
            'tl_calendar_events_member: Added the number of accompanying persons to the number of tickets booked.',
        );
    }
}
