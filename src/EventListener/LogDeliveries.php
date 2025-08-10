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

namespace Markocupic\CalendarEventBookingBundle\EventListener;

use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsBookingNotificationModel;
use Markocupic\CalendarEventBookingBundle\Parcel\Stamp\CalendarEventBookingStamp;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\ReceiptEvent;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\Mailer\EmailStamp;

#[AsEventListener]
class LogDeliveries
{
    public function __construct(
        private readonly Connection $connection,
        #[TaggedLocator('cebb.notification', defaultIndexMethod: 'getType')]
        private ContainerInterface $notificationTypes,
    ) {
    }

    public function __invoke(ReceiptEvent $event): void
    {
        $receipt = $event->receipt;

        if (!$receipt->getParcel()->hasStamp(CalendarEventBookingStamp::class)) {
            return;
        }

        if (!$receipt->getParcel()->hasStamp(EmailStamp::class)) {
            return;
        }

        $booking = $receipt->getParcel()->getStamp(CalendarEventBookingStamp::class)->toArray();

        if (!$this->notificationTypes->has($booking['notification_type'])) {
            return;
        }

        $email = $receipt->getParcel()->getStamp(EmailStamp::class)->toArray();

        $set = [
            'pid' => (int) $booking['booking_id'],
            'tstamp' => time(),
            'deliveredOn' => time(),
            'type' => $booking['notification_type'],
            'recipientsTo' => $email['to'],
            'recipientsCc' => $email['cc'],
            'recipientsBcc' => $email['bcc'],
            'subject' => $email['subject'],
            'exception' => '',
        ];

        if ($receipt->wasDelivered()) {
            $set['delivered'] = 1;

            $this->connection->insert(CalendarEventsBookingNotificationModel::getTable(), $set);

            return;
        }

        $set['delivered'] = 0;
        $set['exception'] = $receipt->getException()->getMessage();
        $this->connection->insert(CalendarEventsBookingNotificationModel::getTable(), $set);
    }
}
