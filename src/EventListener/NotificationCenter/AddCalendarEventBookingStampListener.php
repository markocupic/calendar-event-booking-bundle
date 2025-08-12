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

namespace Markocupic\CalendarEventBookingBundle\EventListener\NotificationCenter;

use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Parcel\Stamp\CalendarEventBookingStamp;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\Event\CreateParcelEvent;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\NotificationConfigStamp;

readonly class AddCalendarEventBookingStampListener
{
    public function __construct(
        #[TaggedLocator('cebb.notification', defaultIndexMethod: 'getType')]
        private ContainerInterface $notificationTypes,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Add the CalendarEventBookingStamp to the notification. We will need to read
     * this later in order to log the notifications for the bookings.
     */
    #[AsEventListener]
    public function onCreatParcel(CreateParcelEvent $event): void
    {
        $parcel = $event->getParcel();

        $notificationConfig = $parcel->getStamp(NotificationConfigStamp::class);

        if (!$notificationConfig instanceof NotificationConfigStamp) {
            return;
        }

        if (!$this->notificationTypes->has($notificationConfig->toArray()['type'])) {
            return;
        }

        $uuid = $this->requestStack->getCurrentRequest()?->attributes->get('_calendar_event_booking_token');

        if (empty($uuid)) {
            return;
        }

        $booking = CalendarEventsMemberModel::findOneBy('bookingToken', $uuid);

        if (null === $booking) {
            return;
        }

        // We will need to read this later in order to log the notifications for the bookings.
        $calendarEventBookingStamp = new CalendarEventBookingStamp((string) $booking->id, $notificationConfig->toArray()['type']);
        $parcel = $parcel->withStamp($calendarEventBookingStamp);

        $event->setParcel($parcel);
    }
}
