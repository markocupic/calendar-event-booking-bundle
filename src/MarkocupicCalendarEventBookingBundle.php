<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle;

use Markocupic\CalendarEventBookingBundle\DependencyInjection\MarkocupicCalendarEventBookingExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicCalendarEventBookingBundle extends Bundle
{
    public function getContainerExtension(): MarkocupicCalendarEventBookingExtension
    {
        return new MarkocupicCalendarEventBookingExtension();
    }
}
