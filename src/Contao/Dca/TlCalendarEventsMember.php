<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Contao\Dca;

use Contao\Input;
use Markocupic\ExportTable\ExportTable;

/**
 * Class TlCalendarEventsMember
 * @package Markocupic\CalendarEventBookingBundle\Contao\Dca
 */
class TlCalendarEventsMember
{

    /**
     * @throws \Exception
     */
    public function downloadRegistrationList()
    {
        // Download the registration list as a csv spreadsheet
        if (Input::get('act') === 'downloadRegistrationList')
        {
            $opt = [];

            // Add fields
            $arrSkip = ['bookingToken'];
            $opt['arrSelectedFields'] = [];
            foreach ($GLOBALS['TL_DCA']['tl_calendar_events_member']['fields'] as $k => $v)
            {
                if (!\in_array($k, $arrSkip))
                {
                    $opt['arrSelectedFields'][] = $k;
                }
            }

            $opt['exportType'] = 'csv';
            $opt['arrFilter'] = [['tl_calendar_events_member.pid=?'], [Input::get('id')]];
            ExportTable::exportTable('tl_calendar_events_member', $opt);
            exit;
        }
    }
}
