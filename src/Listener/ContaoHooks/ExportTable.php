<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;

/**
 * Class ExportTable
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class ExportTable
{

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * ExportTable constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param string $field
     * @param $value
     * @param string $strTable
     * @param $dataRecord
     * @param $dca
     * @return string
     */
    public function exportBookingList(string $field, $value, string $strTable, $dataRecord, $dca)
    {
        if ($strTable === 'tl_calendar_events_member')
        {
            /** @var Date $dateAdapter */
            $dateAdapter = $this->framework->getAdapter(Date::class);

            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var Config $configAdapter */
            $configAdapter = $this->framework->getAdapter(Config::class);

            if ($field === 'addedOn' || $field === 'dateOfBirth')
            {
                if (intval($value))
                {
                    $value = $dateAdapter->parse($configAdapter->get('dateFormat'), $value);
                }
            }

            if ($field === 'pid')
            {
                $objModel = $calendarEventsModelAdapter->findByPk($value);
                if ($objModel !== null)
                {
                    $value = $objModel->title;
                }
            }
        }

        return $value;
    }
}
