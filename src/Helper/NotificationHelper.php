<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Haste\Util\Format;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use NotificationCenter\Model\Notification;

class NotificationHelper
{
    private ContaoFramework $framework;

    // Adapters
    private Adapter $controller;
    private Adapter $environment;
    private Adapter $format;
    private Adapter $notification;
    private Adapter $pageModel;
    private Adapter $stringUtil;
    private Adapter $system;
    private Adapter $userModel;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

        // Adapters
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->environment = $this->framework->getAdapter(Environment::class);
        $this->format = $this->framework->getAdapter(Format::class);
        $this->notification = $this->framework->getAdapter(Notification::class);
        $this->pageModel = $this->framework->getAdapter(PageModel::class);
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
        $this->system = $this->framework->getAdapter(System::class);
        $this->userModel = $this->framework->getAdapter(UserModel::class);
    }

    /**
     * @throws \Exception
     */
    public function getNotificationTokens(CalendarEventsMemberModel $objEventMember): array
    {
        if (null === ($objEvent = $objEventMember->getRelated('pid'))) {
            throw new \Exception(sprintf('Event with ID %s not found.', $objEventMember->pid));
        }

        $arrTokens = [];

        // Get admin email
        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        // Prepare tokens for event member and use "member_*" as prefix
        $this->controller->loadDataContainer('tl_calendar_events_member');

        // Load language file
        $this->controller->loadLanguageFile('tl_calendar_events_member');

        $row = $objEventMember->row();

        foreach ($row as $k => $v) {
            if (isset($GLOBALS['TL_DCA']['tl_calendar_events_member'][$k])) {
                $arrTokens['member_'.$k] = $this->format->dcaValue('tl_calendar_events_member', $k, $v);
            } else {
                $arrTokens['member_'.$k] = html_entity_decode((string) $v);
            }
        }

        $arrTokens['member_salutation'] = html_entity_decode((string) ($GLOBALS['TL_LANG']['tl_calendar_events_member']['salutation_'.$objEventMember->gender] ?? ''));

        // Prepare tokens for event and use "event_*" as prefix
        $this->controller->loadDataContainer('tl_calendar_events');

        $row = $objEvent->row();

        foreach ($row as $k => $v) {
            if (isset($GLOBALS['TL_DCA']['tl_calendar_events'][$k])) {
                $arrTokens['event_'.$k] = $this->format->dcaValue('tl_calendar_events', $k, $v);
            } else {
                $arrTokens['event_'.$k] = html_entity_decode((string) $v);
            }
        }

        // Prepare tokens for the organizer (sender) and use "organizer_*" as prefix
        $objOrganizer = $this->userModel->findByPk($objEvent->eventBookingNotificationSender);

        if (null !== $objOrganizer) {
            $this->controller->loadDataContainer('tl_user');

            $row = $objOrganizer->row();

            foreach ($row as $k => $v) {
                if ('password' === $k || 'session' === $k) {
                    continue;
                }

                if (isset($GLOBALS['TL_DCA']['tl_user'][$k])) {
                    $arrTokens['organizer_'.$k] = $this->format->dcaValue('tl_user', $k, $v);
                } else {
                    $arrTokens['organizer_'.$k] = html_entity_decode((string) $v);
                }
            }

            // deprecated since version 4.2, to be removed in 5.0 Use organizer_name instead of organizer_senderName */
            $arrTokens['organizer_senderName'] = $arrTokens['organizer_name'];

            // deprecated since version 4.2, to be removed in 5.0 Use organizer_email instead of organizer_senderEmail */
            $arrTokens['organizer_senderEmail'] = $arrTokens['organizer_email'];
        }

        // Generate unsubscribe href
        $arrTokens['event_unsubscribeHref'] = '';

        if ($objEvent->activateDeregistration) {
            $objCalendar = $objEvent->getRelated('pid');

            if (null !== $objCalendar) {
                $objPage = $this->pageModel->findByPk($objCalendar->eventUnsubscribePage);

                if (null !== $objPage) {
                    $url = $objPage->getFrontendUrl().'?bookingToken='.$objEventMember->bookingToken;
                    $arrTokens['event_unsubscribeHref'] = $this->environment->get('url').'/'.$url;
                }
            }
        }

        // Trigger calEvtBookingPostBooking hook
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'] as $callback) {
                $arrTokens = $this->system->importStatic($callback[0])->{$callback[1]}($objEventMember, $objEvent, $arrTokens);
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

        if ($objEvent->activateNotification) {
            // Multiple notifications possible
            $arrNotifications = $this->stringUtil->deserialize($objEvent->eventBookingNotification);

            if (!empty($arrNotifications) && \is_array($arrNotifications)) {
                // Get $arrToken from helper
                $arrTokens = $this->getNotificationTokens($objEventMember);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId) {
                    $objNotification = $this->notification->findByPk($notificationId);

                    if (null !== $objNotification) {
                        $objNotification->send($arrTokens, $objPage->language);
                    }
                }
            }
        }
    }
}
