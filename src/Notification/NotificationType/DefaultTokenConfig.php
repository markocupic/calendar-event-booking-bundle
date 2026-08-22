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

namespace Markocupic\CalendarEventBookingBundle\Notification\NotificationType;

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
                'order_*',
                'order_uuid',
                'order_bookingUuid',
                'order_provider',
                'order_providerOrderId',
                'order_createTime',
                'order_status',
                'order_payerId',
                'order_payerEmail',
                'order_grossAmount',
                'order_netAmount',
                'order_vatAmount',
                'order_currencyCode',
                'order_details',
                'order_notes',
                'payment_*',
                'payment_uuid',
                'payment_bookingUuid',
                'payment_orderUuid',
                'payment_type',
                'payment_provider',
                'payment_providerOrderId',
                'payment_providerCaptureId',
                'payment_captureTime',
                'payment_status',
                'payment_isFinal',
                'payment_grossAmount',
                'payment_netAmountReceived',
                'payment_captureFee',
                'payment_currencyCode',
                'payment_settlementCurrencyCode',
                'payment_settlementGrossAmount',
                'payment_exchangeRate',
                'payment_refundTime',
                'payment_refundAmount',
                'payment_refundExchangeRate',
                'payment_refundSettlementAmount',
                'payment_refundFee',
                'payment_details',
                'payment_notes',
            ],
            'file_token' => [
                // empty
            ],
        ];
    }
}
