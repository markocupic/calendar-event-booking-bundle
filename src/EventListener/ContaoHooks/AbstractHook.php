<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks;

abstract class AbstractHook
{
    public const HOOK_SET_CASE = 'calEvtBookingSetCase';
    public const HOOK_ADD_FIELD = 'calEvtBookingAddField';
    public const HOOK_PRE_BOOKING = 'calEvtBookingPreBooking';
    public const HOOK_POST_BOOKING = 'calEvtBookingPostBooking';
    public const HOOK_PRE_VALIDATE_BOOKING_FORM = 'calEvtBookingPreValidate';
    public const HOOK_VALIDATE_REGISTRATION = 'calEvtBookingValidateRegistration';
    public const HOOK_UNSUBSCRIBE_FROM_EVENT = 'calEvtBookingUnsubscribeFromEvent';
    public const HOOK_BOOKING_STATE_CHANGE = 'calEvtBookingStateChange';

    protected static bool $hookIsDisabled = false;

    public static function disableHook(): void
    {
        self::$hookIsDisabled = true;
    }

    public static function enableHook(): void
    {
        self::$hookIsDisabled = false;
    }

    public static function isEnabled(): bool
    {
        return !self::$hookIsDisabled;
    }
}
