<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Migration;

use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class AutogenerateBookingForm extends AbstractMigration
{
    private ContaoFramework $framework;
    private Connection $connection;
    private string $projectDir;

    // Adapters
    private Adapter $controller;

    public function __construct(ContaoFramework $framework, Connection $connection, string $projectDir)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        // Adapters
        $this->controller = $this->framework->getAdapter(Controller::class);
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_form'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_form');

        if (isset($columns['iscalendareventbookingform'], $columns['alias'])) {
            $count = $this->connection->fetchOne(
                'SELECT COUNT(id) FROM tl_form WHERE isCalendarEventBookingForm = ? OR alias = ?',
                ['1', 'event-booking-form']
            );

            if (!$count > 0) {
                // Autogenerate form
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        // Auto generate event booking form, if it doesn't exist
        $this->autoGenerateBookingForm();

        return new MigrationResult(
            true,
            'Auto generated event booking form sample. Please check out the form generator in the contao backend.'
        );
    }

    /**
     * Auto generate event booking form.
     *
     * @throws Exception
     */
    private function autoGenerateBookingForm(): void
    {
        // Initialize the contao framework
        $this->framework->initialize();

        $sqlTlForm = file_get_contents($this->projectDir.'/vendor/markocupic/calendar-event-booking-bundle/sql/tl_form.sql');
        $sqlTlFormField = file_get_contents($this->projectDir.'/vendor/markocupic/calendar-event-booking-bundle/sql/tl_form_field.sql');

        // Set tstamp
        $sqlTlForm = str_replace('##tstamp##', (string) time(), $sqlTlForm);

        // Insert into tl_form
        $this->connection->executeStatement($sqlTlForm);

        if (($intInsertId = $this->connection->lastInsertId()) > 0) {
            // Set tl_form.isCalendarEventBookingForm to true if field exists
            $schemaManager = $this->connection->getSchemaManager();
            $columns = $schemaManager->listTableColumns('tl_form');

            if (isset($columns['iscalendareventbookingform'])) {
                $set = [
                    'isCalendarEventBookingForm' => '1',
                ];

                $this->connection->update('tl_form', $set, ['id' => $intInsertId]);
            }

            // Load dca tl_form_field
            $this->controller->loadDataContainer('tl_form_field');

            $strExtensions = $GLOBALS['TL_DCA']['tl_form_field']['fields']['extensions']['default'] ?? '';

            // Set pid & tstamp
            $sqlTlFormField = str_replace('##pid##', $intInsertId, $sqlTlFormField);
            $sqlTlFormField = str_replace('##tstamp##', (string) time(), $sqlTlFormField);
            $sqlTlFormField = str_replace('##extensions##', $strExtensions, $sqlTlFormField);

            // Insert into tl_form_field
            $this->connection->executeStatement($sqlTlFormField);
        }
    }
}
