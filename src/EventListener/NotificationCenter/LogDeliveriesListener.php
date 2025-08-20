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

use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsBookingNotificationModel;
use Markocupic\CalendarEventBookingBundle\Parcel\Stamp\CalendarEventBookingStamp;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\ReceiptEvent;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\Mailer\EmailStamp;

#[AsEventListener]
class LogDeliveriesListener
{
    public function __construct(
        private readonly Connection $connection,
        #[AutowireLocator('cebb.notification', defaultIndexMethod: 'getType')]
        private ContainerInterface $notificationTypes,
        private readonly array $notificationLogExclude,
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
            'senderAddress' => $email['from'],
            'senderName' => $email['fromName'],
            'replyTo' => $email['replyTo'],
            'recipientsTo' => $email['to'],
            'recipientsCc' => $email['cc'],
            'recipientsBcc' => $email['bcc'],
            'subject' => $email['subject'],
            'text' => $email['text'],
            'html' => $email['html'],
            'attachments' => !empty($email['attachmentVouchers']) ? json_encode($email['attachmentVouchers']) : '',
            'embeddedImages' => !empty($email['embeddedImageVouchers']) ? json_encode($email['embeddedImageVouchers']) : '',
            'exception' => '',
        ];

        if (!empty($set['text']) && $set['html'] === $set['text']) {
            unset($set['html']);
        }

        if ($receipt->wasDelivered()) {
            $set['delivered'] = 1;
            $set = array_filter(array_combine(array_keys($set), array_values($set)), fn ($v, $k) => !\in_array($k, $this->notificationLogExclude, true), ARRAY_FILTER_USE_BOTH);

            $this->connection->insert(CalendarEventsBookingNotificationModel::getTable(), $set);

            return;
        }

        $set['delivered'] = 0;
        $set['exception'] = $receipt->getException()->getMessage();
        $set = array_filter(array_combine(array_keys($set), array_values($set)), fn ($v, $k) => !\in_array($k, $this->notificationLogExclude, true), ARRAY_FILTER_USE_BOTH);

        $this->connection->insert(CalendarEventsBookingNotificationModel::getTable(), $set);
    }
}
