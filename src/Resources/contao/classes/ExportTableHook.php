<?php

/**
 * SAC Pilatus web plugin
 * Copyright (c) 2008-2017 Marko Cupic
 * @package sacpilatus-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2017
 * @link    https://sac-kurse.kletterkader.com
 */

namespace Markocupic\CalendarEventBookingBundle;


use Contao\CalendarEventsModel;
use Contao\Date;
use Contao\System;


/**
 * Class CalendarKurse
 * @package Markocupic\Sacpilatus
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
        if ($strTable == 'tl_calendar_events_member')
        {
            if ($field == 'addedOn')
            {
                if (intval($value))
                {
                    $value = Date::parse('Y-m-d', $value);
                }
            }

            if ($field == 'pid')
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