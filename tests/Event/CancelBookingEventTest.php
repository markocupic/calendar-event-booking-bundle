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
use Markocupic\CalendarEventBookingBundle\Event\CancelBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CancelBookingEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 1]);
        $request = new Request();

        $event = new CancelBookingEvent($booking, 'unsubscribe', $request);

        $this->assertSame($booking, $event->getBooking());
        $this->assertSame('unsubscribe', $event->getContext());
        $this->assertSame($request, $event->getRequest());
    }

    public function testResponseIsNullByDefaultAndPropagationRunning(): void
    {
        $event = new CancelBookingEvent($this->createClassWithPropertiesMock(CalendarEventsMemberModel::class), 'unsubscribe', null);

        $this->assertNull($event->getResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testSettingResponseStopsPropagation(): void
    {
        $event = new CancelBookingEvent($this->createClassWithPropertiesMock(CalendarEventsMemberModel::class), 'unsubscribe', null);
        $response = new Response();

        $event->setResponse($response);

        $this->assertSame($response, $event->getResponse());
        $this->assertTrue($event->isPropagationStopped());
    }
}
