<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 *
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Database;
use Doctrine\DBAL\Connection;

/**
 * Class Migrations
 *
 * @package Markocupic\CalendarEventBookingBundle\Migration
 */
class Migrations extends AbstractMigration
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Migration constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {

        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    public function shouldRun(): bool
    {

        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_module']))
        {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        if (isset($columns['type']))
        {
            $doMigration = false;
            $objDb = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE type=?')->execute('calendar_event_booking_member_list');
            if ($objDb->numRows)
            {
                $doMigration = true;
            }

            $objDb = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE type=?')->execute('unsubscribefromevent');
            if ($objDb->numRows)
            {
                $doMigration = true;
            }

            $objDb = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE type=?')->execute('eventbooking');
            if ($objDb->numRows)
            {
                $doMigration = true;
            }

            if ($doMigration)
            {
                // Run migration script
                return true;
            }
        }
        return false;
    }

    /**
     * @return MigrationResult
     */
    public function run(): MigrationResult
    {
        $arrMessage = [];

        Database::getInstance()->prepare('UPDATE tl_module SET type=? WHERE type=?')->execute('calendar_event_booking_member_list_module', 'calendar_event_booking_member_list');
        $arrMessage[] = 'Renamed frontend module type "calendar_event_booking_member_list" to "calendar_event_booking_member_list_module". Please rename your custom templates from "mod_calendar_event_booking_member_list.html5" to "mod_calendar_event_booking_member_list_module.html5".';

        Database::getInstance()->prepare('UPDATE tl_module SET type=? WHERE type=?')->execute('calendar_event_booking_unsubscribe_from_event_module', 'unsubscribefromevent');
        $arrMessage[] = 'Renamed frontend module type "unsubscribefromevent" to "calendar_event_booking_unsubscribe_from_event_module". Please rename your custom templates from "mod_unsubscribefromevent" to "mod_calendar_event_booking_unsubscribe_from_event_module".';

        Database::getInstance()->prepare('UPDATE tl_module SET type=? WHERE type=?')->execute('calendar_event_booking_event_booking_module', 'eventbooking');
        $arrMessage[] = 'Renamed frontend module type "eventbooking" to "calendar_event_booking_event_booking_module". Please rename your custom templates from "mod_eventbooking.html5" to "mod_calendar_event_booking_event_booking_module.html5".';

        return new MigrationResult(
            true,
            implode(' ', $arrMessage)
        );
    }

}
