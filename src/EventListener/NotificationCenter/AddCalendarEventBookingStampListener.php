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

namespace Markocupic\CalendarEventBookingBundle\EventListener\NotificationCenter;

use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Notification\Parcel\Stamp\CalendarEventBookingStamp;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\CreateParcelEvent;
use Terminal42\NotificationCenterBundle\Parcel\Parcel;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\NotificationConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\TokenCollectionStamp;

class AddCalendarEventBookingStampListener
{
    public function __construct(
        #[AutowireLocator('cebb.notification', defaultIndexMethod: 'getType')]
        private readonly ContainerInterface $notificationTypes,
        private readonly ContaoFramework $framework,
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

        $notificationConfigStamp = $parcel->getStamp(NotificationConfigStamp::class);

        if (!$notificationConfigStamp instanceof NotificationConfigStamp) {
            return;
        }

        if (!$this->notificationTypes->has($notificationConfigStamp->toArray()['type'])) {
            return;
        }

        $uuid = $this->getBookingTokenFromParcel($parcel);

        $booking = $this->framework->getAdapter(CalendarEventsMemberModel::class)->findOneBy('bookingToken', $uuid);

        if (null === $booking) {
            return;
        }

        // We will need to read this later in order to log the notifications for the bookings.
        $calendarEventBookingStamp = $this->framework->createInstance(CalendarEventBookingStamp::class, [(string) $booking->id, $notificationConfigStamp->toArray()['type']]);
        $parcel = $parcel->withStamp($calendarEventBookingStamp);

        $event->setParcel($parcel);
    }

    private function getBookingTokenFromParcel(Parcel $parcel): string|null
    {
        $tokenCollectionStamp = $parcel->getStamp(TokenCollectionStamp::class);

        if (!$tokenCollectionStamp instanceof TokenCollectionStamp) {
            return null;
        }

        $tokens = $tokenCollectionStamp->tokenCollection->toKeyValue();

        return $tokens['member_bookingToken'] ?? null;
    }
}
