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
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingListController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventUnsubscribeController;

class RenameFrontendModuleType extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getName(): string
    {
        return 'Calendar Event Booking Bundle Version 5 update: Rename frontend module type.';
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
                    ['calendar_event_booking_member_list'],
                );

                if ($count > 0) {
                    $doMigration = true;
                }

                // #2 Rename frontend module type
                $count = $this->connection->fetchOne(
                    'SELECT COUNT(id) FROM tl_module WHERE type = ?',
                    ['unsubscribefromevent'],
                );

                if ($count > 0) {
                    $doMigration = true;
                }

                // #3 Rename frontend module type
                $count = $this->connection->fetchOne(
                    'SELECT COUNT(id) FROM tl_module WHERE type = ?',
                    ['eventbooking'],
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
        $arrMessage = [];

        // #1 Rename frontend module type
        $count = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM tl_module WHERE type = ?',
            ['calendar_event_booking_member_list'],
        );

        if ($count > 0) {
            $set = [
                'type' => EventBookingListController::TYPE,
            ];
            $this->connection->update('tl_module', $set, ['type' => 'calendar_event_booking_member_list']);
            $arrMessage[] = 'Renamed frontend module type "calendar_event_booking_member_list" to "'.$set['type'].'". Please rename your custom templates from "mod_calendar_event_booking_member_list.html5" to "mod_event_booking_list.html5".';
        }

        // #2 Rename frontend module type
        $count = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM tl_module WHERE type = ?',
            ['unsubscribefromevent'],
        );

        if ($count > 0) {
            $set = [
                'type' => EventUnsubscribeController::TYPE,
            ];
            $this->connection->update('tl_module', $set, ['type' => 'unsubscribefromevent']);
            $arrMessage[] = 'Renamed frontend module type "unsubscribefromevent" to "'.$set['type'].'". Please rename your custom templates from "mod_unsubscribefromevent.html5" to "mod_event_unsubscribe.html5".';
        }

        // #3 Rename frontend module type
        $count = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM tl_module WHERE type = ?',
            ['eventbooking'],
        );

        if ($count > 0) {
            $set = [
                'type' => EventBookingController::TYPE,
            ];
            $this->connection->update('tl_module', $set, ['type' => 'eventbooking']);
            $arrMessage[] = 'Renamed frontend module type "eventbooking" to "'.$set['type'].'". Please rename your custom templates from "mod_eventbooking.html5" to "mod_event_booking_form.html5".';
        }

        return new MigrationResult(
            true,
            implode(' ', $arrMessage)
        );
    }
}
