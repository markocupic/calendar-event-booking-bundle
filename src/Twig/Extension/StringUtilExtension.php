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

use Contao\StringUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringUtilExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('decode_entities', [$this, 'entityDecode']),
        ];
    }

    public function entityDecode(string $string): string
    {
        return StringUtil::decodeEntities($string);
    }
}
