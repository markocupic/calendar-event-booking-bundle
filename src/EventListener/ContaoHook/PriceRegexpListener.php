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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Widget;

#[AsHook('addCustomRegexp')]
class PriceRegexpListener
{
    public const REGEXP_NAME = 'paypal_price';

    public function __invoke(string $regexp, $input, Widget $widget): bool
    {
        if (self::REGEXP_NAME === $regexp) {
            $regex = '/^(0|\d+\.\d{2})$/';

            if (preg_match($regex, $input)) {
                return true;
            }
            $error = 'Invalid format. Please use a number with two decimal places. Example: 1234.56';
            $widget->addError($error);
        }

        return false;
    }
}
