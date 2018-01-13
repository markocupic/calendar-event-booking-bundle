<?php

/**
 * @copyright  Marko Cupic 2018
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */


namespace Contao;

/**
 * Class CalendarEventsMemberModel
 * @package Contao
 */
class CalendarEventsMemberModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_calendar_events_member';

    /**
     * @param $memberId
     * @param $eventId
     * @return bool
     */
    public function isRegistered($memberId, $eventId)
    {
        $objMember = \MemberModel::findByPk($memberId);
        if ($objMember !== null)
        {
            if ($objMember->sacMemberId != '')
            {
                $objEventsMembers = \Database::getInstance()->prepare('SELECT * FROM ' . static::$strTable . ' WHERE pid=? AND sacMemberId=?')->execute($eventId, $objMember->sacMemberId);
                if ($objEventsMembers->numRows)
                {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * @param $memberId
     * @return array
     */
    public function findUpcomingEventsByMemberId($memberId)
    {
        $arrEvents = array();
        $objMember = \MemberModel::findByPk($memberId);

        if ($objMember === null)
        {
            return $arrEvents;
        }

        $objEvents = \Database::getInstance()->prepare('SELECT * FROM tl_calendar_events WHERE endDate>? ORDER BY startDate')->execute(time());
        while ($objEvents->next())
        {
            $objJoinedEvents = \Database::getInstance()->prepare('SELECT * FROM tl_calendar_events_member WHERE sacMemberId=? AND pid=?')->limit(1)->execute($objMember->sacMemberId, $objEvents->id);
            if ($objJoinedEvents->numRows)
            {
                $arr['id'] = $objEvents->id;
                $arr['dateSpan'] = ($objEvents->startDate != $objEvents->endDate) ? \Date::parse('d.m.', $objEvents->startDate) . ' - ' . \Date::parse('d.m.Y', $objEvents->endDate) : \Date::parse('d.m.Y', $objEvents->startDate);
                $arr['eventType'] = \CalendarEventsModel::findByPk($objEvents->id)->getRelated('pid')->calendarType;
                $arr['registrationId'] = $objJoinedEvents->id;
                $arrEvents[] = $arr;
            }

        }


        return $arrEvents;
    }


    /**
     * @param $memberId
     * @return array
     */
    public function findPastEventsByMemberId($memberId)
    {
        $arrEvents = array();
        $objMember = \MemberModel::findByPk($memberId);

        if ($objMember === null)
        {
            return $arrEvents;
        }

        $objEvents = \Database::getInstance()->prepare('SELECT * FROM tl_calendar_events WHERE endDate<? ORDER BY startDate')->execute(time());
        while ($objEvents->next())
        {
            $objJoinedEvents = \Database::getInstance()->prepare('SELECT * FROM tl_calendar_events_member WHERE sacMemberId=? AND pid=?')->limit(1)->execute($objMember->sacMemberId, $objEvents->id);
            if ($objJoinedEvents->numRows)
            {
                $arr['id'] = $objEvents->id;
                $arr['dateSpan'] = ($objEvents->startDate != $objEvents->endDate) ? \Date::parse('d.m.', $objEvents->startDate) . ' - ' . \Date::parse('d.m.Y', $objEvents->endDate) : \Date::parse('d.m.Y', $objEvents->startDate);
                $arr['eventType'] = \CalendarEventsModel::findByPk($objEvents->id)->getRelated('pid')->calendarType;
                $arr['registrationId'] = $objJoinedEvents->id;
                $arrEvents[] = $arr;
            }

        }


        return $arrEvents;
    }

}
