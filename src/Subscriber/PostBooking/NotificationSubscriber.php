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

namespace Markocupic\CalendarEventBookingBundle\Subscriber\PostBooking;

use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NotificationSubscriber implements EventSubscriberInterface
{
    public const PRIORITY = 1000;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    public function __construct(NotificationHelper $notificationHelper)
    {
        $this->notificationHelper = $notificationHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBookingEvent::NAME => ['notify', self::PRIORITY],
        ];
    }

    /**
     * Launch post booking notification.
     *
     * @throws \Exception
     */
    public function notify(PostBookingEvent $event): void
    {
        if ($event->isDisabled(self::class)) {
            return;
        }

        $event->disableSubscriber(ContaoLogSubscriber::class);
        $this->notificationHelper->notify($event->getEventMember(), $event->getEvent());
    }
}
