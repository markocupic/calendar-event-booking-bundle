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

class RenameFrontendModuleType extends AbstractMigration
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

        // If the database table itself does not exist we should do nothing
        if ($schemaManager->tablesExist(['tl_module'])) {
            $columns = $schemaManager->listTableColumns('tl_module');

            if (isset($columns['type'])) {
                // #1 Rename frontend module type
                $count = $this->connection->fetchOne(
                    'SELECT COUNT(id) FROM tl_module WHERE type = ?',
                    ['calendar_event_booking_event_booking_module'],
                );

                if ($count > 0) {
                    $doMigration = true;
                }

                // #2 Rename frontend module type
                $count = $this->connection->fetchOne(
                    'SELECT COUNT(id) FROM tl_module WHERE type = ?',
                    ['calendar_event_booking_member_list_module'],
                );

                if ($count > 0) {
                    $doMigration = true;
                }

                // #3 Rename frontend module type
                $count = $this->connection->fetchOne(
                    'SELECT COUNT(id) FROM tl_module WHERE type = ?',
                    ['calendar_event_booking_unsubscribe_from_event_module'],
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
        $messages = [];

        // #1 Rename frontend module type
        $old = 'calendar_event_booking_event_booking_module';
        $new = 'event_booking_form';
        $set = [
            'type' => $new,
        ];

        $intAffected = $this->connection->update('tl_module', $set, ['type' => $old]);

        if ($intAffected) {
            $messages[] = "Renamed frontend module type '$old' to '$new'. Please rename your customized frontend module templates accordingly.";
        }

        // #2 Rename frontend module type
        $old = 'calendar_event_booking_member_list_module';
        $new = 'event_booking_member_list';
        $set = [
            'type' => $new,
        ];

        $intAffected = $this->connection->update('tl_module', $set, ['type' => $old]);

        if ($intAffected) {
            $messages[] = "Renamed frontend module type '$old' to '$new'. Please rename your customized frontend module templates accordingly.";
        }

        // #3 Rename frontend module type
        $old = 'calendar_event_booking_unsubscribe_from_event_module';
        $new = 'event_booking_unsubscribe';
        $set = [
            'type' => $new,
        ];

        $intAffected = $this->connection->update('tl_module', $set, ['type' => $old]);

        if ($intAffected) {
            $messages[] = "Renamed frontend module type '$old' to '$new'. Please rename your customized frontend module templates accordingly.";
        }

        return new MigrationResult(
            true,
            implode(' ', $messages),
        );
    }
}
