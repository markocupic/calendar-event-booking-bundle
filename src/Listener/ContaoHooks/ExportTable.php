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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Date;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Listener\ContaoHooks\ExportTableListenerInterface;

/**
 * @Hook(ExportTable::HOOK, priority=ExportTable::PRIORITY)
 */
final class ExportTable implements ExportTableListenerInterface
{
    public const HOOK = 'exportTable';
    public const PRIORITY = 1000;

    /**
     * @var bool
     */
    public static $disableHook = false;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param string $strFieldname
     * @param $varValue
     * @param string $strTablename
     * @param array $arrDataRecord
     * @param array $arrDca
     * @param Config $objConfig
     * @return mixed
     */
    public function __invoke(string $strFieldname, $varValue, string $strTablename, array $arrDataRecord, array $arrDca, Config $objConfig)
    {
        if ('tl_calendar_events_member' === $strTablename) {
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            if ('pid' === $strFieldname) {
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
}
