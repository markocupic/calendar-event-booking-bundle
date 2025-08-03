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

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Markocupic\CalendarEventBookingBundle\Event\SendNotificationEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Terminal42\NotificationCenterBundle\Receipt\ReceiptCollection;

class NotificationHelper
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EventBooking $eventBooking,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly NotificationCenter $notificationCenter,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function sendNotification(int $notificationId, array $tokens, CalendarEventsMemberModel $booking): ReceiptCollection|null
    {
        $event = new SendNotificationEvent($notificationId, $tokens, $booking, $this->requestStack->getCurrentRequest());

        $this->eventDispatcher->dispatch($event);

        if (false === $event->shouldSend()) {
            return null;
        }

        return $this->notificationCenter->sendNotification($event->getNotificationId(), $event->getTokens());
    }

    /**
     * @throws \Exception
     */
    public function getNotificationTokens(CalendarEventsMemberModel $booking, CalendarEventsPaymentModel|null $payment = null): array
    {
        if (null === ($event = $booking->getRelated('pid'))) {
            throw new \Exception(\sprintf('Event with ID %s not found.', $booking->pid));
        }

        $controllerAdapter = $this->framework->getAdapter(Controller::class);
        $userModelAdapter = $this->framework->getAdapter(UserModel::class);
        $systemAdapter = $this->framework->getAdapter(System::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

        // Load language file
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        $arrTokens = [];

        // Get admin email
        $arrTokens['admin_email'] = $this->getAdminEmail();

        // Prepare tokens for event member and use "member_" as prefix
        $row = $booking->row();

        foreach ($row as $k => $v) {
            $arrTokens['member_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
        }

        $arrTokens['member_salutation'] = $stringUtilAdapter->revertInputEncoding((string) $GLOBALS['TL_LANG']['tl_calendar_events_member']['salutation_'.$booking->gender]);

        // Prepare tokens for event and use "event_" as prefix
        $row = $event->row();

        foreach ($row as $k => $v) {
            $arrTokens['event_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
        }

        // Prepare tokens for organizer_* (sender)
        $organizer = $userModelAdapter->findById($event->eventBookingNotificationSender);

        if (null !== $organizer) {
            $row = $organizer->row();

            foreach ($row as $k => $v) {
                if ('password' === $k || 'session' === $k) {
                    continue;
                }
                $arrTokens['organizer_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
            }
        }

        // Generate opt-in link
        $arrTokens['member_optInLink'] = $this->eventBooking->getOptInLink($booking);

        // Generate unsubscribe link
        $arrTokens['member_unsubscribeLink'] = $this->eventBooking->getUnsubscribeLink($booking);

        // Add payment tokens
        if (null !== $payment) {
            foreach ($payment->row() as $k => $v) {
                $arrTokens['payment_'.$k] = $stringUtilAdapter->revertInputEncoding((string) $v);
            }
        }

        // Trigger calEvtBookingGetNotificationTokens hook
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingGetNotificationTokens'] as $callback) {
                $arrTokens = $systemAdapter->importStatic($callback[0])->{$callback[1]}($booking, $event, $arrTokens);
            }
        }

        return $arrTokens;
    }

    private function getAdminEmail(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $this->scopeMatcher->isFrontendRequest()) {
            /** @var PageModel $pageModel */
            $pageModel = $request->attributes->get('pageModel');

            if (null !== $pageModel && $adminEmail = $pageModel->adminEmailget) {
                return $adminEmail;
            }
        }

        if (!empty($GLOBALS['TL_ADMIN_EMAIL'])) {
            return $GLOBALS['TL_ADMIN_EMAIL'];
        }

        if ($email = Config::get('adminEmail')) {
            return $email;
        }

        return '';
    }
}
