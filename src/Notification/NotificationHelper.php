<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Notification;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Config;
use Contao\PageModel;
use Contao\UserModel;

/**
 * Class NotificationHelper
 * @package Markocupic\CalendarEventBookingBundle\Notification
 */
class NotificationHelper
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * NotificationHelper constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param CalendarEventsMemberModel $objEventMember
     * @param CalendarEventsModel $objEvent
     * @return array
     * @throws \Exception
     */
    public function getNotificationTokens(CalendarEventsMemberModel $objEventMember, CalendarEventsModel $objEvent): array
    {
        /** @var Config $configAdapter */
        $configAdapter = $this->framework->getAdapter(Config::class);

        /** @var Date $dateAdapter */
        $dateAdapter = $this->framework->getAdapter(Date::class);

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        /** @var UserModel $userModelAdapter */
        $userModelAdapter = $this->framework->getAdapter(UserModel::class);

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);

        $arrTokens = [];

        // Load language file
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        // Prepare tokens for event member and use "member_" as prefix
        $row = $objEventMember->row();
        foreach ($row as $k => $v)
        {
            $arrTokens['member_' . $k] = html_entity_decode((string) $v);
        }
        $arrTokens['member_salutation'] = html_entity_decode((string) $GLOBALS['TL_LANG']['tl_calendar_events_member'][$objEventMember->gender]);
        $arrTokens['member_dateOfBirthFormated'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $objEventMember->dateOfBirth);

        // Prepare tokens for event and use "event_" as prefix
        $row = $objEvent->row();
        foreach ($row as $k => $v)
        {
            $arrTokens['event_' . $k] = html_entity_decode((string) $v);
        }

        // event startTime & endTime
        if ($objEvent->addTime)
        {
            $arrTokens['event_startTime'] = $dateAdapter->parse($configAdapter->get('timeFormat'), $objEvent->startTime);
            $arrTokens['event_endTime'] = $dateAdapter->parse($configAdapter->get('timeFormat'), $objEvent->endTime);
        }
        else
        {
            $arrTokens['event_startTime'] = '';
            $arrTokens['event_endTime'] = '';
        }

        // event title
        $arrTokens['event_title'] = html_entity_decode((string) $objEvent->title);

        // event startDate & endDate
        $arrTokens['event_startDate'] = '';
        $arrTokens['event_endDate'] = '';
        if (is_numeric($objEvent->startDate))
        {
            $arrTokens['event_startDate'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $objEvent->startDate);
        }
        if (is_numeric($objEvent->endDate))
        {
            $arrTokens['event_endDate'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $objEvent->endDate);
        }

        // Prepare tokens for organizer_* (sender)
        $objOrganizer = $userModelAdapter->findByPk($objEvent->eventBookingNotificationSender);
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
                $arrTokens['organizer_' . $k] = html_entity_decode((string) $v);
            }
        }

        // Generate unsubscribe href
        $arrTokens['event_unsubscribeHref'] = '';
        if ($objEvent->enableDeregistration)
        {
            $objCalendar = $objEvent->getRelated('pid');
            if ($objCalendar !== null)
            {
                $objPage = $pageModelAdapter->findByPk($objCalendar->eventUnsubscribePage);
                if ($objPage !== null)
                {
                    $url = $objPage->getFrontendUrl() . '?bookingToken=' . $objEventMember->bookingToken;
                    $arrTokens['event_unsubscribeHref'] = $controllerAdapter->replaceInsertTags('{{env::url}}/') . $url;
                }
            }
        }

        return $arrTokens;
    }
}
