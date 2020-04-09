<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\CalendarEventsModel;
use Contao\Date;
use Contao\System;

/**
 * Class ExportTableHook
 * @package Markocupic\CalendarEventBookingBundle
 */
class ExportTableHook extends System
{

    /**
     * @param $field
     * @param $value
     * @param $strTable
     * @param $dataRecord
     * @param $dca
     * @return string
     */
    public function exportBookingListHook($field, $value, $strTable, $dataRecord, $dca)
    {
        if ($strTable === 'tl_calendar_events_member')
        {
            if ($field === 'addedOn')
            {
                if (intval($value))
                {
                    $value = Date::parse('Y-m-d', $value);
                }
            }

            if ($field === 'pid')
            {
                $objModel = CalendarEventsModel::findByPk($value);
                if ($objModel !== null)
                {
                    $value = $objModel->title;
                }
            }
        }

        return $value;
    }
}
