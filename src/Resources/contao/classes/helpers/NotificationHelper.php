<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\Controller;
use Contao\Date;
use Contao\Config;
use Contao\PageModel;
use Contao\UserModel;

/**
 * Class NotificationHelper
 * @package Markocupic\CalendarEventBookingBundle
 */
class NotificationHelper
{

    /**
     * @param $objEventMember
     * @param $objEvent
     * @return array
     */
    public static function getNotificationTokens($objEventMember, $objEvent)
    {
        $arrTokens = array();

        // Prepare tokens for event member and use "member_" as prefix
        $row = $objEventMember->row();
        foreach ($row as $k => $v)
        {
            $arrTokens['member_' . $k] = html_entity_decode($v);
        }
        $arrTokens['member_salutation'] = html_entity_decode($GLOBALS['TL_LANG']['tl_calendar_events_member'][$objEventMember->gender]);
        $arrTokens['member_dateOfBirthFormated'] = Date::parse(Config::get('dateFormat'), $objEventMember->dateOfBirth);

        // Prepare tokens for event and use "event_" as prefix
        $row = $objEvent->row();
        foreach ($row as $k => $v)
        {
            $arrTokens['event_' . $k] = html_entity_decode($v);
        }

        // event startTime & endTime
        if ($objEvent->addTime)
        {
            $arrTokens['event_startTime'] = Date::parse(Config::get('timeFormat'), $objEvent->startTime);
            $arrTokens['event_endTime'] = Date::parse(Config::get('timeFormat'), $objEvent->endTime);
        }
        else
        {
            $arrTokens['event_startTime'] = '';
            $arrTokens['event_endTime'] = '';
        }

        // event title
        $arrTokens['event_title'] = html_entity_decode($objEvent->title);

        // event startDate & endDate
        $arrTokens['event_startDate'] = '';
        $arrTokens['event_endDate'] = '';
        if (is_numeric($objEvent->startDate))
        {
            $arrTokens['event_startDate'] = Date::parse(Config::get('dateFormat'), $objEvent->startDate);
        }
        if (is_numeric($objEvent->endDate))
        {
            $arrTokens['event_endDate'] = Date::parse(Config::get('dateFormat'), $objEvent->endDate);
        }

        // Prepare tokens for organizer_* (sender)
        $objOrganizer = UserModel::findByPk($objEvent->eventBookingNotificationSender);
        if ($objOrganizer !== null)
        {
            $arrTokens['organizer_senderName'] = $objOrganizer->name;
            $arrTokens['organizer_senderEmail'] = $objOrganizer->email;

            $row = $objOrganizer->row();
            foreach ($row as $k => $v)
            {
                if ($k === 'password')
                {
                    continue;
                }
                $arrTokens['organizer_' . $k] = html_entity_decode($v);
            }
        }

        // Generate unsubscribe href
        $arrTokens['event_unsubscribeHref'] = '';
        if ($objEvent->enableDeregistration)
        {
            $objCalendar = $objEvent->getRelated('pid');
            if ($objCalendar !== null)
            {
                $objPage = PageModel::findByPk($objCalendar->eventUnsubscribePage);
                if ($objPage !== null)
                {
                    $url = $objPage->getFrontendUrl() . '?bookingToken=' . $objEventMember->bookingToken;
                    $arrTokens['event_unsubscribeHref'] = Controller::replaceInsertTags('{{env::url}}/') . $url;
                }
            }
        }

        return $arrTokens;
    }
}
