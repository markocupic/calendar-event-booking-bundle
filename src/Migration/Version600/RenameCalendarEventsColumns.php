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

class RenameCalendarEventsColumns extends AbstractMigration
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

        $migrationData = $this->getMigrationData();

        foreach ($migrationData as $item) {
            if ($schemaManager->tablesExist([$item['table']])) {
                $columns = $schemaManager->listTableColumns($item['table']);

                if (isset($columns[strtolower($item['old'])]) && !isset($columns[strtolower($item['new'])])) {
                    $doMigration = true;
                    break;
                }
            }
        }

        return $doMigration;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        $messages = [];

        $schemaManager = $this->connection->createSchemaManager();

        $migrationData = $this->getMigrationData();

        foreach ($migrationData as $item) {
            if ($schemaManager->tablesExist([$item['table']])) {
                $columns = $schemaManager->listTableColumns($item['table']);

                if (isset($columns[strtolower($item['old'])]) && !isset($columns[strtolower($item['new'])])) {
                    // Quote identifiers
                    $table = $this->connection->quoteIdentifier($item['table']);
                    $old = $this->connection->quoteIdentifier($item['old']);
                    $new = $this->connection->quoteIdentifier($item['new']);

                    $sql = \sprintf('ALTER TABLE %s CHANGE %s %s %s', $table, $old, $new, $item['type']);
                    $this->connection->executeQuery($sql);

                    // Show message
                    $messages[] = \sprintf('Renamed column %s.%s to %s.%s', $item['table'], $item['old'], $item['table'], $item['new']);
                }
            }
        }

        return new MigrationResult(
            true,
            implode(' ', $messages),
        );
    }

    private function getMigrationData(): array
    {
        return [
            [
                'table' => 'tl_calendar_events',
                'old' => 'addBookingForm',
                'new' => 'enableBookingForm',
                'type' => "TINYINT(1) NOT NULL DEFAULT '0'",
            ],
            [
                'table' => 'tl_calendar_events',
                'old' => 'maxEscortsPerMember',
                'new' => 'maxEscortsPerBooking',
                'type' => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
            ],
            [
                'table' => 'tl_calendar_events',
                'old' => 'minMembers',
                'new' => 'minBookings',
                'type' => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
            ],
            [
                'table' => 'tl_calendar_events',
                'old' => 'maxMembers',
                'new' => 'maxBookings',
                'type' => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
            ],
        ];
    }
}
