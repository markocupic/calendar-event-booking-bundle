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

namespace Markocupic\CalendarEventBookingBundle\Payment;

enum PaymentStatus: string
{
    case APPROVED = 'approved';
    case DECLINED = 'expired';
    case PENDING = 'pending';
    case CANCELLED = 'canceled';
    case REFUNDED = 'refunded';
    case PENDING_APPROVAL = 'pending_approval';
    case PENDING_PAYMENT = 'pending_payment';
    case PENDING_CONFIRMATION = 'pending_confirmation';
    case PENDING_PAYMENT_CONFIRMATION = 'pending_payment_confirmation';
    case PENDING_PAYMENT_APPROVAL = 'pending_payment_approval';

    const COMPLETED = 'completed';
}
