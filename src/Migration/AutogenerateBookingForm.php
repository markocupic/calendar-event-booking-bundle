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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Class AutogenerateBookingForm.
 */
class AutogenerateBookingForm extends AbstractMigration
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * AutogenerateBookingForm constructor.
     */
    public function __construct(string $projectDir, Connection $connection, ContaoFramework $framework)
    {
        $this->projectDir = $projectDir;
        $this->connection = $connection;
        $this->framework = $framework;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_form'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_form');

        if (isset($columns['iscalendareventbookingform'], $columns['alias'])) {
            $objForm = $this->connection->prepare('SELECT * FROM tl_form WHERE isCalendarEventBookingForm=? OR alias=?');
            $objForm->execute(['1', 'event-booking-form']);

            if (!$objForm->rowCount() > 0) {
                // Autogenerate form
                return true;
            }
        }

        return false;
    }

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
     * Auto generate event booking form.
     */
    private function autoGenerateBookingForm(): void
    {
        $sqlTlForm = file_get_contents($this->projectDir.'/vendor/markocupic/calendar-event-booking-bundle/src/Resources/autogenerate-form-sql/tl_form.sql');
        $sqlTlFormField = file_get_contents($this->projectDir.'/vendor/markocupic/calendar-event-booking-bundle/src/Resources/autogenerate-form-sql/tl_form_field.sql');

        // Set tstamp
        $sqlTlForm = str_replace('##tstamp##', time(), $sqlTlForm);

        // Insert into tl_form
        $this->connection->executeQuery($sqlTlForm);

        if (($intInsertId = $this->connection->lastInsertId()) > 0) {
            // Set tl_form.isCalendarEventBookingForm to true if field exists
            $schemaManager = $this->connection->getSchemaManager();
            $columns = $schemaManager->listTableColumns('tl_form');

            if (isset($columns['iscalendareventbookingform'])) {
                $stmt = $this->connection->prepare('UPDATE tl_form SET isCalendarEventBookingForm=? WHERE id=?');
                $stmt->execute(['1', $intInsertId]);
            }

            // Initialize the contao framework
            $this->framework->initialize();

            // Load dca tl_form_field
            Controller::loadDataContainer('tl_form_field');
            $strExtensions = $GLOBALS['TL_DCA']['tl_form_field']['fields']['extensions']['default'];

            // Set pid & tstamp
            $sqlTlFormField = str_replace('##pid##', $intInsertId, $sqlTlFormField);
            $sqlTlFormField = str_replace('##tstamp##', time(), $sqlTlFormField);
            $sqlTlFormField = str_replace('##extensions##', $strExtensions, $sqlTlFormField);

            // Insert into tl_form_field
            $this->connection->executeQuery($sqlTlFormField);
        }
    }
}
