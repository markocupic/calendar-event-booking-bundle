<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Contao\UserModel;
use Markocupic\CalendarEventBookingBundle\Event\SendNotificationEvent;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\UnsubscribeLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Terminal42\NotificationCenterBundle\Receipt\ReceiptCollection;

class NotificationManager
{
    private const PREFIX_MEMBER = 'member_';

    private const PREFIX_CALENDAR = 'calendar_';

    private const PREFIX_EVENT = 'event_';

    private const PREFIX_ORGANIZER = 'organizer_';

    private const PREFIX_PAYMENT = 'payment_';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly NotificationCenter $notificationCenter,
        private readonly RequestStack $requestStack,
        private readonly UnsubscribeLinkBuilder $unsubscribeLinkBuilder,
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

    public function getNotificationTokens(CalendarEventsMemberModel $booking, CalendarEventsPaymentModel|null $payment = null): array
    {
        $event = $this->getEventOrFail($booking);
        $calendar = $this->getCalendarOrFail($event);

        $this->getControllerAdapter()->loadLanguageFile('tl_calendar_events_member');

        $tokens = [];
        $tokens = $this->addMemberTokens($tokens, $booking);
        $tokens = $this->addCalendarTokens($tokens, $calendar);
        $tokens = $this->addEventTokens($tokens, $event);
        $tokens = $this->addOrganizerTokens($tokens, $event);
        $tokens = $this->addUnsubscribeToken($tokens, $booking);

        if (null !== $payment) {
            $tokens = $this->addPaymentTokens($tokens, $payment);
        }

        return $tokens;
    }

    protected function getEventOrFail(CalendarEventsMemberModel $booking): object
    {
        $event = $booking->getRelated('pid');

        if (null === $event) {
            throw new \Exception(\sprintf('Event with ID %s not found.', $booking->pid));
        }

        return $event;
    }

    protected function getCalendarOrFail(object $event): object
    {
        $calendar = $event->getRelated('pid');
        if (null === $calendar) {
            throw new \Exception(\sprintf('Calendar with ID %s not found.', $event->pid));
        }

        return $calendar;
    }

    protected function addMemberTokens(array $tokens, CalendarEventsMemberModel $booking): array
    {
        foreach ($booking->row() as $key => $value) {
            $tokens[self::PREFIX_MEMBER.$key] = StringUtil::revertInputEncoding((string) $value);
        }

        if (!empty($booking->gender) && !empty($GLOBALS['TL_LANG']['tl_calendar_events_member']['salutation_'.$booking->gender])) {
            $tokens[self::PREFIX_MEMBER.'salutation'] = StringUtil::revertInputEncoding(
                (string) $GLOBALS['TL_LANG']['tl_calendar_events_member']['salutation_'.$booking->gender],
            );
        }

        return $tokens;
    }

    protected function addCalendarTokens(array $tokens, object $calendar): array
    {
        foreach ($calendar->row() as $key => $value) {
            $tokens[self::PREFIX_CALENDAR.$key] = StringUtil::revertInputEncoding((string) $value);
        }

        return $tokens;
    }

    protected function addEventTokens(array $tokens, object $event): array
    {
        foreach ($event->row() as $key => $value) {
            $tokens[self::PREFIX_EVENT.$key] = StringUtil::revertInputEncoding((string) $value);
        }

        return $tokens;
    }

    protected function addOrganizerTokens(array $tokens, object $event): array
    {
        $organizer = $this->getUserModelAdapter()->findById($event->eventBookingNotificationSender);
        if (null !== $organizer) {
            foreach ($organizer->row() as $key => $value) {
                if ('password' === $key || 'session' === $key) {
                    continue;
                }
                $tokens[self::PREFIX_ORGANIZER.$key] = StringUtil::revertInputEncoding((string) $value);
            }
        }

        return $tokens;
    }

    protected function addUnsubscribeToken(array $tokens, CalendarEventsMemberModel $booking): array
    {
        $tokens[self::PREFIX_MEMBER.'unsubscribeLink'] = $this->unsubscribeLinkBuilder->build($booking);

        return $tokens;
    }

    protected function addPaymentTokens(array $tokens, CalendarEventsPaymentModel $payment): array
    {
        foreach ($payment->row() as $key => $value) {
            $tokens[self::PREFIX_PAYMENT.$key] = StringUtil::revertInputEncoding((string) $value);
        }

        return $tokens;
    }

    protected function getControllerAdapter(): Adapter
    {
        return $this->framework->getAdapter(Controller::class);
    }

    protected function getUserModelAdapter(): Adapter
    {
        return $this->framework->getAdapter(UserModel::class);
    }
}
