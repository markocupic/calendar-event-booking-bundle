<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2020 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;

/**
 * Class ExportTable.
 */
class ExportTable
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * ExportTable constructor.
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param $value
     * @param $dataRecord
     * @param $dca
     *
     * @return string
     */
    public function exportBookingList(string $field, $value, string $strTable, $dataRecord, $dca)
    {
        if ('tl_calendar_events_member' === $strTable) {
            /** @var Date $dateAdapter */
            $dateAdapter = $this->framework->getAdapter(Date::class);

            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var Config $configAdapter */
            $configAdapter = $this->framework->getAdapter(Config::class);

            if ('addedOn' === $field || 'dateOfBirth' === $field) {
                if ((int) $value) {
                    $value = $dateAdapter->parse($configAdapter->get('dateFormat'), $value);
                }
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
