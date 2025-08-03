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

namespace Markocupic\CalendarEventBookingBundle\NotificationType;

class DefaultTokenConfig
{
    public static function getDefaultTokenConfig(): array
    {
        return [
            'email_token' => [
                'admin_email',
                'member_email',
                'organizer_email',
            ],
            'text_token' => [
                'admin_email',
                'event_*',
                'event_endDateFormatted',
                'event_endTimeFormatted',
                'event_startDateFormatted',
                'event_startTimeFormatted',
                'event_title',
                'event_unsubscribeLimitTstamp',
                'event_requireOptIn',
                'member_*',
                'member_optInLink',
                'member_dateOfBirth',
                'member_salutation',
                'member_unsubscribeLink',
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
        ];
    }
}
