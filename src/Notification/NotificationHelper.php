<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Notification;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

/**
 * Class NotificationHelper.
 */
class NotificationHelper
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * NotificationHelper constructor.
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
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

        /** @var System $systemAdapter */
        $systemAdapter = $this->framework->getAdapter(System::class);

        $arrTokens = [];

        $delimiter = ', ';

        // Load language file
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        // Prepare tokens for event member and use "member_" as prefix
        $row = $objEventMember->row();

        foreach ($row as $k => $v) {
            $arrTokens = $this->flatten(html_entity_decode((string) $v), 'member_'.$k, $arrTokens, $delimiter);
        }

        $arrTokens['member_salutation'] = html_entity_decode((string) $GLOBALS['TL_LANG']['tl_calendar_events_member'][$objEventMember->gender]);
        $arrTokens['member_dateOfBirthFormated'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $objEventMember->dateOfBirth);

        // Prepare tokens for event and use "event_" as prefix
        $row = $objEvent->row();

        foreach ($row as $k => $v) {
            $arrTokens = $this->flatten(html_entity_decode((string) $v), 'event_'.$k, $arrTokens, $delimiter);
        }

        // event startTime & endTime
        if ($objEvent->addTime) {
            $arrTokens['event_startTime'] = $dateAdapter->parse($configAdapter->get('timeFormat'), $objEvent->startTime);
            $arrTokens['event_endTime'] = $dateAdapter->parse($configAdapter->get('timeFormat'), $objEvent->endTime);
        } else {
            $arrTokens['event_startTime'] = '';
            $arrTokens['event_endTime'] = '';
        }

        // event title
        $arrTokens['event_title'] = html_entity_decode((string) $objEvent->title);

        // event startDate & endDate
        $arrTokens['event_startDate'] = '';
        $arrTokens['event_endDate'] = '';

        if (is_numeric($objEvent->startDate)) {
            $arrTokens['event_startDate'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $objEvent->startDate);
        }

        if (is_numeric($objEvent->endDate)) {
            $arrTokens['event_endDate'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $objEvent->endDate);
        }

        // Prepare tokens for organizer_* (sender)
        $objOrganizer = $userModelAdapter->findByPk($objEvent->eventBookingNotificationSender);

        if (null !== $objOrganizer) {
            $arrTokens['organizer_senderName'] = $objOrganizer->name;
            $arrTokens['organizer_senderEmail'] = $objOrganizer->email;

            $row = $objOrganizer->row();

            foreach ($row as $k => $v) {
                if ('password' === $k) {
                    continue;
                }
                $arrTokens = $this->flatten(html_entity_decode((string) $v), 'organizer_'.$k, $arrTokens, $delimiter);
            }
        }

        // Generate unsubscribe href
        $arrTokens['event_unsubscribeHref'] = '';

        if ($objEvent->enableDeregistration) {
            $objCalendar = $objEvent->getRelated('pid');

            if (null !== $objCalendar) {
                $objPage = $pageModelAdapter->findByPk($objCalendar->eventUnsubscribePage);

                if (null !== $objPage) {
                    $url = $objPage->getFrontendUrl().'?bookingToken='.$objEventMember->bookingToken;
                    $arrTokens['event_unsubscribeHref'] = $controllerAdapter->replaceInsertTags('{{env::url}}/').$url;
                }
            }
        }

        // Trigger calEvtBookingPostBooking hook
        if (!empty($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens']) || \is_array($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'] as $callback) {
                $arrTokens = $systemAdapter->importStatic($callback[0])->{$callback[1]}($objEventMember, $objEvent, $arrTokens);
            }
        }

        return $arrTokens;
    }

    /**
     * Flatten input data, Simple Tokens can't handle arrays.
     *
     * @param mixed  $varValue
     * @param string $strKey
     * @param string $strPattern
     */
    private function flatten($varValue, $strKey, array $arrData, $strPattern = ', ')
    {
        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

        if (!empty($varValue) && !\is_array($varValue) && \is_string($varValue) && \strlen($varValue) > 3 && \is_array($stringUtilAdapter->deserialize($varValue))) {
            $varValue = $stringUtilAdapter->deserialize($varValue);
        }

        if (\is_object($varValue)) {
            return $arrData;
        }

        if (!\is_array($varValue)) {
            $arrData[$strKey] = $varValue;

            return $arrData;
        }

        $blnAssoc = array_is_assoc($varValue);
        $arrValues = [];

        foreach ($varValue as $k => $v) {
            if ($blnAssoc || \is_array($v)) {
                $arrData = $this->flatten($v, $strKey.'_'.$k, $arrData);
            } else {
                $arrData[$strKey.'_'.$v] = '1';
                $arrValues[] = $v;
            }
        }

        $arrData[$strKey] = implode($strPattern, $arrValues);

        return $arrData;
    }
}
