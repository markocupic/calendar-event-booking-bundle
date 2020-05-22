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

use Contao\Calendar;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Message;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

/**
 * Class TlCalendarEvents
 * @package Markocupic\CalendarEventBookingBundle\Contao\Dca
 */
class TlCalendarEvents extends \tl_calendar_events
{

    /**
     * Adjust bookingStartDate and bookingStartDate
     *
     * @param DataContainer $dc
     */
    public function adjustBookingDate(DataContainer $dc): void
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $arrSet['bookingStartDate'] = $dc->activeRecord->bookingStartDate;
        $arrSet['bookingEndDate'] = $dc->activeRecord->bookingEndDate;

        // Set end date
        if (!empty($dc->activeRecord->bookingEndDate))
        {
            if ($dc->activeRecord->bookingEndDate < $dc->activeRecord->bookingStartDate)
            {
                $arrSet['bookingEndDate'] = $dc->activeRecord->bookingStartDate;
                Message::addInfo('Das Enddatum für den Buchungszeitraum wurde angepasst.', TL_MODE);
            }
        }

        Database::getInstance()->prepare("UPDATE tl_calendar_events %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }

    /**
     * @param array $arrRow
     * @return string
     */
    public function listEvents(array $arrRow): string
    {
        if ($arrRow['addBookingForm'] === '1')
        {
            $countBookings = CalendarEventsMemberModel::countBy('pid', $arrRow['id']);

            $span = Calendar::calculateSpan($arrRow['startTime'], $arrRow['endTime']);

            if ($span > 0)
            {
                $date = Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['startTime']) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['endTime']);
            }
            elseif ($arrRow['startTime'] === $arrRow['endTime'])
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

    /**
     * @param int|null $intValue
     * @param DataContainer $dc
     * @return int|null
     */
    public function saveUnsubscribeLimitTstamp(int $intValue = null, DataContainer $dc): ?int
    {
        if (!empty($intValue))
        {
            // Check whether we have an unsubscribeLimit (in days) set as well, notify the user that we cannot
            // have both
            if ($dc->activeRecord->unsubscribeLimit > 0)
            {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['conflictingUnsubscribeLimits']);
            }

            // Check whether the timestamp entered makes sense in relation to the event start and end times
            $intMaxValue = null;

            // If the event has an end date (and optional time) that's the last sensible time unsubscription makes sense
            if ($dc->activeRecord->endDate)
            {
                $intMaxValue = (int) $dc->activeRecord->endDate + (int) $dc->activeRecord->endTime;
            }
            else
            {
                $intMaxValue = (int) $dc->activeRecord->startDate + (int) $dc->activeRecord->startTime;
            }

            if ($intValue > $intMaxValue)
            {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['invalidUnsubscriptionLimit']);
            }
        }

        return $intValue;
    }
}
