<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 * @package import_from_csv
 * @author Marko Cupic 2014, extension sponsered by Rainer-Maria Fritsch - Fast-Doc UG, Berlin
 * @link https://github.com/markocupic/import_from_csv
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace Contao;

/**
 * Class CalendarEventsMemberModel
 * Reads and writes tl_calendar_events_member
 * Copyright: 2016 Marko Cupic
 * @author Marko Cupic <m.cupic@gmx.ch>
 * @package sacpilatus-bundle
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
        while($objEvents->next())
        {
            $objJoinedEvents = \Database::getInstance()->prepare('SELECT * FROM tl_calendar_events_member WHERE sacMemberId=? AND pid=?')->limit(1)->execute($objMember->sacMemberId, $objEvents->id);
            if($objJoinedEvents->numRows)
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
        while($objEvents->next())
        {
            $objJoinedEvents = \Database::getInstance()->prepare('SELECT * FROM tl_calendar_events_member WHERE sacMemberId=? AND pid=?')->limit(1)->execute($objMember->sacMemberId, $objEvents->id);
            if($objJoinedEvents->numRows)
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
