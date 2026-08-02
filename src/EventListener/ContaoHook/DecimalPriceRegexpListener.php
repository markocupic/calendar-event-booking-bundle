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
use Markocupic\CalendarEventBookingBundle\Validator\DecimalPriceValidator;

#[AsHook('addCustomRegexp')]
class DecimalPriceRegexpListener
{
    public const string REGEXP_NAME = 'decimal_price';

    public function __invoke(string $regexp, $input, Widget $widget): bool
    {
        if (self::REGEXP_NAME === $regexp) {
            if (DecimalPriceValidator::validate($input)) {
                return true;
            }

            $error = 'Invalid format. Please use a number with two decimal places. Example: 1234.56';

            $widget->addError($error);
        }

        return false;
    }
}
