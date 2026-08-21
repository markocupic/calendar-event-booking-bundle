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

namespace Markocupic\CalendarEventBookingBundle\Domain\Payment;

class AmountConverter
{
    /**
     * Currencies without decimal places.
     */
    public const array ZERO_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW',
        'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF',
        'XOF', 'XPF',
    ];

    /**
     * Currencies with three decimal places. Stripe requires the last digit
     * of the minor amount to be zero.
     */
    public const array THREE_DECIMAL_CURRENCIES = [
        'BHD', 'JOD', 'KWD', 'OMR', 'TND',
    ];

    public function toMinorUnits(float $amount, string $currencyCode): int
    {
        $factor = $this->getFactor($currencyCode);

        $minor = (int) round($amount * $factor);

        if (\in_array($this->normalize($currencyCode), self::THREE_DECIMAL_CURRENCIES, true)) {
            // Stripe only accepts amounts ending with a zero for these currencies.
            $minor = (int) (round($minor / 10) * 10);
        }

        return $minor;
    }

    public function fromMinorUnits(int $amount, string $currencyCode): float
    {
        return round($amount / $this->getFactor($currencyCode), 4);
    }

    public function getFactor(string $currencyCode): int
    {
        $currencyCode = $this->normalize($currencyCode);

        if (\in_array($currencyCode, self::ZERO_DECIMAL_CURRENCIES, true)) {
            return 1;
        }

        if (\in_array($currencyCode, self::THREE_DECIMAL_CURRENCIES, true)) {
            return 1000;
        }

        return 100;
    }

    private function normalize(string $currencyCode): string
    {
        return strtoupper(trim($currencyCode));
    }
}
