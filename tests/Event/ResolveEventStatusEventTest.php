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

namespace Markocupic\CalendarEventBookingBundle\Tests\Event;

use Contao\CalendarEventsModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Event\ResolveEventStatusEvent;
use Symfony\Component\HttpFoundation\Request;

class ResolveEventStatusEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $calEvent = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 2]);
        $request = new Request();

        $event = new ResolveEventStatusEvent($calEvent, $request, 'bookable');

        $this->assertSame($calEvent, $event->getCalendarEvent());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame('bookable', $event->getEventStatus());
    }

    public function testEventStatusCanBeChanged(): void
    {
        $event = new ResolveEventStatusEvent($this->createClassWithPropertiesMock(CalendarEventsModel::class), new Request(), 'bookable');

        $event->setEventStatus('fullyBooked');

        $this->assertSame('fullyBooked', $event->getEventStatus());
    }
}
