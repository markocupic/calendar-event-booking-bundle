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

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Event\BookingConfirmEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;

class BookingConfirmEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 1]);
        $request = new Request();

        $event = new BookingConfirmEvent($booking, 'opt-in', $request);

        $this->assertSame($booking, $event->getBooking());
        $this->assertSame('opt-in', $event->getContext());
        $this->assertSame($request, $event->getRequest());
    }

    public function testRequestMayBeNull(): void
    {
        $event = new BookingConfirmEvent($this->createClassWithPropertiesMock(CalendarEventsMemberModel::class), 'opt-in', null);

        $this->assertNull($event->getRequest());
    }
}
