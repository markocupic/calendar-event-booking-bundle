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

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

class CalendarEventsOrder
{
    #[AsCallback(table: 'tl_calendar_events_order', target: 'fields.grossAmount.load')]
    #[AsCallback(table: 'tl_calendar_events_order', target: 'fields.netAmount.load')]
    #[AsCallback(table: 'tl_calendar_events_order', target: 'fields.vatAmount.load')]
    public function loadAsDoublePrecision(float $value, DataContainer $dc): string
    {
        return number_format($value, 2, '.', '');
    }
}
