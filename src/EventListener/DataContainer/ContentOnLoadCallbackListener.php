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

namespace Markocupic\CalendarEventBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback(table: 'tl_calendar_events_member', target: 'config.onload')]
class ContentOnLoadCallbackListener
{
    public function __invoke(): void
    {
        // Add Backend CSS
        $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
    }
}
