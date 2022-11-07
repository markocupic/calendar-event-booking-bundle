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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Markocupic\ExportTable\Config\Config;

/**
 * @Hook(ExportTable::HOOK, priority=ExportTable::PRIORITY)
 */
final class ExportTable extends AbstractHook
{
    public const HOOK = 'exportTable';
    public const PRIORITY = 1000;

    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param mixed $varValue
     *
     * @return mixed
     */
    public function __invoke(string $strFieldName, $varValue, string $strTableName, array $arrDataRecord, array $arrDca, Config $objConfig)
    {
        if (!self::isEnabled()) {
            return $varValue;
        }

        if ('tl_calendar_events_member' === $strTableName) {
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            if ('pid' === $strFieldName) {
                $objModel = $calendarEventsModelAdapter->findByPk($varValue);

                if (null !== $objModel) {
                    $varValue = $objModel->title;
                }
            } elseif ('bookingState' === $strFieldName) {
                if (!empty($varValue) && isset($GLOBALS['TL_LANG']['MSC'][$varValue])) {
                    $varValue = $GLOBALS['TL_LANG']['MSC'][$varValue];
                }
            }
        }

        return $varValue;
    }
}
