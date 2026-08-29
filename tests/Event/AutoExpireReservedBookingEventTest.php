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
use Markocupic\CalendarEventBookingBundle\Event\AutoExpireReservedBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;

class AutoExpireReservedBookingEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 1]);
        $request = new Request();

        $event = new AutoExpireReservedBookingEvent($booking, 'cron', $request);

        $this->assertSame($booking, $event->getBooking());
        $this->assertSame('cron', $event->getContext());
        $this->assertSame($request, $event->getRequest());
    }

    public function testShouldExpireDefaultsToTrueAndCanBeToggled(): void
    {
        $event = new AutoExpireReservedBookingEvent($this->createClassWithPropertiesStub(CalendarEventsMemberModel::class), 'cron', null);

        $this->assertTrue($event->shouldExpire());

        $event->setShouldExpire(false);

        $this->assertFalse($event->shouldExpire());
    }
}
