<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\Message;
use Contao\CalendarEventsMemberModel;
use Contao\Calendar;
use Contao\Date;

/**
 * Class tl_calendar_event_booking
 */
class tl_calendar_event_booking extends tl_calendar_events
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adjust bookingStartDate and  bookingStartDate
     *
     * @param DataContainer $dc
     */
    public function adjustBookingDate(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $arrSet['bookingStartDate'] = $dc->activeRecord->bookingStartDate;
        $arrSet['bookingEndDate'] = $dc->activeRecord->bookingEndDate;

        // Set end date
        if (strlen($dc->activeRecord->bookingEndDate))
        {
            if ($dc->activeRecord->bookingEndDate < $dc->activeRecord->bookingStartDate)
            {
                $arrSet['bookingEndDate'] = $dc->activeRecord->bookingStartDate;
                Message::addInfo('Das Enddatum für den Buchungszeitraum wurde angepasst.', TL_MODE);
            }
        }


        $this->Database->prepare("UPDATE tl_calendar_events %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }

    /**
     * Add the type of input field
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listEvents($arrRow)
    {
        if ($arrRow['addBookingForm'])
        {
            $countBookings = CalendarEventsMemberModel::countBy('pid', $arrRow['id']);
            $span = Calendar::calculateSpan($arrRow['startTime'], $arrRow['endTime']);

            if ($span > 0)
            {
                $date = Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['startTime']) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['endTime']);
            }
            elseif ($arrRow['startTime'] == $arrRow['endTime'])
            {
                $date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']) . ($arrRow['addTime'] ? ' ' . Date::parse(Config::get('timeFormat'), $arrRow['startTime']) : '');
            }
            else
            {
                $date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']) . ($arrRow['addTime'] ? ' ' . Date::parse(Config::get('timeFormat'), $arrRow['startTime']) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse(Config::get('timeFormat'), $arrRow['endTime']) : '');
            }

            return '<div class="tl_content_left">' . $arrRow['title'] . ' <span style="color:#999;padding-left:3px">[' . $date . ']</span><span style="color:#999;padding-left:3px">[' . $GLOBALS['TL_LANG']['MSC']['bookings'] . ': ' . $countBookings . 'x]</span></div>';
        }
        else
        {
            return parent::listEvents($arrRow);
        }
    }
}
