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

namespace Markocupic\CalendarEventBookingBundle\Migration;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

class AutogenerateBookingForm extends AbstractMigration
{
    private const MIGRATION_TEXT = 'Auto generated event booking form sample. Please check the form generator in the Contao backend.';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // If the database table itself doesn't exist, we should do nothing
        if (!$schemaManager->tablesExist(['tl_form'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_form');

        if (!isset($columns['iscalendareventbookingform'])) {
            return false;
        }

        $count = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM tl_form WHERE isCalendarEventBookingForm = ? OR alias = ?',
            [1, 'event-booking-form'],
        );

        if (!$count > 0) {
            // Autogenerate form
            return true;
        }

        // Check if the form fields `email`, `waitingList` and `ticketAmount` exist
        $arrFormFields = ['email', 'ticketAmount', 'waitingList'];
        $formIDS = $this->connection->fetchFirstColumn('SELECT id FROM tl_form WHERE isCalendarEventBookingForm = 1');

        foreach ($formIDS as $formID) {
            foreach ($arrFormFields as $formField) {
                if (false === $this->connection->fetchOne('SELECT id FROM tl_form_field WHERE name= ? AND pid = ?', [$formField, $formID])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM tl_form WHERE isCalendarEventBookingForm = ? OR alias = ?',
            [1, 'event-booking-form'],
        );

        if (!$count > 0) {
            // Auto generate the event booking form if it doesn't exist
            $this->autoGenerateBookingForm();
        }

        // Append the mandatory form fields `email`, `waitingList` and `ticketAmount` to
        // the event booking form if they don't exist.
        $this->addMandatoryFormFields(['email', 'ticketAmount', 'waitingList']);

        return new MigrationResult(
            true,
            self::MIGRATION_TEXT,
        );
    }

    public function getMinSorting(int $formId): int
    {
        if (false === $this->connection->fetchOne('SELECT id FROM tl_form WHERE id = ?', [$formId])) {
            throw new \Exception(\sprintf('Form with id %d does not exist.', $formId));
        }

        $count = $this->connection->fetchOne('SELECT COUNT(id) FROM tl_form_field WHERE pid = ?', [$formId]);

        if (0 === $count) {
            return 128;
        }

        $min = $this->connection->fetchOne('SELECT MIN(sorting) FROM tl_form_field WHERE pid = ?', [$formId]);

        if (0 !== $min % 2 || $min < 128) {
            $this->resortItems($formId);

            return $this->getMinSorting($formId);
        }

        return $min;
    }

    public function getMaxSorting(int $formId): int
    {
        if (false === $this->connection->fetchOne('SELECT id FROM tl_form WHERE id = ?', [$formId])) {
            throw new \Exception(\sprintf('Form with id %d does not exist.', $formId));
        }

        $count = $this->connection->fetchOne('SELECT COUNT(id) FROM tl_form_field WHERE pid = ?', [$formId]);

        if (0 === $count) {
            return 0;
        }

        $max = $this->connection->fetchOne('SELECT MAX(sorting) FROM tl_form_field WHERE pid = ?', [$formId]);

        if (0 !== $max % 2 || $max < 1) {
            $this->resortItems($formId);

            return $this->getMaxSorting($formId);
        }

        return $max;
    }

    private function addMandatoryFormFields(array $arrFormFields): void
    {
        $arrYaml = $this->getFormConfigFromYaml();

        $formFieldsConfig = [];

        foreach ($arrYaml['form_fields'] as $ff) {
            if (!isset($ff['name'])) {
                continue;
            }
            $formFieldsConfig[$ff['name']] = $ff;
        }

        $formIDS = $this->connection->fetchFirstColumn('SELECT id FROM tl_form WHERE isCalendarEventBookingForm = 1');

        foreach ($formIDS as $formID) {
            foreach ($arrFormFields as $formField) {
                $result = $this->connection->fetchOne(
                    'SELECT id FROM tl_form_field WHERE name = ? AND pid = ?',
                    [$formField, $formID],
                );

                if (false === $result) {
                    // Add the missing form field to the booking form.
                    $this->addFormField($formID, $formFieldsConfig[$formField], $this->getMaxSorting($formID) + 128);
                }
            }
        }
    }

    private function addFormField(int $formId, array $formField, int $sorting = 0): void
    {
        if (false === $this->connection->fetchOne('SELECT id FROM tl_form WHERE id = ?', [$formId])) {
            throw new \Exception(\sprintf('Form with id %d does not exist.', $formId));
        }

        $arrFormField = array_map(static fn ($value) => \is_array($value) ? serialize($value) : $value, $formField);

        // Set class
        if (isset($arrFormField['name'], $arrFormField['class']) && str_contains($arrFormField['class'], '%s')) {
            $arrFormField['class'] = \sprintf($arrFormField['class'], $arrFormField['name']);
        }

        $arrFormField['pid'] = $formId;
        $arrFormField['tstamp'] = time();
        $arrFormField['sorting'] = $sorting;

        // Create a new form field
        $ff = new FormFieldModel();
        $ff->setRow($arrFormField);
        $ff->save();

        $this->resortItems($formId);
    }

    /**
     * Auto generate event booking form.
     */
    private function autoGenerateBookingForm(): void
    {
        // Initialize the contao framework
        $this->framework->initialize();
        $arrYaml = $this->getFormConfigFromYaml();

        $arrForm = $arrYaml['form'];
        $arrFormFields = $arrYaml['form_fields'];

        $form = new FormModel();
        $arrForm['tstamp'] = time();
        $arrForm['title'] = \is_string($arrForm['title']) ? $this->encodeInput($arrForm['title']) : '';

        $form->setRow($arrForm);
        $form->save();

        $sorting = $this->getMaxSorting($form->id) + 128;

        foreach ($arrFormFields as $ff) {
            $this->addFormField($form->id, $ff, $sorting);
            $sorting += 128;
        }
    }

    private function getFormConfigFromYaml(): array
    {
        return Yaml::parseFile(Path::join($this->projectDir, 'vendor/markocupic/calendar-event-booking-bundle/sql/form-generator.yaml'));
    }

    private function resortItems(int $formId): void
    {
        if (false === $this->connection->fetchOne('SELECT id FROM tl_form WHERE id = ?', [$formId])) {
            throw new \Exception(\sprintf('Form with id %d does not exist.', $formId));
        }

        $arrIDS = $this->connection->fetchFirstColumn('SELECT id FROM tl_form_field WHERE pid = ? ORDER BY sorting ASC, id ASC', [$formId]);
        $count = 2;

        foreach ($arrIDS as $id) {
            $sorting = 128 * $count;
            $this->connection->update('tl_form_field', ['sorting' => $sorting], ['id' => $id]);
            ++$count;
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
