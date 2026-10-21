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

namespace Markocupic\CalendarEventBookingBundle\Messenger\Message;

use Contao\CoreBundle\Messenger\Message\HighPriorityMessageInterface;

/**
 * Sends one notification for one booking, outside of the request that caused it.
 *
 * Dispatched by NotificationService::queueNotification() inside the transaction that
 * changes the booking. Contao routes it to the doctrine transport contao_prio_high,
 * which writes it into tl_message_queue over the same database connection - so it is
 * committed together with that change or not at all. That is the whole point: a
 * booking can no longer end up paid, or promoted from the waiting list, without the
 * notification that tells the participant about it.
 *
 * Only ids and scalars: the message is serialised, and the handler loads the
 * records fresh, as they are when it runs.
 */
final readonly class SendBookingNotificationMessage implements HighPriorityMessageInterface
{
    /**
     * @param string      $messageId identifies this message across redeliveries, see the handler
     * @param string|null $locale    the language of the notification, taken from the request
     *                               that queued it; there is no request in the worker
     */
    public function __construct(
        public string $messageId,
        public int $notificationId,
        public int $bookingId,
        public int|null $orderId = null,
        public int|null $paymentId = null,
        public string|null $locale = null,
    ) {
    }
}
