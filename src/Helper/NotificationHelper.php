<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Codefog\HasteBundle\UrlParser;
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
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class NotificationHelper
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly NotificationCenter $notificationCenter,
        private readonly RequestStack $requestStack,
        private readonly UrlParser $urlParser,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function getNotificationTokens(CalendarEventsMemberModel $registration): array
    {
        if (null === ($event = $registration->getRelated('pid'))) {
            throw new \Exception(sprintf('Event with ID %s not found.', $registration->pid));
        }

        $controllerAdapter = $this->framework->getAdapter(Controller::class);
        $userModelAdapter = $this->framework->getAdapter(UserModel::class);
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $systemAdapter = $this->framework->getAdapter(System::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        // Load language file
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        $arrTokens = [];

        // Get admin email
        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        // Prepare tokens for event member and use "member_" as prefix
        $row = $registration->row();

        foreach ($row as $k => $v) {
            if (isset($GLOBALS['TL_DCA']['tl_calendar_events_member']['fields'][$k])) {
                $arrTokens['member_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
            } else {
                $arrTokens['member_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
            }
        }

        $arrTokens['member_salutation'] = $stringUtilAdapter->revertInputEncoding((string) $GLOBALS['TL_LANG']['tl_calendar_events_member']['salutation_'.$registration->gender]);

        // Prepare tokens for event and use "event_" as prefix
        $row = $event->row();

        foreach ($row as $k => $v) {
            $arrTokens['event_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
        }

        $arrTokens['event_startDateFormatted'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $event->startDate);
        $arrTokens['event_endDateFormatted'] = $dateAdapter->parse($configAdapter->get('dateFormat'), $event->endDate);
        $arrTokens['event_startTimeFormatted'] = $dateAdapter->parse($configAdapter->get('timeFormat'), $event->startTime);
        $arrTokens['event_endTimeFormatted'] = $dateAdapter->parse($configAdapter->get('timeFormat'), $event->endTime);

        // Prepare tokens for organizer_* (sender)
        $organizer = $userModelAdapter->findByPk($event->eventBookingNotificationSender);

        if (null !== $organizer) {
            $row = $organizer->row();

            foreach ($row as $k => $v) {
                if ('password' === $k || 'session' === $k) {
                    continue;
                }
                $arrTokens['organizer_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
            }
        }

        // Generate unsubscribe href
        $arrTokens['event_unsubscribeHref'] = '';

        if ($event->enableDeregistration) {
            $calendar = $event->getRelated('pid');

            if (null !== $calendar) {
                $page = $pageModelAdapter->findByPk($calendar->eventUnsubscribePage);

                if (null !== $page) {
                    $arrTokens['event_unsubscribeHref'] = $this->urlParser->addQueryString('bookingToken='.$registration->bookingToken, $page->getAbsoluteUrl());
                }
            }
        }

        // Trigger calEvtBookingGetNotificationTokens hook
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'] as $callback) {
                $arrTokens = $systemAdapter->importStatic($callback[0])->{$callback[1]}($registration, $event, $arrTokens);
            }
        }

        return $arrTokens;
    }

    /**
     * @throws \Exception
     */
    public function notify(CalendarEventsMemberModel $registration, CalendarEventsModel $event): void
    {
        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

        if ($event->enableNotificationCenter) {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdapter->deserialize($event->eventBookingNotificationCenterIds);

            if (!empty($arrNotifications) && \is_array($arrNotifications)) {
                // Get $arrToken from helper
                $arrTokens = $this->getNotificationTokens($registration);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId) {
                    $request = $this->requestStack->getCurrentRequest();
                    /** @var PageModel $page */
                    $page = $request->attributes->get('page');

                    $this->notificationCenter->sendNotification((int) $notificationId, $arrTokens, $page?->language);
                }
            }
        }
    }
}
