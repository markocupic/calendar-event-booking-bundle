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

namespace Markocupic\CalendarEventBookingBundle\Validator;

class DecimalPriceValidator
{
    private const string REGEX = '/^(0|\d+\.\d{2})$/';

    public static function validate(string $value): bool
    {
        if (!preg_match(self::REGEX, $value)) {
            return false;
        }

        return is_numeric($value);
    }
}
