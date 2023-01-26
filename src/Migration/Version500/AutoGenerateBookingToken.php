<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Migration\Version500;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Ramsey\Uuid\Uuid;

class AutoGenerateBookingToken extends AbstractMigration
{
    private const MIGRATION_TEXT = "Auto generate missing booking tokens in data table 'tl_calendar_events_member'.";

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

        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if ($schemaManager->tablesExist(['tl_calendar_events_member'])) {
            $columns = $schemaManager->listTableColumns('tl_calendar_events_member');

            if (isset($columns['bookingtoken'])) {
                $count = $this->connection->fetchOne(
                    'SELECT COUNT(id) FROM tl_calendar_events_member WHERE bookingToken = ?',
                    [''],
                );

                if ($count > 0) {
                    $doMigration = true;
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
        $arrIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_calendar_events_member WHERE bookingToken = ?',
            [''],
        );

        if (!empty($arrIds)) {
            foreach ($arrIds as $id) {
                $bookingToken = Uuid::uuid4()->toString();
                $set = ['bookingToken' => $bookingToken];
                $this->connection->update('tl_calendar_events_member', $set, ['id' => $id]);
            }
        }

        return new MigrationResult(
            true,
            self::MIGRATION_TEXT
        );
    }
}
