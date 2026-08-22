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

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Fills the settlement columns of tl_calendar_events_payment for the payments
 * that were recorded before those columns existed.
 *
 * Those rows carry a single currency for all their amounts, and for every one of
 * them that currency is both the charged and the settled one - a payment settled
 * in a different currency would have produced figures that did not belong
 * together, which is the reason the columns were introduced. So settlement
 * currency := currencyCode, settlement gross := grossAmount and rate := 1 is not
 * a guess about them, it is what they already meant.
 *
 * It is a guess about anything written afterwards, which is why shouldRun() stops
 * as soon as the column holds a single value: from that moment on an empty
 * settlement currency is a statement ("the provider did not report it"), and
 * overwriting it would replace a known gap with an invented certainty.
 */
class SettlementCurrencyCodeMigration extends AbstractMigration
{
    private const TABLE = 'tl_calendar_events_payment';

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns(self::TABLE);

        // Doctrine lowercases the column names it reports.
        if (
            !isset(
                $columns[strtolower('settlementCurrencyCode')],
                $columns[strtolower('settlementGrossAmount')],
                $columns[strtolower('exchangeRate')],
                $columns[strtolower('currencyCode')],
            )
        ) {
            return false;
        }

        // Anything set means the column is in use - see the class comment.
        $inUse = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM '.self::TABLE.' WHERE settlementCurrencyCode != ?',
            [''],
        );

        if ($inUse > 0) {
            return false;
        }

        $pending = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM '.self::TABLE.' WHERE settlementCurrencyCode = ? AND currencyCode != ?',
            ['', ''],
        );

        return $pending > 0;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        $count = $this->connection->executeStatement(
            'UPDATE '.self::TABLE.'
                SET settlementCurrencyCode = currencyCode,
                    settlementGrossAmount = grossAmount,
                    exchangeRate = 1
                WHERE settlementCurrencyCode = ? AND currencyCode != ?',
            ['', ''],
        );

        return new MigrationResult(
            true,
            \sprintf(
                '%s: Settled %d existing payment(s) into the currency the customer was charged in.',
                self::TABLE,
                $count,
            ),
        );
    }
}
