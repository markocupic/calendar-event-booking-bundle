<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Notification;

use Codefog\HasteBundle\Formatter;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\PageModel;
use Contao\System;
use Contao\UserModel;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use NotificationCenter\Model\Notification as NotificationModel;

class Notification
{
    // Adapters
    private Adapter $config;
    private Adapter $controller;
    private Adapter $environment;
    private Adapter $notification;
    private Adapter $pageModel;
    private Adapter $system;
    private Adapter $userModel;

    private array $arrTokens = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Formatter $formatter,
    ) {
        // Adapters
        $this->config = $this->framework->getAdapter(Config::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->environment = $this->framework->getAdapter(Environment::class);
        $this->notification = $this->framework->getAdapter(NotificationModel::class);
        $this->pageModel = $this->framework->getAdapter(PageModel::class);
        $this->system = $this->framework->getAdapter(System::class);
        $this->userModel = $this->framework->getAdapter(UserModel::class);
    }

    public function getTokens(): array
    {
        return $this->arrTokens;
    }

    /**
     * @throws \Exception
     */
    public function setTokens(EventConfig $eventConfig, CalendarEventsMemberModel $objEventMember, int|null $senderId): void
    {
        $arrTokens = [];

        $strEventMemberTable = CalendarEventsMemberModel::getTable();

        // Get admin email
        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'] ?? $this->config->get('adminEmail');

        // Prepare tokens for event member and use "member_*" as prefix
        $this->controller->loadDataContainer($strEventMemberTable);

        // Load language file
        $this->controller->loadLanguageFile($strEventMemberTable);

        $row = $objEventMember->row();

        foreach ($row as $k => $v) {
            if (isset($GLOBALS['TL_DCA'][$strEventMemberTable]['fields'][$k])) {
                $arrTokens['member_'.$k] = $this->formatter->dcaValue('tl_calendar_events_member', $k, $v);
            } else {
                $arrTokens['member_'.$k] = html_entity_decode((string) $v);
            }
        }

        $arrTokens['member_salutation'] = html_entity_decode((string) ($GLOBALS['TL_LANG'][$strEventMemberTable]['salutation_'.$objEventMember->gender] ?? ''));

        // Prepare tokens for event and use "event_*" as prefix
        $this->controller->loadDataContainer('tl_calendar_events');

        $row = $eventConfig->getModel()->row();

        foreach ($row as $k => $v) {
            if (isset($GLOBALS['TL_DCA']['tl_calendar_events']['fields'][$k])) {
                $arrTokens['event_'.$k] = $this->formatter->dcaValue('tl_calendar_events', $k, $v);
            } else {
                $arrTokens['event_'.$k] = html_entity_decode((string) $v);
            }
        }

        if ($senderId) {
            // Prepare tokens for the sender and use "sender_*" as prefix
            $objSender = $this->userModel->findByPk($senderId);

            if (null !== $objSender) {
                $this->controller->loadDataContainer('tl_user');

                $row = $objSender->row();

                foreach ($row as $k => $v) {
                    if ('password' === $k || 'session' === $k) {
                        continue;
                    }

                    if (isset($GLOBALS['TL_DCA']['tl_user']['fields'][$k])) {
                        $arrTokens['sender_'.$k] = $this->formatter->dcaValue('tl_user', $k, $v);
                    } else {
                        $arrTokens['sender'.$k] = html_entity_decode((string) $v);
                    }
                }
            }
        }

        // Generate unsubscribe href
        $arrTokens['member_unsubscribeHref'] = '';

        /* Backward compatibility */
        $arrTokens['event_unsubscribeHref'] = '';

        if ($eventConfig->get('activateDeregistration')) {
            $objCalendar = $eventConfig->getModel()->getRelated('pid');

            if (null !== $objCalendar) {
                $objPage = $this->pageModel->findByPk($objCalendar->eventUnsubscribePage);

                if (null !== $objPage) {
                    $url = $objPage->getFrontendUrl().'?bookingToken='.$objEventMember->bookingToken;
                    $arrTokens['member_unsubscribeHref'] = $this->environment->get('url').'/'.$url;
                    $arrTokens['event_unsubscribeHref'] = $this->environment->get('url').'/'.$url;
                }
            }
        }

        // Trigger calEvtBookingPostBooking hook
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'] as $callback) {
                $arrTokens = $this->system->importStatic($callback[0])->{$callback[1]}($objEventMember, $eventConfig, $arrTokens);
            }
        }

        $this->arrTokens = $arrTokens;
    }

    /**
     * @throws \Exception
     */
    public function notify(array $arrNotifications): void
    {
        global $objPage;

        if (!empty($arrNotifications)) {
            // Send notification (multiple notifications possible)
            foreach ($arrNotifications as $notificationId) {
                $objNotification = $this->notification->findByPk($notificationId);

                $objNotification?->send($this->getTokens(), $objPage->language);
            }
        }
    }
}
