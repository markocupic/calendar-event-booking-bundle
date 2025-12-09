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

namespace Markocupic\CalendarEventBookingBundle\EventListener;

use Markocupic\CalendarEventBookingBundle\Event\FrontendModuleGetResponseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class TestListener
{
    public function __invoke(FrontendModuleGetResponseEvent $event): void
    {
        $request = $event->getRequest();

        $request->attributes->set('_calendar_event_booking_token', '123456789');
    }
}
