<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingMemberListModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingUnsubscribeFromEventModuleController;

/**
 * Class Migrations.
 */
class Migrations extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Migration constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        if (isset($columns['type'])) {
            $doMigration = false;

            $objDb = $this->connection->prepare('SELECT * FROM tl_module WHERE type=?');
            $objDb->execute(['calendar_event_booking_member_list']);

            if ($objDb->rowCount() > 0) {
                $doMigration = true;
            }

            $objDb = $this->connection->prepare('SELECT * FROM tl_module WHERE type=?');
            $objDb->execute(['unsubscribefromevent']);

            if ($objDb->rowCount() > 0) {
                $doMigration = true;
            }

            $objDb = $this->connection->prepare('SELECT * FROM tl_module WHERE type=?');
            $objDb->execute(['eventbooking']);

            if ($objDb->rowCount() > 0) {
                $doMigration = true;
            }

            if ($doMigration) {
                // Run migration script
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $arrMessage = [];

        $type = CalendarEventBookingMemberListModuleController::TYPE;
        $stmt = $this->connection->prepare('UPDATE tl_module SET type=? WHERE type=?');
        $stmt->execute([$type, 'calendar_event_booking_member_list']);
        $arrMessage[] = 'Renamed frontend module type "calendar_event_booking_member_list" to "'.$type.'". Please rename your custom templates from "mod_calendar_event_booking_member_list.html5" to "mod_calendar_event_booking_member_list_module.html5".';

        $type = CalendarEventBookingUnsubscribeFromEventModuleController::TYPE;
        $stmt = $this->connection->prepare('UPDATE tl_module SET type=? WHERE type=?');
        $stmt->execute([$type, 'unsubscribefromevent']);
        $arrMessage[] = 'Renamed frontend module type "unsubscribefromevent" to "'.$type.'". Please rename your custom templates from "mod_unsubscribefromevent.html5" to "mod_calendar_event_booking_unsubscribe_from_event_module.html5".';

        $type = CalendarEventBookingEventBookingModuleController::TYPE;
        $stmt = $this->connection->prepare('UPDATE tl_module SET type=? WHERE type=?');
        $stmt->execute([$type, 'eventbooking']);
        $arrMessage[] = 'Renamed frontend module type "eventbooking" to "'.$type.'". Please rename your custom templates from "mod_eventbooking.html5" to "mod_calendar_event_booking_event_booking_module.html5".';

        return new MigrationResult(
            true,
            implode(' ', $arrMessage)
        );
    }
}
