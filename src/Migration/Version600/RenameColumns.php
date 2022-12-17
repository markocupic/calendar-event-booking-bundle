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

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class RenameColumns extends AbstractMigration
{
    private const STRING_TO_INT_CONVERSION = 'string_to_int_conversion';
    private const ALTERATION_TYPE_RENAME_COLUMN = 'alteration_type_rename_column';
    private const NULL_TO_0_CONVERSION = 'null_to_0_conversion';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Calendar Event Booking Bundle Version 6 update: Rename columns.';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $doMigration = false;
        $schemaManager = $this->connection->createSchemaManager();
        $arrAlterations = $this->getAlterationData();

        foreach ($arrAlterations as $arrAlteration) {
            $type = $arrAlteration['type'];

            // Version 600 migration: "Convert NULL to 0"
            if (self::NULL_TO_0_CONVERSION === $type) {
                $strTable = $arrAlteration['table'];
                $strField = $arrAlteration['field'];

                // If the database table itself does not exist we should do nothing
                if ($schemaManager->tablesExist([$strTable])) {
                    $columns = $schemaManager->listTableColumns($strTable);

                    if (isset($columns[strtolower($arrAlteration['field'])])) {
                        $result = $this->connection->fetchOne('SELECT id FROM '.$strTable.' WHERE '.$strField.' IS NULL');

                        if ($result) {
                            $doMigration = true;
                        }
                    }
                }
            }

            // Version 600 migration: "Convert empty string to 0"
            if (self::STRING_TO_INT_CONVERSION === $type) {
                $strTable = $arrAlteration['table'];
                // If the database table itself does not exist we should do nothing
                if ($schemaManager->tablesExist([$strTable])) {
                    $columns = $schemaManager->listTableColumns($strTable);

                    if (isset($columns[strtolower($arrAlteration['field'])])) {
                        $result = $this->connection->fetchOne('SELECT id FROM '.$strTable.' WHERE '.$arrAlteration['field'].' = ?', ['']);

                        if ($result) {
                            $doMigration = true;
                        }
                    }
                }
            }

            // Version 600 migration: "Rename columns"
            if (self::ALTERATION_TYPE_RENAME_COLUMN === $type) {
                $strTable = $arrAlteration['table'];
                // If the database table itself does not exist we should do nothing
                if ($schemaManager->tablesExist([$strTable])) {
                    $columns = $schemaManager->listTableColumns($strTable);

                    if (isset($columns[strtolower($arrAlteration['old'])]) && !isset($columns[strtolower($arrAlteration['new'])])) {
                        $doMigration = true;
                    }
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
        $resultMessages = [];

        $schemaManager = $this->connection->createSchemaManager();

        $arrAlterations = $this->getAlterationData();

        foreach ($arrAlterations as $arrAlteration) {
            $type = $arrAlteration['type'];

            // Version 600 migration: "Convert NULL to 0"
            if (self::NULL_TO_0_CONVERSION === $type) {
                $strTable = $arrAlteration['table'];
                $strField = $arrAlteration['field'];

                // If the database table itself does not exist we should do nothing
                if ($schemaManager->tablesExist([$strTable])) {
                    $columns = $schemaManager->listTableColumns($strTable);

                    if (isset($columns[strtolower($arrAlteration['field'])])) {
                        $result = $this->connection->fetchOne('SELECT id FROM '.$strTable.' WHERE '.$strField.' IS NULL');

                        if ($result) {
                            $this->connection->executeStatement('UPDATE '.$strTable.' SET '.$strField.' = ? WHERE '.$strField.' IS NULL', ['0']);
                            $resultMessages[] = sprintf(
                                'Convert NULL to "0" in column %s.%s. ',
                                $strTable,
                                $strField,
                            );
                        }
                    }
                }
            }

            // Version 600 migration: "Convert empty string to 0"
            if (self::STRING_TO_INT_CONVERSION === $type) {
                $strTable = $arrAlteration['table'];
                // If the database table itself does not exist we should do nothing
                if ($schemaManager->tablesExist([$strTable])) {
                    $columns = $schemaManager->listTableColumns($strTable);

                    if (isset($columns[strtolower($arrAlteration['field'])])) {
                        $result = $this->connection->fetchOne('SELECT id FROM '.$strTable.' WHERE '.$arrAlteration['field'].' = ?', ['']);

                        if ($result) {
                            $set = [
                                $arrAlteration['field'] => $arrAlteration['field_value_new'],
                            ];
                            // Convert ''to '0'
                            $this->connection->update($arrAlteration['table'], $set, [$arrAlteration['field'] => $arrAlteration['field_value_old']]);
                            $resultMessages[] = sprintf(
                                'Convert empty string to "0" in column %s.%s. ',
                                $strTable,
                                $arrAlteration['field'],
                            );
                        }
                    }
                }
            }

            // Version 600 migration: "Rename columns"
            if (self::ALTERATION_TYPE_RENAME_COLUMN === $type) {
                $strTable = $arrAlteration['table'];

                if ($schemaManager->tablesExist([$strTable])) {
                    $columns = $schemaManager->listTableColumns($strTable);

                    if (isset($columns[strtolower($arrAlteration['old'])]) && !isset($columns[strtolower($arrAlteration['new'])])) {
                        $strQuery = 'ALTER TABLE `'.$strTable.'` CHANGE `'.$arrAlteration['old'].'` `'.$arrAlteration['new'].'` '.$arrAlteration['sql'];

                        $this->connection->executeQuery($strQuery);

                        $resultMessages[] = sprintf(
                            'Rename column %s.%s to %s.%s. ',
                            $strTable,
                            $arrAlteration['old'],
                            $strTable,
                            $arrAlteration['new'],
                        );
                    }
                }
            }
        }

        return $this->createResult(true, $resultMessages ? implode("\n", $resultMessages) : null);
    }

    private function getAlterationData(): array
    {
        return [
            // tl_calendar_events
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events',
                'old' => 'enableNotificationCenter',
                'new' => 'activateBookingNotification',
                'sql' => 'char(1)',
            ],
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events',
                'old' => 'addBookingForm',
                'new' => 'activateBookingForm',
                'sql' => 'char(1)',
            ],
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events',
                'old' => 'enableDeregistration',
                'new' => 'activateDeregistration',
                'sql' => 'char(1)',
            ],
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events',
                'old' => 'eventBookingNotificationCenterIds',
                'new' => 'eventBookingNotification',
                'sql' => 'blob',
            ],
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events',
                'old' => 'includeEscortsWhenCalculatingRegCount',
                'new' => 'addEscortsToTotal',
                'sql' => 'char(1)',
            ],
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events',
                'old' => 'enableMultiBookingWithSameAddress',
                'new' => 'allowDuplicateEmail',
                'sql' => 'char(1)',
            ],
            // tl_calendar_events_member
            [
                'type' => self::STRING_TO_INT_CONVERSION,
                'table' => 'tl_calendar_events_member',
                'field_value_old' => '',
                'field_value_new' => '0',
                'field' => 'addedOn',
            ],
            [
                'type' => self::NULL_TO_0_CONVERSION,
                'table' => 'tl_calendar_events_member',
                'field' => 'escorts',
            ],
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_calendar_events_member',
                'old' => 'addedOn',
                'new' => 'dateAdded',
                'sql' => 'int(10)',
            ],
            // tl_module
            [
                'type' => self::ALTERATION_TYPE_RENAME_COLUMN,
                'table' => 'tl_module',
                'old' => 'calendarEventBookingMemberListPartialTemplate',
                'new' => 'cebb_memberListPartialTemplate',
                'sql' => 'varchar(128)',
            ],
        ];
    }
}
