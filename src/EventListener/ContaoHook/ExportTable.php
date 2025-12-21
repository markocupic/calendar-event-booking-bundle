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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\EventListener\ContaoHooks\ListenerInterface;

#[AsHook(ExportTable::HOOK, priority: 1000)]
final class ExportTable implements ListenerInterface
{
    public const HOOK = 'exportTable';

    public static bool $disableHook = false;

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(string $strFieldName, $varValue, string $strTableName, array $arrDataRecord, array $arrDca, Config $config)
    {
        if (CalendarEventsModel::getTable() === $strTableName) {
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            if ('pid' === $strFieldName) {
                $event = $calendarEventsModelAdapter->findById($varValue);

                if (null !== $event) {
                    $varValue = $event->title;
                }
            }
        }

        return $varValue;
    }

    public static function disableHook(): void
    {
        self::$disableHook = true;
    }

    public static function enableHook(): void
    {
        self::$disableHook = false;
    }

    public static function isEnabled(): bool
    {
        return self::$disableHook;
    }
}
