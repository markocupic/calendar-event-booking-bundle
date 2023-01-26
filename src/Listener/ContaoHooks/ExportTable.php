<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Listener\ContaoHooks\ListenerInterface;

#[AsHook(ExportTable::HOOK, priority: 1000)]
final class ExportTable implements ListenerInterface
{
    public const HOOK = 'exportTable';

    public static bool $disableHook = false;

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * @param $varValue
     *
     * @return mixed
     */
    public function __invoke(string $strFieldName, $varValue, string $strTableName, array $arrDataRecord, array $arrDca, Config $objConfig)
    {
        if ('tl_calendar_events_member' === $strTableName) {
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            if ('pid' === $strFieldName) {
                $objModel = $calendarEventsModelAdapter->findByPk($varValue);

                if (null !== $objModel) {
                    $varValue = $objModel->title;
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
