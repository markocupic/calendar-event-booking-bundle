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

namespace Markocupic\CalendarEventBookingBundle\Migration\Version600;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Yaml\Yaml;

class AutoGenerateBookingForm extends AbstractMigration
{
    private ContaoFramework $framework;
    private Connection $connection;
    private string $projectDir;

    public function __construct(ContaoFramework $framework, Connection $connection, string $projectDir)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->projectDir = $projectDir;
    }

    public function getName(): string
    {
        return 'Calendar Event Booking Bundle Version 6 update: Auto generate booking form.';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

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
     */
    private function autoGenerateBookingForm(): void
    {
        // Initialize the contao framework
        $this->framework->initialize();
        $arrYaml = Yaml::parseFile($this->projectDir.'/vendor/markocupic/calendar-event-booking-bundle/sql/form-generator.yaml');
        $arrForm = $arrYaml['form'];
        $arrFormFields = $arrYaml['form']['form_fields'];

        // Create new form
        $form = new FormModel();
        $arrForm['tstamp'] = time();
        $arrForm['title'] = \is_string($arrForm['title']) ? $this->encodeInput($arrForm['title']) : '';

        $form->setRow($arrForm);
        $form->save();

        foreach ($arrFormFields as $ff) {
            $arrFormField = array_map(static fn ($value) => \is_array($value) ? serialize($value) : $value, $ff);
            // Set class
            if (isset($arrFormField['name'], $arrFormField['class']) && false !== strpos($arrFormField['class'], '%s')) {
                $arrFormField['class'] = sprintf($arrFormField['class'], $arrFormField['name']);
            }

            $arrFormField['pid'] = $form->id;
            $arrFormField['tstamp'] = time();

            // Create new form field
            $formField = new FormFieldModel();
            $formField->setRow($arrFormField);
            $formField->save();
        }
    }

    private function encodeInput(string $varValue): string
    {
        $varValue = Input::decodeEntities($varValue);
        $varValue = Input::xssClean($varValue, true);
        $varValue = Input::stripTags($varValue);

        return Input::encodeSpecialChars($varValue);
    }
}
