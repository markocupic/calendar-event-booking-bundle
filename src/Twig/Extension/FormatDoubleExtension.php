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

namespace Markocupic\CalendarEventBookingBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatDoubleExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('format_double', [$this, 'formatDouble']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('format_double', [$this, 'formatDouble']),
        ];
    }

    public function formatDouble($number, int $decimals = 2, string $decPoint = '.', string $thousandsSep = ''): string
    {
        return number_format((float) $number, $decimals, $decPoint, $thousandsSep);
    }
}
