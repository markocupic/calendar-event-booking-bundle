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
use Markocupic\CalendarEventBookingBundle\Event\AutoDeleteExpiredBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;

class AutoDeleteExpiredBookingEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 1]);
        $request = new Request();

        $event = new AutoDeleteExpiredBookingEvent($booking, 'cron', $request);

        $this->assertSame($booking, $event->getBooking());
        $this->assertSame('cron', $event->getContext());
        $this->assertSame($request, $event->getRequest());
    }

    public function testShouldDeleteDefaultsToTrueAndCanBeToggled(): void
    {
        $event = new AutoDeleteExpiredBookingEvent($this->createClassWithPropertiesMock(CalendarEventsMemberModel::class), 'cron', null);

        $this->assertTrue($event->shouldDelete());

        $event->setShouldDelete(false);

        $this->assertFalse($event->shouldDelete());
    }
}
