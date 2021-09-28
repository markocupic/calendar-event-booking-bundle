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
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Haste\Util\Format;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use NotificationCenter\Model\Notification;

class NotificationHelper
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @throws \Exception
     */
    public function getNotificationTokens(CalendarEventsMemberModel $objEventMember): array
    {
        if (null === ($objEvent = $objEventMember->getRelated('pid'))) {
            throw new \Exception(sprintf('Event with ID %s not found.', $objEventMember->pid));
        }

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        /** @var UserModel $userModelAdapter */
        $userModelAdapter = $this->framework->getAdapter(UserModel::class);

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);

        /** @var System $systemAdapter */
        $systemAdapter = $this->framework->getAdapter(System::class);

        // Load language file
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        $arrTokens = [];

        // Get admin email
        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        // Prepare tokens for event member and use "member_" as prefix
        $row = $objEventMember->row();

        foreach ($row as $k => $v) {
            $arrTokens['member_'.$k] = Format::dcaValue('tl_calendar_events_member', $k, $v);
        }

        $arrTokens['member_salutation'] = html_entity_decode((string) $GLOBALS['TL_LANG']['tl_calendar_events_member']['salutation_'.$objEventMember->gender]);

        // Prepare tokens for event and use "event_" as prefix
        $row = $objEvent->row();

        foreach ($row as $k => $v) {
            $arrTokens['event_'.$k] = Format::dcaValue('tl_calendar_events', $k, $v);
        }

        // Prepare tokens for organizer_* (sender)
        $objOrganizer = $userModelAdapter->findByPk($objEvent->eventBookingNotificationSender);

        if (null !== $objOrganizer) {
            $row = $objOrganizer->row();

            foreach ($row as $k => $v) {
                if ('password' === $k || 'session' === $k) {
                    continue;
                }
                $arrTokens['organizer_'.$k] = Format::dcaValue('tl_user', $k, $v);
            }

            // deprecated since version 4.2, to be removed in 5.0 Use organizer_name instead of organizer_senderName */
            $arrTokens['organizer_senderName'] = $arrTokens['organizer_name'];

            // deprecated since version 4.2, to be removed in 5.0 Use organizer_email instead of organizer_senderEmail */
            $arrTokens['organizer_senderEmail'] = $arrTokens['organizer_email'];
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
     * @throws \Exception
     */
    public function notify(CalendarEventsMemberModel $objEventMember, CalendarEventsModel $objEvent): void
    {
        global $objPage;

        /** @var Notification $notificationAdapter */
        $notificationAdapter = $this->framework->getAdapter(Notification::class);

        /** @var StringUtil $stringUtilAdaper */
        $stringUtilAdaper = $this->framework->getAdapter(StringUtil::class);

        if ($objEvent->enableNotificationCenter) {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdaper->deserialize($objEvent->eventBookingNotificationCenterIds);

            if (!empty($arrNotifications) && \is_array($arrNotifications)) {
                // Get $arrToken from helper
                $arrTokens = $this->getNotificationTokens($objEventMember);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId) {
                    $objNotification = $notificationAdapter->findByPk($notificationId);

                    if (null !== $objNotification) {
                        $objNotification->send($arrTokens, $objPage->language);
                    }
                }
            }
        }
    }
}
