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

use Contao\Controller;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Database;
use Doctrine\DBAL\Connection;

/**
 * Class AutogenerateBookingForm
 *
 * @package Markocupic\CalendarEventBookingBundle\Migration
 */
class AutogenerateBookingForm extends AbstractMigration
{

    /** @var string */
    private $projectDir;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * AutogenerateBookingForm constructor.
     *
     * @param string $projectDir
     * @param Connection $connection
     */
    public function __construct(string $projectDir, Connection $connection)
    {

        $this->projectDir = $projectDir;
        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    public function shouldRun(): bool
    {

        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_form']))
        {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_form');

        if (isset($columns['iscalendareventbookingform']) && isset($columns['formid']))
        {
            $objForm = Database::getInstance()->prepare('SELECT * FROM tl_form WHERE isCalendarEventBookingForm=? OR alias=?')->execute('1', 'event-booking-form');
            if (!$objForm->numRows)
            {
                // Autogenerate form
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

        // Auto generate event booking form, if it doesn't exists
        $this->autoGenerateBookingForm();

        return new MigrationResult(
            true,
            'Auto generated event booking form sample. Please check out the form generator in the contao backend.'
        );
    }

    /**
     * Auto generate event booking form
     */
    private function autoGenerateBookingForm(): void
    {

        $sqlTlForm = file_get_contents($this->projectDir . '/vendor/markocupic/calendar-event-booking-bundle/src/Resources/autogenerate-form-sql/tl_form.sql');
        $sqlTlFormField = file_get_contents($this->projectDir . '/vendor/markocupic/calendar-event-booking-bundle/src/Resources/autogenerate-form-sql/tl_form_field.sql');

        // Set tstamp
        $sqlTlForm = str_replace('##tstamp##', time(), $sqlTlForm);

        // Insert into tl_form
        $objInsertStmt1 = Database::getInstance()->query($sqlTlForm);
        if (($intInsertId = $objInsertStmt1->insertId) > 0)
        {
            // Set tl_form.isCalendarEventBookingForm to true if field exists
            if (Database::getInstance()->fieldExists('isCalendarEventBookingForm', 'tl_form'))
            {
                $set = [
                    'isCalendarEventBookingForm' => '1',
                ];
                Database::getInstance()->prepare('UPDATE tl_form %s WHERE id=?')->set($set)->execute($intInsertId);
            }

            // Load dca tl_form_field
            Controller::loadDataContainer('tl_form_field');
            $strExtensions = $GLOBALS['TL_DCA']['tl_form_field']['fields']['extensions']['default'];

            // Set pid & tstamp
            $sqlTlFormField = str_replace('##pid##', $intInsertId, $sqlTlFormField);
            $sqlTlFormField = str_replace('##tstamp##', time(), $sqlTlFormField);
            $sqlTlFormField = str_replace('##extensions##', $strExtensions, $sqlTlFormField);

            // Insert into tl_form_field
            Database::getInstance()->query($sqlTlFormField);
        }
    }
}
