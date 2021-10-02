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

/**
 * @Hook(ExportTable::HOOK, priority=ExportTable::PRIORITY)
 */
final class ExportTable
{
    public const HOOK = 'exportTable';
    public const PRIORITY = 1000;


    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param $value
     * @param $dataRecord
     * @param $dca
     */
    public function __invoke(string $field, $value, string $strTable, $dataRecord, $dca)
    {
        if ('tl_calendar_events_member' === $strTable) {
            $dateAdapter = $this->framework->getAdapter(Date::class);
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            $controllerAdapter->loadDataContainer($strTable);
            $rgxp = $GLOBALS['TL_DCA']['tl_calendar_events_member']['fields'][$field]['eval']['rgxp'] ?? null;

            if (null !== $value && '' !== $value && \in_array($rgxp, ['date', 'time', 'datim'], true)) {
                $value = $dateAdapter->parse($dateAdapter->getFormatFromRgxp($rgxp), $value);
            }

            if ('pid' === $field) {
                $objModel = $calendarEventsModelAdapter->findByPk($value);

                if (null !== $objModel) {
                    $value = $objModel->title;
                }
            }
        }

        return $value;
    }
}
