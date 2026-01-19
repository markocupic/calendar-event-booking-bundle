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

namespace Markocupic\CalendarEventBookingBundle\NotificationType;

class DefaultTokenConfig
{
    public static function getDefaultTokenConfig(): array
    {
        return [
            'email_token' => [
                'member_email',
                'organizer_email',
            ],
            'text_token' => [
                'calendar_*',
                'calendar_title',
                'calendar_requireOptIn',
                'event_*',
                'event_endDateFormatted',
                'event_endTimeFormatted',
                'event_startDateFormatted',
                'event_startTimeFormatted',
                'event_title',
                'event_unsubscribeLimitTstamp',
                'member_*',
                'member_dateOfBirth',
                'member_salutation',
                'member_unsubscribeLink',
                'member_waitingList',
                'member_temporaryReserved',
                'member_temporaryReserved',
                'member_canceled',
                'member_expired',
                'member_paid',
                'member_ticketAmount',
                'organizer_*',
                'organizer_email',
                'organizer_name',
                'payment_*',
                'payment_uuid',
                'payment_bookingUuid',
                'payment_paidAt',
                'payment_refundedAt',
                'payment_method',
                'payment_transactionId',
                'payment_transactionStatus',
                'payment_currencyCode',
                'payment_taxValue',
                'payment_grossAmount',
                'payment_netAmount',
                'payment_vatAmount',
                'payment_transactionDetails',
                'payment_notes',
            ],
            'file_token' => [
                // empty
            ],
        ];
    }
}
